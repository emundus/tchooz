<?php

namespace Tchooz\Synchronizers\Payment;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Enums\Payment\PayboxEnvironmentEnum;
use Tchooz\Enums\Payment\PayboxResponseCodeEnum;
use Tchooz\Repositories\Payment\TransactionRepository;

class Paybox implements PaymentSynchronizerInterface
{
	private const HASH_ALGORITHM = 'SHA512';

	private const RETURN_MAPPING = 'Mt:M;Ref:R;Auto:A;Erreur:E;Signature:K';

	private DatabaseDriver $db;

	private array $config = [];

	private string $hmacKey = '';

	private int $syncId = 0;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.paybox.php'], Log::ALL, ['com_emundus.paybox']);

		try {
			$this->db = Factory::getContainer()->get('DatabaseDriver');
			$this->setConfig();

			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
			}

			if (!empty($this->config['authentication']['hmac_key']))
			{
				$this->hmacKey = \EmundusHelperFabrik::decryptDatas($this->config['authentication']['hmac_key']);
			}
		} catch (\Exception $e) {
			Log::add('Error on Paybox connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.paybox');
		}
	}

	private function setConfig(): void
	{
		$query = $this->db->getQuery(true);

		$query->select('id, config')
			->from('#__emundus_setup_sync')
			->where('type like ' . $this->db->quote('paybox'));

		try {
			$this->db->setQuery($query);
			$sync = $this->db->loadAssoc();

			if (!empty($sync) && !empty($sync['config'])) {
				$config = json_decode($sync['config'], true);

				if (!empty($config)) {
					$this->syncId = (int) $sync['id'];
					$this->config = $config;
				}
			}
		} catch (\Exception $e) {
			Log::add('Error on get Paybox api config : ' . $e->getMessage(), Log::ERROR, 'com_emundus.paybox');
		}
	}

	private function getEndpoint(): string
	{
		$endpoint = $this->config['configuration']['endpoint'] ?? '';
		if (!empty($endpoint)) {
			return $endpoint;
		}

		$environment = PayboxEnvironmentEnum::tryFrom($this->config['configuration']['mode'] ?? '');
		if ($environment === null) {
			Log::add('No valid Paybox mode configured', Log::ERROR, 'com_emundus.paybox');
			throw new \Exception(Text::_('COM_EMUNDUS_PAYBOX_ERROR_INVALID_MODE'));
		}

		return $environment->getEndpoint();
	}

	public function prepareCheckout(TransactionEntity $transaction, CartEntity $cart): array
	{
		$currency = $transaction->getCurrency()->getIso4217();
		if (empty($currency)) {
			throw new \Exception(Text::_('COM_EMUNDUS_PAYBOX_ERROR_INVALID_CURRENCY'));
		}

		$ipnUrl        = Uri::base() . 'index.php?option=com_emundus&controller=webhook&task=updatePaymentTransaction&sync_id=' . $this->syncId;
		$browserReturn = $ipnUrl . '&transaction_ref=' . $transaction->getExternalReference();

		$fields = [
			'PBX_SITE'        => $this->config['authentication']['site'] ?? '',
			'PBX_RANG'        => $this->config['authentication']['rang'] ?? '',
			'PBX_IDENTIFIANT' => $this->config['authentication']['identifiant'] ?? '',
			'PBX_TOTAL'       => (int) round($transaction->getAmount() * 100),
			'PBX_DEVISE'      => $currency,
			// todo: make this part editable through parameters/payment configuration ?
			'PBX_TYPEPAIEMENT' => 'CARTE',
			'PBX_TYPECARTE'   => 'CB',
			'PBX_CMD'         => $transaction->getExternalReference(),
			'PBX_PORTEUR'     => $cart->getCustomer()->getEmail(),
			'PBX_RETOUR'      => self::RETURN_MAPPING,
			'PBX_HASH'        => self::HASH_ALGORITHM,
			'PBX_TIME'        => date('c'),
			'PBX_REPONDRE_A'  => $ipnUrl,
			'PBX_RUF1'        => 'POST',
			'PBX_EFFECTUE'    => $browserReturn,
			'PBX_REFUSE'      => $browserReturn,
			'PBX_ANNULE'      => $browserReturn,
			'PBX_ATTENTE'     => $browserReturn,
		];

		$fields['PBX_HMAC'] = $this->buildHmac($fields);

		return [
			'action' => $this->getEndpoint(),
			'method' => 'POST',
			'fields' => $fields,
			'type'   => 'form',
		];
	}

	private function buildHmac(array $fields): string
	{
		if (empty($this->hmacKey)) {
			Log::add('Paybox HMAC key is not set in configuration', Log::ERROR, 'com_emundus.paybox');
			throw new \Exception(Text::_('COM_EMUNDUS_PAYBOX_ERROR_HMAC_KEY_MISSING'));
		}

		$pairs = [];
		foreach ($fields as $name => $value) {
			$pairs[] = $name . '=' . $value;
		}
		$message = implode('&', $pairs);

		$binaryKey = pack('H*', $this->hmacKey);

		return strtoupper(hash_hmac('sha512', $message, $binaryKey));
	}

	public function verifySignature(string $payload): bool
	{
		$publicKey = $this->config['authentication']['public_key'] ?? '';
		if (empty($publicKey)) {
			Log::add('Paybox public key is not set in configuration', Log::ERROR, 'com_emundus.paybox');
			return false;
		}

		$marker   = '&Signature=';
		$position = strpos($payload, $marker);
		if ($position === false) {
			Log::add('No Paybox signature found in callback data', Log::ERROR, 'com_emundus.paybox');
			return false;
		}

		$signedData = substr($payload, 0, $position);
		$signature  = base64_decode(urldecode(substr($payload, $position + strlen($marker))));

		$publicKeyResource = openssl_pkey_get_public($this->normalizePublicKey($publicKey));
		if ($publicKeyResource === false) {
			Log::add('Invalid Paybox public key', Log::ERROR, 'com_emundus.paybox');
			return false;
		}

		$verified = openssl_verify($signedData, $signature, $publicKeyResource, OPENSSL_ALGO_SHA1);

		if ($verified !== 1) {
			Log::add('Paybox signature verification failed', Log::WARNING, 'com_emundus.paybox');
		}

		return $verified === 1;
	}

	private function normalizePublicKey(string $key): string
	{
		$key = trim($key);

		if (preg_match('/-----BEGIN ([A-Z0-9 ]+)-----\s*(.*?)\s*-----END \1-----/s', $key, $matches)) {
			$body = preg_replace('/\s+/', '', $matches[2]);

			return '-----BEGIN ' . $matches[1] . "-----\n" . chunk_split($body, 64, "\n") . '-----END ' . $matches[1] . "-----\n";
		}

		return $key;
	}

	public function updateTransactionFromCallback(array $data, int $transaction_id, int $user_id): bool
	{
		$updated = false;

		if (!empty($transaction_id) && !empty($data)) {
			$transactionRepository = new TransactionRepository();
			$transaction           = $transactionRepository->getById($transaction_id);

			if (!empty($transaction) && $transaction->getExternalReference() === ($data['Ref'] ?? '')) {
				$code         = $data['Erreur'] ?? '';
				$responseCode = PayboxResponseCodeEnum::fromCode($code);
				$status       = $responseCode->getTransactionStatus();

				if ($transaction->getStatus() === TransactionStatus::CONFIRMED && $status !== TransactionStatus::CONFIRMED) {
					Log::add('Ignoring Paybox callback for transaction ' . $transaction->getId() . ' : already CONFIRMED, refusing downgrade to ' . $status->value . ' (Erreur=' . $code . ')', Log::WARNING, 'com_emundus.paybox');

					return false;
				}

				$transaction->setStatus($status);
				$transaction->setUpdatedAt(date('Y-m-d H:i:s'));
				$transaction->setUpdatedBy($user_id);
				$updated = $transactionRepository->saveTransaction($transaction, $user_id);

				if ($updated) {
					Log::add('Transaction ' . $transaction->getId() . ' updated from Paybox callback (Erreur=' . $code . ', status=' . $status->value . ')', Log::INFO, 'com_emundus.paybox');
				} else {
					Log::add('Failed to update transaction ' . $transaction->getId() . ' from Paybox callback', Log::ERROR, 'com_emundus.paybox');
				}

				if ($status === TransactionStatus::FAILED) {
					$reason = $responseCode->getLabel() . ' (code ' . $code . ')';
					$transactionRepository->logFailureReason($transaction, $reason, $user_id, ['code' => $code]);
				}
			} else {
				Log::add('Transaction not found or reference mismatch from Paybox callback', Log::ERROR, 'com_emundus.paybox');
			}
		}

		return $updated;
	}
}