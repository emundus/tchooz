<?php

namespace Tchooz\Synchronizers\GED\Sacem;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\api\Api;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\SacemGed\SacemGedAuthenticator;
use Tchooz\Synchronizers\MappingTransportInterface;

/**
 * Sacem GED transport: authenticates through the Sacem identity provider (via SacemGedAuthenticator)
 * and carries requests to the B2B API, including the two ports of the document deposit: the presign
 * request and the raw byte upload.
 *
 * The GED exposes no object-search route, so this transport deliberately does not implement
 * ObjectSearchInterface: mapping objects for the GED must resolve existence from stored external
 * references rather than a remote lookup.
 *
 * Like every mapping transport it is ignorant of business objects and routes; the Sacem GED mapping
 * objects carry that knowledge.
 */
class SacemSynchronizer extends Api implements MappingTransportInterface
{
	public const TYPE = 'sacem_ged';

	/**
	 * Path prefix shared by every document route, appended to the configured instance URL.
	 */
	public const DOCUMENTS_API_PATH = 'api';

	private string $partnerId;

	public function __construct()
	{
		parent::__construct();

		Log::addLogger(['text_file' => 'com_emundus.ged_sacem.php'], Log::ALL, ['com_emundus.ged_sacem']);

		$syncEntity = (new SynchronizerRepository())->getByType(self::TYPE);

		if (empty($syncEntity))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SACEM_GED_MISSING_CONFIGURATION'));
		}

		// Both values are hand-typed and end up inside a URL, so stray whitespace is trimmed here
		// rather than trusted: a trailing space silently turns every route into a 404.
		$apiConfig       = $syncEntity->getConfig()['api'] ?? [];
		$baseUrl         = trim((string) ($apiConfig['base_url'] ?? ''));
		$this->partnerId = trim((string) ($apiConfig['partner_id'] ?? ''));

		if (empty($baseUrl) || $this->partnerId === '')
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SACEM_GED_MISSING_CONFIGURATION'));
		}

		// Auth failures propagate (no silent catch) so the real cause surfaces to the action log.
		$token = SacemGedAuthenticator::fromSynchronizer($syncEntity)->getAccessToken();

		$this->setBaseUrl(rtrim($baseUrl, '/'));

		$this->setHeaders([
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
			'Accept'        => 'application/json',
		]);
		$this->setClient();
		$this->setAuth($token);
	}

	public function getPartnerId(): string
	{
		return $this->partnerId;
	}

	/**
	 * Step 1: request a presigned upload target for the document described by $metadata.
	 *
	 * @return array{url: ?string, key: ?string, headers: array<string,string>}
	 * @throws \Exception
	 */
	public function presignDocument(array $metadata): array
	{
		$route = self::DOCUMENTS_API_PATH . '/partners/' . rawurlencode($this->partnerId) . '/documents/presign';

		$response = $this->post(
			$route,
			json_encode($metadata),
			['Content-Type' => 'application/json', 'Accept' => 'application/json']
		);

		if (empty($response) || !in_array($response['status'], [200, 201], true))
		{
			Log::add('Sacem GED presign failed on ' . $this->getBaseUrl() . '/' . $route . ' : ' . json_encode($response), Log::ERROR, 'com_emundus.ged_sacem');

			// The API reports a length violation without naming the field. Log the sizes, never the
			// values: the payload carries the beneficiary's name and id.
			if (($response['status'] ?? 0) === 400)
			{
				$sizes = [];

				foreach ($metadata as $name => $value)
				{
					$sizes[] = $name . '=' . mb_strlen((string) $value);
				}

				Log::add('Sacem GED rejected payload field sizes : ' . implode(' ', $sizes), Log::ERROR, 'com_emundus.ged_sacem');
			}

			throw new \RuntimeException(Text::sprintf(
				'COM_EMUNDUS_SACEM_GED_PRESIGN_FAILED',
				$metadata['filename'] ?? '',
				$this->describeError($response)
			));
		}

		return $this->extractPresignTarget($response['data']);
	}

	/**
	 * Turn an error body into one readable reason, so a failed deposit says why in the task history
	 * instead of only how. Two shapes reach us: the API's JSON
	 * ({"status": ..., "errors": [{"key": ..., "message": ...}]}) and, on the upload step, S3's XML.
	 */
	private function describeError(array $response): string
	{
		$body    = (string) ($response['error'] ?? $response['error_details'] ?? '');
		$reasons = [];

		if (str_starts_with(ltrim($body), '<'))
		{
			$reasons[] = $this->describeXmlError($body);
		}
		else
		{
			$decoded = $body !== '' ? json_decode($body) : null;

			foreach ($decoded->errors ?? [] as $error)
			{
				$reasons[] = trim(($error->key ?? '') . ' ' . ($error->message ?? ''));
			}

			if (empty(array_filter($reasons)))
			{
				$reasons[] = $decoded->description ?? $decoded->message ?? (string) ($response['message'] ?? '');
			}
		}

		return trim(($response['status'] ?? '') . ' ' . implode(' ; ', array_filter($reasons)));
	}

	/**
	 * S3 answers the upload with an XML error document rather than the API's JSON envelope.
	 */
	private function describeXmlError(string $body): string
	{
		$previous = libxml_use_internal_errors(true);
		$xml      = simplexml_load_string($body);
		libxml_use_internal_errors($previous);

		if ($xml === false)
		{
			return '';
		}

		return trim(((string) ($xml->Code ?? '')) . ' ' . ((string) ($xml->Message ?? '')));
	}

	/**
	 * Step 2: send the raw bytes to the presigned target. The URL is pre-authorised, so the API
	 * bearer token is deliberately not attached.
	 *
	 * The headers come from the presign response and are sent exactly as received: they are part of
	 * the signature, and any difference is rejected with a 403.
	 *
	 * @param   array<string,string>  $requiredHeaders
	 *
	 * @throws \RuntimeException when the target refuses the upload, carrying its reason.
	 * @throws \Exception
	 */
	public function putDocument(string $presignedUrl, string $filePath, array $requiredHeaders): void
	{
		$transport = new Api();
		$transport->setBaseUrl($presignedUrl);
		$transport->setHeaders([]);
		$transport->setClient();

		$response = $transport->put(
			$presignedUrl,
			file_get_contents($filePath),
			$requiredHeaders
		);

		if (empty($response) || !in_array($response['status'], [200, 201, 204], true))
		{
			Log::add('Sacem GED presigned upload failed for ' . $filePath . ' : ' . json_encode($response), Log::ERROR, 'com_emundus.ged_sacem');

			throw new \RuntimeException(Text::sprintf(
				'COM_EMUNDUS_SACEM_GED_UPLOAD_FAILED',
				basename($filePath),
				$this->describeError($response)
			));
		}
	}

	/**
	 * Read the upload target out of the presign response: the signed URL (valid 15 minutes), the key
	 * that identifies the document from then on, and the headers the upload must carry.
	 *
	 * @return array{url: ?string, key: ?string, headers: array<string,string>}
	 */
	private function extractPresignTarget(mixed $data): array
	{
		$target = ['url' => null, 'key' => null, 'headers' => []];

		if (empty($data) || !is_object($data))
		{
			Log::add('Sacem GED presign returned no usable body : ' . json_encode($data), Log::ERROR, 'com_emundus.ged_sacem');

			return $target;
		}

		$target['url'] = !empty($data->url) && is_string($data->url) ? $data->url : null;
		$target['key'] = !empty($data->key) ? (string) $data->key : null;

		foreach ((array) ($data->requiredHeaders ?? []) as $name => $value)
		{
			$target['headers'][(string) $name] = (string) $value;
		}

		if ($target['url'] === null)
		{
			Log::add('Sacem GED presign response carried no upload URL, keys were : ' . implode(', ', array_keys(get_object_vars($data))), Log::ERROR, 'com_emundus.ged_sacem');
		}

		return $target;
	}
}
