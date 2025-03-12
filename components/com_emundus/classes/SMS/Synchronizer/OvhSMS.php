<?php

namespace classes\SMS\Synchronizer;

use classes\SMS\Entities\SMSEntity;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Ovh\Api;

class OvhSMS {

	private ?Api $api = null;

	private array $sms_services = [];

	private string $used_service = '';

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.sms.php',], Log::ALL, ['com_emundus.sms']);

		try {
			$auth = $this->getAuthenticationInfos();
			$this->api = new Api($auth['client_id'], $auth['client_secret'], 'ovh-eu', $auth['consumer_key']);
			$this->setSmsServices();
		} catch (\Exception $e) {
			Log::add('Error on Ovh api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		if (empty($this->used_service)) {
			Log::add('No sms service available', Log::ERROR, 'com_emundus.sms');
			throw new \Exception('No sms service available');
		}
	}

	private function getAuthenticationInfos(): array
	{
		$auth = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('config')
			->from('#__emundus_setup_sync')
			->where('type like ' . $db->quote('ovh'));

		try {
			$db->setQuery($query);
			$config = json_decode($db->loadResult(), true);

			if (!empty($config['authentication'])) {
				$auth = [
					'client_id' => $config['authentication']['client_id'],
					'client_secret' => $config['authentication']['client_secret'],
					'consumer_key' => $config['authentication']['consumer_key']
				];

				if (!empty($auth['client_secret'])) {
					require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
					$auth['client_secret'] = \EmundusHelperFabrik::decryptDatas($auth['client_secret']);
				}
			}
		} catch (\Exception $e) {
			Log::add('Error on get Ovh api authentication infos : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $auth;
	}

	private function setSmsServices(): void
	{
		try {
			$services = $this->api->get('/sms');

			if (!empty($services)) {
				foreach ($services as $service_name) {
					$this->sms_services[] = $service_name;
				}

				$this->used_service = current($this->sms_services);
			}
		} catch (\Exception $e) {
			Log::add('Error on Ovh api get sms services : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}
	}

	public function getSmsServices(): array
	{
		return $this->sms_services;
	}

	public function sendSMS(SMSEntity $sms): bool
	{
		$sent = false;

		if ($sms->doesMessageContainTags()) {
			$sent_states = [];

			foreach($sms->getReceivers() as $receiver) {
				$message = $sms->getMessage();
				$transformedMessage = $sms->transformMessage($message, $receiver);
				$sent_states[] = $this->sendSMSOVH($sms, $transformedMessage, [$receiver->phone_number]);
			}

			$sent = !in_array(false, $sent_states);
		} else {
			$message = $sms->getMessage();
			$phoneNumbers = $sms->getReceiversPhoneNumbers();

			$sent = $this->sendSMSOVH($sms, $message, $phoneNumbers);
		}

		return $sent;
	}

	/**
	 * @param   SMSEntity  $sms
	 * @param   string     $message
	 * @param   array      $phoneNumbers
	 *
	 * @return bool
	 */
	private function sendSMSOVH(SMSEntity $sms, string $message, array $phoneNumbers): bool
	{
		$sent = false;

		try {
			$response = $this->api->post('/sms/' . $this->used_service . '/jobs', [
				"charset"=> "UTF-8",
				"class"=> "phoneDisplay",
				"coding"=> "7bit",
				'message' => $message,
				"noStopClause"=> false,
				"priority"=> "high",
				'receivers' => $phoneNumbers,
				"senderForResponse"=> true,
				"validityPeriod"=> 2880
			]);

			if (!empty($response['invalidReceivers'])) {
				Log::add('Invalid receivers : ' . implode(', ', $response['invalidReceivers']), Log::ERROR, 'com_emundus.sms');

				foreach ($response['invalidReceivers'] as $phone_number) {
					$sms->save($phone_number, $message, 'COM_EMUNDUS_SEND_SMS_FAILED');
				}
			}

			if (!empty($response['validReceivers'])) {
				Log::add('SMS sent to : ' . implode(', ', $response['validReceivers']), Log::INFO, 'com_emundus.sms');

				foreach ($response['validReceivers'] as $phone_number) {
					$sms->save($phone_number, $message);
				}
			}

			$sent = empty($response['invalidReceivers']) && !empty($response['validReceivers']);
		} catch (\Exception $e) {
			Log::add('Error on Ovh api send sms : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $sent;
	}
}