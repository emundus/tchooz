<?php
/**
 * @package     Tchooz\Synchronizers\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Synchronizers\NumericSign;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Smalot\PdfParser\Parser;
use Tchooz\api\Api;
use Tchooz\Traits\TraitDispatcher;

if (!class_exists('EmundusHelperFabrik'))
{
	require_once(JPATH_ROOT . '/components/com_emundus/helpers/EmundusHelperFabrik.php');
}

class YousignSynchronizer extends Api
{
	use TraitDispatcher;

	public function __construct()
	{
		parent::__construct();

		Log::addLogger(['text_file' => 'com_emundus.yousign.php',], Log::ALL, ['com_emundus.yousign']);

		try
		{
			$auth = $this->getAuthenticationInfos();

			$this->setBaseUrl($auth['base_url']);
			$headers = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $auth['token'],
				'Accept'        => 'application/json'
			);
			$this->setHeaders($headers);
			$this->setClient();
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
		}
	}

	public function initRequest(string $name, string $delivery_mode = 'email', string $expiration_date = ''): array
	{
		$payload = [
			'name'          => trim($name),
			'delivery_mode' => $delivery_mode
		];
		
		if(!empty($expiration_date))
		{
			$payload['expiration_date'] = $expiration_date;
		}

		try
		{
			$response = $this->post('signature_requests', json_encode($payload));

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api init request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function activateRequest(string $procedure_id): array
	{
		try
		{
			$response = $this->post('signature_requests/' . $procedure_id . '/activate');

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api activate request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function cancelRequest(string $procedure_id, ?string $cancel_reason = ''): array
	{
		$payload = [
			'reason' => 'contractualization_aborted'
		];

		if (!empty($cancel_reason))
		{
			$payload['custom_note'] = $cancel_reason;
		}

		try
		{
			$response = $this->post('signature_requests/' . $procedure_id . '/cancel', json_encode($payload));

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api cancel request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function deleteRequest(string $procedure_id): array
	{
		try
		{
			$response = $this->delete('signature_requests/' . $procedure_id);

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api delete request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function getRequest(string $procedure_id): array
	{
		$signature_request = [];

		try
		{
			$response = $this->get('signature_requests/' . $procedure_id);

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			if (!empty($response['data']->id))
			{
				$signature_request = [
					'status'  => $response['status'],
					'message' => $response['message'],
					'data'    => $response['data']
				];
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api get request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			$signature_request = [
				'status'  => $e->getCode(),
				'message' => $e->getMessage()
			];
		}

		return $signature_request;
	}

	public function getRequests(?string $cursor = null): array
	{
		try
		{
			$params = [];
			if (!empty($cursor))
			{
				$params['after'] = $cursor;
			}

			$response = $this->get('signature_requests', $params);

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			$signature_request = [
				'status'  => $response['status'],
				'message' => $response['message'],
				'data'    => $response['data']
			];
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api get requests : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			$signature_request = [
				'status'  => $e->getCode(),
				'message' => $e->getMessage()
			];
		}

		return $signature_request;
	}

	public function addDocument(string $procedure_id, string $file_path): array
	{
		if (pathinfo($file_path, PATHINFO_EXTENSION) !== 'pdf')
		{
			Log::add('Error on Yousign api add document : file is not a pdf', Log::ERROR, 'com_emundus.yousign');

			return [];
		}
		if (!file_exists($file_path))
		{
			Log::add('Error on Yousign api add document : file not found', Log::ERROR, 'com_emundus.yousign');

			return [];
		}

		$payload = [
			[
				'name'     => 'file',
				'contents' => fopen($file_path, 'r'),
				'filename' => basename($file_path),
				'headers'  => [
					'Content-Type' => 'application/pdf'
				]
			],
			[
				'name'     => 'nature',
				'contents' => 'signable_document'
			],
			[
				'name'     => 'parse_anchors',
				'contents' => 'true'
			]
		];

		try
		{
			$response = $this->post('signature_requests/' . $procedure_id . '/documents', $payload, [], true);

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api add document : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function downloadDocuments(string $procedure_id, string $document_id): array
	{
		$documents = [];

		try
		{
			$headers  = [
				'Cache-Control' => 'no-cache'
			];
			$response = $this->get('signature_requests/' . $procedure_id . '/documents/' . $document_id . '/download', [], $headers);

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			if (!empty($response['data']))
			{
				$documents = [
					'status'  => $response['status'],
					'message' => $response['message'],
					'data'    => $response['data']
				];
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api download documents : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			$documents = [
				'status'  => $e->getCode(),
				'message' => $e->getMessage()
			];
		}

		return $documents;
	}

	public function getAuditTrails(string $procedure_id, string $signer_id): array
	{
		$audit_trails = [];

		try
		{
			$response = $this->get('signature_requests/' . $procedure_id . '/signers/' . $signer_id . '/audit_trails');

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			if (!empty($response['data']))
			{
				$audit_trails = [
					'status'  => $response['status'],
					'message' => $response['message'],
					'data'    => $response['data']
				];
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api get audit trails : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			$audit_trails = [
				'status'  => $e->getCode(),
				'message' => $e->getMessage()
			];
		}

		return $audit_trails;
	}

	public function getSigners(string $procedure_id): array
	{
		$signers = [];

		try
		{
			$response = $this->get('signature_requests/' . $procedure_id . '/signers');

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			if (!empty($response['data']))
			{
				$signers = [
					'status'  => $response['status'],
					'message' => $response['message'],
					'data'    => $response['data']
				];
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api get signers : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			$signers = [
				'status'  => $e->getCode(),
				'message' => $e->getMessage()
			];
		}

		return $signers;
	}

	public function addSigner(string $procedure_id, \stdClass $signer, string $document_id, object|string|null $signature_position = '', string $signature_level = 'electronic_signature', string $signature_authentication_mode = 'otp_email'): array
	{
		if($signature_level !== 'electronic_signature')
		{
			$signature_authentication_mode = 'otp_sms';
		}

		$payload = [
			'info'                          => [
				'email'      => trim($signer->email),
				'first_name' => trim($signer->firstname),
				'last_name'  => trim($signer->lastname),
				'locale'     => 'fr',
			],
			'signature_level'               => $signature_level,
			'signature_authentication_mode' => $signature_authentication_mode
		];
		
		if($signature_authentication_mode == 'otp_sms')
		{
			$phoneUtil = PhoneNumberUtil::getInstance();

			$phone_number = $signer->phone_1;
			if(!empty($phone_number))
			{
				if (preg_match('/^\w{2}/', $phone_number))
				{
					$region       = substr($phone_number, 0, 2);
					$phone_number = substr($phone_number, 2);

					$phone_number = $phoneUtil->parse($phone_number, $region);
				}

				if ($phoneUtil->isValidNumber($phone_number))
				{
					$phone_number = $phoneUtil->format($phone_number, PhoneNumberFormat::E164);

					$payload['info']['phone_number'] = $phone_number;
				}
			}
		}

		if (!empty($signature_position))
		{
			$payload['fields'] = [
				[
					'type'        => 'signature',
					'document_id' => $document_id,
					'page'        => $signature_position->page,
					'x'           => $signature_position->x,
					'y'           => $signature_position->y,
					'width'       => $signature_position->width,
					'height'      => $signature_position->height
				]
			];
		}

		if (!empty($signer->phone_number))
		{
			$payload['info']['phone_number'] = $signer->phone_number;
		}

		try
		{
			$response = $this->post('signature_requests/' . $procedure_id . '/signers', json_encode($payload));

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api add signer : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function sendReminder(string $procedure_id, string $signer_id): array
	{
		try
		{
			$response = $this->post('signature_requests/' . $procedure_id . '/signers/' . $signer_id . '/send_reminder');

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api send reminder : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function getConsumptionsData(): array
	{
		try
		{
			$response = $this->get('consumptions');

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api consumption : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function getWebhookSubscriptions(): array
	{
		try
		{
			$response = $this->get('webhooks');

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api webhook subscriptions : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function createWebhookSubscription(): array
	{
		$payload = [
			'description'       => 'Tchooz Webhook - ' . Factory::getApplication()->get('sitename'),
			'endpoint'          => Uri::base() . 'index.php?option=com_emundus&controller=sign&task=yousigncallback&format=raw',
			'sandbox'           => false,
			'subscribed_events' => ['signature_request.done', 'signer.done'],
			'scopes'            => ['*'],
			'auto_retry'        => true,
			'enabled'           => true
		];

		try
		{
			$response = $this->post('webhooks', json_encode($payload));

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api create webhook subscription : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function toggleWebhookSubscription(string $webhookId, bool $enable = true)
	{
		try
		{
			$payload = [
				'enabled' => $enable
			];

			$response = $this->patch('webhooks/' . $webhookId, json_encode($payload));

			if ($this->isThrowed($response))
			{
				throw new \Exception($response['message'], $response['status']);
			}

			return $response;
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api disable webhook subscription : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			return [];
		}
	}

	public function getWorkspaces(): array
	{
		try
		{
			$workspaces = $this->get('workspaces');

			if ($workspaces['status'] !== 200)
			{
				throw new \Exception($workspaces['message'], $workspaces['status']);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api workspaces : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			$workspaces = [
				'status'  => $e->getCode(),
				'message' => $e->getMessage()
			];
		}

		return $workspaces;
	}

	private function getAuthenticationInfos(): array
	{
		$auth = [];

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('config')
			->from('#__emundus_setup_sync')
			->where('type like ' . $db->quote('yousign'));

		try
		{
			$db->setQuery($query);
			$config = json_decode($db->loadResult(), true);

			if (!empty($config['authentication']))
			{
				$auth = [
					'base_url' => $config['base_url'],
					'token'    => \EmundusHelperFabrik::decryptDatas($config['authentication']['token'])
				];
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on get Yousign api authentication infos : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
		}

		return $auth;
	}

	private function isThrowed($response): bool
	{
		$throwException = false;

		switch ($response['status'])
		{
			case 400:
			case 401:
			case 403:
			case 404:
			case 405:
			case 429:
			case 500:
			case 503:
				$throwException = true;
				Log::add('Error on Yousign api : ' . $response['message'], Log::ERROR, 'com_emundus.yousign');

				$this->dispatchJoomlaEvent('onYousignError', [
					'status'  => $response['status'],
					'message' => $response['message'],
					'data'    => $response['data']
				]);
				break;
		}

		return $throwException;
	}
}