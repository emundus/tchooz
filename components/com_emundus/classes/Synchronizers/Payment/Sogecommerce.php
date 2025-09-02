<?php

namespace Tchooz\Synchronizers\Payment;

use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Language\Text;

use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\TransactionRepository;

class Sogecommerce
{
	private DatabaseDriver $db;

	private string $endpoint = '';
	private $config = [];

	private string $signature_mode = 'SHA256';

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.sogecommerce.php',], Log::ALL, ['com_emundus.sogecommerce']);

		try {
			$this->db = Factory::getContainer()->get('DatabaseDriver');
			$this->setConfig();
			$this->setEndpoint();
		} catch (\Exception $e) {
			Log::add('Error on Ovh api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}
	}

	private function getAuthenticationInfos(): array
	{
		$auth = [];

		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
		}

		$auth['client_id'] = $this->config['authentication']['client_id'] ?? '';
		$auth['client_secret'] = !empty($this->config['authentication']['client_secret']) ? \EmundusHelperFabrik::decryptDatas($this->config['authentication']['client_secret']) :  '';

		if (empty($auth['client_id']) || empty($auth['client_secret'])) {
			Log::add('No authentication available', Log::ERROR, 'com_emundus.sogecommerce');
			throw new \Exception('No authentication available');
		}

		return $auth;
	}

	private function setConfig(): void
	{
		$query = $this->db->getQuery(true);

		$query->select('config')
			->from('#__emundus_setup_sync')
			->where('type like ' . $this->db->quote('sogecommerce'));

		try {
			$this->db->setQuery($query);
			$config = json_decode($this->db->loadResult(), true);

			if (!empty($config)) {
				$this->config = $config;
			}
		} catch (\Exception $e) {
			Log::add('Error on get Sogecommerce api config : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sogecommerce');
		}
	}

	private function setEndpoint(): void
	{
		$endpoint = $this->config['endpoint'] ?? '';

		if (empty($endpoint)) {
			Log::add('No endpoint available', Log::ERROR, 'com_emundus.sogecommerce');
			throw new \Exception('No endpoint available');
		}

		$this->endpoint = $endpoint;
	}

	private function getEndpoint(): string
	{
		return $this->endpoint;
	}


	private function getSHA1Signature(array $params, string $key): string
	{
		/**
		 * Function that computes the signature.
		 * $params : table containing the fields to send in the payment form.
		 * $key : TEST or  PRODUCTION key
		 */
		$signature_content = "" ;
		ksort($params);
		foreach ($params as $name =>$value)
		{
			if (substr($name,0,5)=='vads_') {
				$signature_content .= $value."+";
			}
		}
		$signature_content .= $key;

		return sha1($signature_content);
	}

	/**
	 * Method provided by Sogecommerce to generate the signature
	 * @param   array   $params
	 * @param   string  $key
	 *
	 * @return string
	 */
	private function getSHA256Signature(array $params, string $key): string
	{
		$signature_content = "";
		ksort($params);
		foreach ($params as $name => $value)
		{
			if (substr($name,0,5)=='vads_')
			{
				$signature_content .= $value . "+";
			}
		}
		$signature_content .= $key;

		return base64_encode(hash_hmac('sha256', $signature_content, $key, true));
	}

	private function getCountryISO2(int $country_id): string
	{
		$iso2 = '';

		if (!empty($country_id))
		{
			$query = $this->db->createQuery();

			$query->select('iso2')
				->from('data_country')
				->where('id = ' . $country_id);

			$this->db->setQuery($query);
			$iso2 = $this->db->loadResult();
		}

		return $iso2;
	}

	/**
	 * Sogecommerce needs an alphanumeric string of 6 characters
	 * @param   string  $external_reference
	 *
	 * @return bool
	 */
	public function verifyReference(string $external_reference): bool
	{
		$valid = false;

		if (!empty($external_reference)) {
			// must not be longer than 6 characters
			if (strlen($external_reference) <= 6 && ctype_alnum($external_reference)) {
				$valid = true;
			} else {
				Log::add(Text::_('COM_EMUNDUS_ERROR_INVALID_EXTERNAL_REFERENCE'), Log::WARNING, 'com_emundus.sogecommerce');
			}
		}

		return $valid;
	}

	private function prepareDefaultFields(TransactionEntity $transaction, CartEntity $cart): array
	{
		$current_language = Factory::getApplication()->getLanguage()->getTag();
		$short_language = substr($current_language, 0, 2);


		$contact = $cart->getCustomer();
		$address = $contact->getAddress();

		if (!isset($this->config['mode']) || !in_array($this->config['mode'], ['TEST', 'PRODUCTION'])) {
			$this->config['mode'] = 'PRODUCTION';
		}

		$fields = [
			'vads_site_id' => $this->config['authentication']['client_id'],
			'vads_ctx_mode' => $this->config['mode'] ?? 'PRODUCTION',
			'vads_trans_id' => $transaction->getExternalReference(),
			'vads_trans_date' => date('YmdHis'),
			'vads_amount' => $transaction->getAmount() * 100,
			'vads_currency' => $transaction->getCurrency()->getIso4217(),
			'vads_action_mode' => 'INTERACTIVE',
			'vads_page_action' => 'PAYMENT',
			'vads_version' => 'V2',
			'vads_payment_config' => 'SINGLE',
			'vads_capture_delay' => 0,
			'vads_validation_mode' => 0,
			'vads_cust_id' => $contact->getUserId(),
			'vads_cust_email' => $contact->getEmail(),
			'vads_cust_first_name' => $contact->getFirstName(),
			'vads_cust_last_name' => $contact->getLastName(),
			'vads_cust_address' => $address->getAddress1(),
			'vads_cust_address2' => $address->getAddress2(),
			'vads_cust_city' => $address->getCity(),
			'vads_cust_zip' => $address->getZip(),
			'vads_cust_country' => $this->getCountryISO2($address->getCountry()),
			'vads_url_check' => $this->config['return_url'],
			'vads_url_return' => !empty($this->config['return_url']) ? $this->config['return_url'] . '&transaction_ref=' . $transaction->getExternalReference() : '',
			'vads_url_cancel' => !empty($this->config['return_url']) ? $this->config['return_url'] . '&transaction_ref=' . $transaction->getExternalReference() : '',
			'vads_url_error' => !empty($this->config['return_url']) ? $this->config['return_url'] . '&transaction_ref=' . $transaction->getExternalReference() : '',
			'vads_url_refused' => !empty($this->config['return_url']) ? $this->config['return_url'] . '&transaction_ref=' . $transaction->getExternalReference() : '',
			'vads_url_success' => !empty($this->config['return_url']) ? $this->config['return_url'] . '&transaction_ref=' . $transaction->getExternalReference() : '',
			'vads_language' => $short_language
		];
		ksort($fields);

		return $fields;
	}

	private function prepareSogecommerceImmediateTransaction(TransactionEntity $transaction, CartEntity $cart): array
	{
		$fields = $this->prepareDefaultFields($transaction, $cart);
		$fields['vads_payment_cards'] = 'CB';
		ksort($fields);

		$fields['signature'] = $this->generateSignature($fields);

		return $fields;
	}

	private function generateSignature(array $fields): string
	{
		$signature = '';

		if (!empty($fields)) {
			switch ($this->signature_mode) {
				case 'SHA1':
					$signature = $this->getSHA1Signature($fields, $this->getAuthenticationInfos()['client_secret']);
					break;
				case 'SHA256':
					$signature = $this->getSHA256Signature($fields, $this->getAuthenticationInfos()['client_secret']);
					break;
				default:
					throw new \Exception('Invalid signature mode');
			}
		}

		return $signature;
	}


	/**
	 * @param   TransactionEntity  $transaction
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function prepareSogecommerceInstallmentTransaction(TransactionEntity $transaction, CartEntity $cart): array
	{
		$fields = $this->prepareDefaultFields($transaction, $cart);

		$total = $transaction->getAmount();
		$nb_iterations = $cart->getNumberInstallmentDebit();
		$amounts_by_iterations = PaymentRepository::generateAmountsByIterations($total, $nb_iterations);

		if (!empty($cart->getPaymentStep()->getInstallmentMonthday())) {
			$monthday = $cart->getPaymentStep()->getInstallmentMonthday();
		} else {
			$monthday = $cart->getInstallmentMonthday();
		}
		$amount_by_date = end($amounts_by_iterations);

		$fields['vads_payment_cards'] = 'SDD';
		$fields['vads_page_action'] = 'REGISTER_SUBSCRIBE';
		$fields['vads_sub_amount'] = $amount_by_date * 100;
		$fields['vads_sub_desc'] = 'RRULE:FREQ=MONTHLY;COUNT=' . $nb_iterations . ';BYMONTHDAY=' . $monthday;
		$fields['vads_sub_currency'] = $transaction->getCurrency()->getIso4217();

		if (!empty($cart->getPaymentStep()->getInstallmentEffectDate())) {
			// if date is in less than 15 days, we set it to today + 15 days to
			// see https://sogecommerce.societegenerale.eu/doc/fr-FR/error-code/error-10115.html
			if (strtotime($cart->getPaymentStep()->getInstallmentEffectDate()) < strtotime('+15 days')) {
				$fields['vads_sub_effect_date'] = date('Ymd', strtotime('+15 days'));
			} else {
				$fields['vads_sub_effect_date'] = str_replace('-', '', $cart->getPaymentStep()->getInstallmentEffectDate());
			}
		} else {
			$fields['vads_sub_effect_date'] = date('Ymd', strtotime('+15 days'));
		}

		if ($amounts_by_iterations[0] !== end($amounts_by_iterations)) {
			$fields['vads_sub_init_amount'] = $amounts_by_iterations[0] * 100;
			$fields['vads_sub_init_amount_number'] = 1;
		}

		ksort($fields);

		$fields['signature'] = $this->generateSignature($fields);

		return $fields;
	}

	public function prepareCheckout(TransactionEntity $transaction, CartEntity $cart): array
	{
		switch($transaction->getPaymentMethod()->name)
		{
			case 'sepa':
				$fields = $this->prepareSogecommerceInstallmentTransaction($transaction, $cart);
				break;
			default:
				$fields = $this->prepareSogecommerceImmediateTransaction($transaction, $cart);
				break;
		}

		return [
			'action' => $this->getEndpoint(),
			'method' => 'POST',
			'fields' => $fields,
			'type' => 'form'
		];
	}

	public function verifySignature(array $fields): bool
	{
		$verified = false;

		if (!empty($fields) && !empty($fields['signature']))
		{
			$received_signature = $fields['signature'];
			unset($fields['signature']);
			ksort($fields);

			$expected_signature = $this->generateSignature($fields);

			$verified = hash_equals($expected_signature, $received_signature);
		}

		return $verified;
	}

	/**
	 * @param   array  $data
	 * @param   int    $transaction_id
	 * @param   int    $user_id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function updateTransactionFromCallback(array $data, int $transaction_id, int $user_id): bool
	{
		$updated = false;

		if (!empty($transaction_id) && !empty($data)) {
			$transaction_repository = new TransactionRepository();
			$transaction = $transaction_repository->getById($transaction_id);

			if (!empty($transaction) && $transaction->getExternalReference() === $data['vads_trans_id'])
			{
				switch($data['vads_trans_status']) {
					case 'ABANDONED':
					case 'CANCELLED':
					case 'EXPIRED':
					case 'REFUSED':
						$transaction->setStatus(TransactionStatus::CANCELLED);
						break;
					case 'CAPTURED':
					case 'ACCEPTED':
					case 'AUTHORISED':
						$transaction->setStatus(TransactionStatus::CONFIRMED);
						break;
					case 'CAPTURE_FAILED':
						$transaction->setStatus(TransactionStatus::FAILED);
						break;
					case 'SUSPENDED':
					case 'AUTHORISED_TO_VALIDATE':
					case 'UNDER_VERIFICATION':
					case 'WAITING_AUTHORISATION':
					case 'WAITING_AUTHORISATION_TO_VALIDATE':
						$transaction->setStatus(TransactionStatus::WAITING);
						break;
				}

				$transaction->setUpdatedAt(date('Y-m-d H:i:s'));
				$transaction->setUpdatedBy($user_id);
				$updated = $transaction_repository->saveTransaction($transaction, $user_id);
			} else {
				Log::add('Transaction not found or external reference mismatch', Log::ERROR, 'com_emundus.sogecommerce');
				throw new \Exception('Transaction not found or external reference mismatch');
			}
		}

		return $updated;
	}
}