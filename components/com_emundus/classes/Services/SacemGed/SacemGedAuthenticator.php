<?php

namespace Tchooz\Services\SacemGed;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\api\Api;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;

/**
 * Authentication for the Sacem GED integration: a client credentials grant against the Sacem
 * identity provider, sending the client credentials as HTTP Basic.
 *
 * The resulting token is cached until it expires so repeated calls within one lifecycle reuse it.
 * The authenticator receives an already-decrypted configuration (the caller decrypts secrets before
 * handing it over) and is agnostic of where the configuration comes from, which keeps it testable.
 */
class SacemGedAuthenticator
{
	private const GRANT_TYPE = 'client_credentials';

	private string $idpTokenUrl;

	private string $idpClientId;

	private string $idpClientSecret;

	private Api $transport;

	private ?string $accessToken = null;

	private int $expiresAt = 0;

	public function __construct(array $config, ?Api $transport = null)
	{
		$this->idpTokenUrl     = $config['idp_token_url'] ?? '';
		$this->idpClientId     = $config['idp_client_id'] ?? '';
		$this->idpClientSecret = $config['idp_client_secret'] ?? '';
		$this->transport       = $transport ?? new Api();

		Log::addLogger(['text_file' => 'com_emundus.ged_sacem.php'], Log::ALL, ['com_emundus.ged_sacem']);
	}

	/**
	 * Build an authenticator from a Sacem GED synchronizer, reading its authentication configuration
	 * and decrypting the stored secrets. Single source of truth for turning stored config into
	 * runnable credentials — used both by the transport and by the setup authentication test.
	 */
	public static function fromSynchronizer(SynchronizerEntity $synchronizer, ?Api $transport = null): self
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		$authConfig = $synchronizer->getConfig()['authentication'] ?? [];

		$config = [
			'idp_token_url'     => $authConfig['idp_token_url'] ?? '',
			'idp_client_id'     => $authConfig['idp_client_id'] ?? '',
			'idp_client_secret' => !empty($authConfig['idp_client_secret']) ? \EmundusHelperFabrik::decryptDatas($authConfig['idp_client_secret']) : '',
		];

		return new self($config, $transport);
	}

	/**
	 * Return a valid access token, requesting a new one only when the cached token is missing or
	 * about to expire.
	 *
	 * @throws \Exception
	 */
	public function getAccessToken(): string
	{
		if ($this->accessToken !== null && $this->expiresAt > (time() + 30))
		{
			return $this->accessToken;
		}

		if (empty($this->idpTokenUrl) || empty($this->idpClientId) || empty($this->idpClientSecret))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SACEM_GED_MISSING_CONFIGURATION'));
		}

		$this->transport->setBaseUrl($this->idpTokenUrl);
		$this->transport->setClient();

		$response = $this->transport->post(
			$this->idpTokenUrl,
			['grant_type' => self::GRANT_TYPE],
			[
				'Authorization' => 'Basic ' . base64_encode($this->idpClientId . ':' . $this->idpClientSecret),
				'Accept'        => 'application/json',
			]
		);

		if (empty($response) || $response['status'] !== 200 || empty($response['data']->access_token))
		{
			Log::add('Sacem GED token request failed : ' . json_encode($response), Log::ERROR, 'com_emundus.ged_sacem');

			throw new \RuntimeException(Text::_('COM_EMUNDUS_SACEM_GED_TOKEN_FAILED'));
		}

		$this->accessToken = $response['data']->access_token;
		$expiresIn         = !empty($response['data']->expires_in) ? (int) $response['data']->expires_in : 3599;
		$this->expiresAt   = time() + $expiresIn;

		return $this->accessToken;
	}
}
