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
use Tchooz\Enums\Payment\WorldlineEnvironmentEnum;
use Tchooz\Enums\Payment\WorldlineStatusCategoryEnum;
use Tchooz\Repositories\CountryRepository;
use Tchooz\Repositories\Payment\TransactionRepository;
use Worldline\Connect\Sdk\Client;
use Worldline\Connect\Sdk\Communicator;
use Worldline\Connect\Sdk\CommunicatorConfiguration;
use Worldline\Connect\Sdk\V1\Domain\Address;
use Worldline\Connect\Sdk\V1\Domain\AmountOfMoney;
use Worldline\Connect\Sdk\V1\Domain\CardPaymentMethodSpecificInputBase;
use Worldline\Connect\Sdk\V1\Domain\ContactDetails;
use Worldline\Connect\Sdk\V1\Domain\CreateHostedCheckoutRequest;
use Worldline\Connect\Sdk\V1\Domain\Customer;
use Worldline\Connect\Sdk\V1\Domain\HostedCheckoutSpecificInput;
use Worldline\Connect\Sdk\V1\Domain\Order;
use Worldline\Connect\Sdk\V1\Domain\OrderReferences;
use Worldline\Connect\Sdk\V1\Webhooks\WebhooksHelper;
use Worldline\Connect\Sdk\Webhooks\InMemorySecretKeyStore;

class Worldline implements PaymentSynchronizerInterface
{
	private const INTEGRATOR = 'eMundus';

	private const LOG_CHANNEL = 'com_emundus.worldline';

	private DatabaseDriver $db;

	private array $config = [];

	private int $sync_id = 0;

	private string $merchant_id = '';

	private ?Client $client = null;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.worldline.php'], Log::ALL, [self::LOG_CHANNEL]);
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		try
		{
			$this->setConfig();

			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
			}

			$auth = $this->config['authentication'] ?? [];

			$this->merchant_id = $auth['merchant_id'] ?? '';
			$api_key_id        = $auth['api_key_id'] ?? '';
			$api_secret        = !empty($auth['api_secret']) ? \EmundusHelperFabrik::decryptDatas($auth['api_secret']) : '';

			if (empty($this->merchant_id) || empty($api_key_id) || empty($api_secret))
			{
				throw new \Exception('Worldline credentials are not set in configuration');
			}

			$communicatorConfiguration = new CommunicatorConfiguration(
				$api_key_id,
				$api_secret,
				$this->getEnvironment()->getEndpoint(),
				self::INTEGRATOR
			);

			$this->client = new Client(new Communicator($communicatorConfiguration));
		}
		catch (\Exception $e)
		{
			Log::add('Worldline client initialization failed: ' . $e->getMessage(), Log::ERROR, self::LOG_CHANNEL);
			throw new \Exception('Failed to initialize Worldline client');
		}
	}

	private function setConfig(): void
	{
		$query = $this->db->createQuery();

		$query->select('id, config')
			->from($this->db->quoteName('#__emundus_setup_sync'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('worldline'));

		try
		{
			$this->db->setQuery($query);
			$sync = $this->db->loadAssoc();

			if (!empty($sync['config']))
			{
				$config = json_decode($sync['config'], true);

				if (!empty($config))
				{
					$this->sync_id = (int) $sync['id'];
					$this->config  = $config;
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error on get Worldline api config : ' . $e->getMessage(), Log::ERROR, self::LOG_CHANNEL);
		}
	}

	/**
	 * Drives the server-to-server API host only. The MyCheckout domain the consumer is
	 * redirected to is a separate setting (checkout_subdomain).
	 */
	private function getEnvironment(): WorldlineEnvironmentEnum
	{
		return WorldlineEnvironmentEnum::fromProductionFlag($this->config['authentication']['mode'] ?? 0);
	}

	public function prepareCheckout(TransactionEntity $transaction, CartEntity $cart): array
	{
		$amountOfMoney                = new AmountOfMoney();
		$amountOfMoney->amount        = (int) round($transaction->getAmount() * 100);
		// Worldline expects the alphabetic ISO 4217 code ("EUR"), not the numeric one used by Lyra/Paybox.
		$amountOfMoney->currencyCode  = $transaction->getCurrency()->getIso3();

		$contactDetails               = new ContactDetails();
		$contactDetails->emailAddress = $cart->getCustomer()->getEmail();

		$customer                 = new Customer();
		$customer->contactDetails = $contactDetails;
		$customer->billingAddress = $this->buildBillingAddress($cart);
		// Required by the account configuration; same identifier Lyra sends as vads_cust_id.
		$customer->merchantCustomerId = (string) $cart->getCustomer()->getUserId();

		$references                    = new OrderReferences();
		$references->merchantReference = $transaction->getExternalReference();

		$order                = new Order();
		$order->amountOfMoney = $amountOfMoney;
		$order->customer      = $customer;
		$order->references    = $references;

		$returnUrl = Uri::base()
			. 'index.php?option=com_emundus&controller=webhook&task=updatePaymentTransaction&sync_id=' . $this->sync_id
			. '&transaction_ref=' . $transaction->getExternalReference();

		$hostedCheckoutSpecificInput            = new HostedCheckoutSpecificInput();
		$hostedCheckoutSpecificInput->returnUrl = $returnUrl;
		$hostedCheckoutSpecificInput->locale    = str_replace('-', '_', Factory::getApplication()->getLanguage()->getTag());
		// Without this, a consumer abandoning the page comes back indistinguishable from a failure.
		$hostedCheckoutSpecificInput->returnCancelState = true;

		// Capture the funds straight away. Left to the account default, the payment stops at
		// PENDING_APPROVAL and waits for a manual approval on the Worldline side, which would
		// never reach CAPTURED and never confirm the transaction here.
		$cardPaymentMethodSpecificInput                   = new CardPaymentMethodSpecificInputBase();
		$cardPaymentMethodSpecificInput->requiresApproval = false;

		$request                                  = new CreateHostedCheckoutRequest();
		$request->order                           = $order;
		$request->hostedCheckoutSpecificInput     = $hostedCheckoutSpecificInput;
		$request->cardPaymentMethodSpecificInput  = $cardPaymentMethodSpecificInput;

		try
		{
			$response = $this->client->v1()->merchant($this->merchant_id)->hostedcheckouts()->create($request);
		}
		catch (\Exception $e)
		{
			Log::add('Worldline hosted checkout creation failed: ' . $e->getMessage(), Log::ERROR, self::LOG_CHANNEL);
			throw new \Exception('Failed to create Worldline hosted checkout');
		}

		return [
			'action' => $this->buildRedirectUrl($response->partialRedirectUrl ?? ''),
			'method' => 'POST',
			'data'   => [],
			'type'   => 'redirect',
		];
	}

	/**
	 * Worldline refuses the request without a billing country (BILLING_ADDRESS_COUNTRY_CODE_IS_REQUIRED),
	 * so the customer address is mandatory here. verifyCart() already guarantees one exists
	 * before checkout, this only guards against a malformed one.
	 */
	private function buildBillingAddress(CartEntity $cart): Address
	{
		$addresses = $cart->getCustomer()->getAddresses();
		$address   = !empty($addresses) ? $addresses[0] : null;

		if ($address === null)
		{
			Log::add('No billing address on cart ' . $cart->getId(), Log::ERROR, self::LOG_CHANNEL);

			throw new \Exception(Text::_('COM_EMUNDUS_CART_CUSTOMER_ADDRESS_NOT_SET'));
		}

		// The cart stores a country id, Worldline expects the ISO 3166-1 alpha-2 code.
		$country = (new CountryRepository())->getById((int) ($address->getCountry() ?? 0));

		$billingAddress              = new Address();
		$billingAddress->countryCode = $country?->getIso2() ?? '';

		if (empty($billingAddress->countryCode))
		{
			Log::add('No country on billing address for cart ' . $cart->getId(), Log::ERROR, self::LOG_CHANNEL);

			throw new \Exception(Text::_('COM_EMUNDUS_CART_CUSTOMER_ADDRESS_NOT_SET'));
		}

		$billingAddress->street         = $address->getStreetAddress() ?: null;
		$billingAddress->additionalInfo = $address->getExtendedAddress() ?: null;
		$billingAddress->city           = $address->getLocality() ?: null;
		$billingAddress->zip            = $address->getPostalCode() ?: null;

		return $billingAddress;
	}

	/**
	 * partialRedirectUrl already carries the host and path, minus its first label:
	 *   pay1.preprod.checkout.worldline-solutions.com/checkout/...
	 * That label is the MyCheckout subdomain, "payment" unless the account uses a custom one.
	 */
	private const DEFAULT_CHECKOUT_SUBDOMAIN = 'payment';

	private function buildRedirectUrl(string $partialRedirectUrl): string
	{
		if (empty($partialRedirectUrl))
		{
			Log::add('Worldline returned an empty partialRedirectUrl', Log::ERROR, self::LOG_CHANNEL);
			throw new \Exception('Failed to create Worldline hosted checkout');
		}

		$subdomain          = trim($this->config['authentication']['checkout_subdomain'] ?? '');
		$subdomain          = trim(preg_replace('#^https?://#', '', $subdomain), './');
		$partialRedirectUrl = ltrim($partialRedirectUrl, '/');

		if ($subdomain === '')
		{
			$subdomain = self::DEFAULT_CHECKOUT_SUBDOMAIN;
		}

		// Accept a full host as well as a bare label: both are natural things to paste from
		// the Configuration Center, and prepending a full host would produce a broken URL.
		if (str_contains($subdomain, '.'))
		{
			$path = strstr($partialRedirectUrl, '/');

			return 'https://' . $subdomain . ($path === false ? '/' : $path);
		}

		return 'https://' . $subdomain . '.' . $partialRedirectUrl;
	}

	/**
	 * @param   string  $payload  Raw request body
	 * @param   array   $headers  Request headers, including X-GCS-KeyId and X-GCS-Signature
	 */
	public function verifySignature(string $payload, array $headers): bool
	{
		$auth = $this->config['authentication'] ?? [];

		$key_id = $auth['webhook_key_id'] ?? '';
		$secret = !empty($auth['webhook_secret']) ? \EmundusHelperFabrik::decryptDatas($auth['webhook_secret']) : '';

		if (empty($key_id) || empty($secret))
		{
			Log::add('Worldline webhook keys are not set in configuration', Log::ERROR, self::LOG_CHANNEL);

			return false;
		}

		try
		{
			$secretKeyStore = new InMemorySecretKeyStore();
			$secretKeyStore->storeSecretKey($key_id, $secret);

			// unmarshal() validates the signature and throws when it does not match.
			(new WebhooksHelper($secretKeyStore))->unmarshal($payload, $headers);

			return true;
		}
		catch (\Exception $e)
		{
			Log::add('Worldline signature verification failed: ' . $e->getMessage(), Log::ERROR, self::LOG_CHANNEL);

			return false;
		}
	}

	public function updateTransactionFromCallback(array $data, int $transaction_id, int $user_id): bool
	{
		$updated = false;

		if (empty($transaction_id) || empty($data))
		{
			return false;
		}

		$transaction_repository = new TransactionRepository();
		$transaction            = $transaction_repository->getById($transaction_id);

		if (empty($transaction))
		{
			Log::add('Transaction not found on Worldline callback', Log::ERROR, self::LOG_CHANNEL);

			return false;
		}

		$payment           = $data['payment'] ?? [];
		$merchantReference = $payment['paymentOutput']['references']['merchantReference'] ?? '';

		if ($transaction->getExternalReference() !== $merchantReference)
		{
			Log::add('Transaction external reference does not match Worldline callback data', Log::ERROR, self::LOG_CHANNEL);

			return false;
		}

		$status   = $payment['status'] ?? '';
		$category = $payment['statusOutput']['statusCategory'] ?? '';

		$new_status = WorldlineStatusCategoryEnum::resolve($status, $category);

		if ($new_status === null)
		{
			Log::add('Ignoring Worldline callback for transaction ' . $transaction->getId() . ' : status "' . $status . '" (category "' . $category . '") has no equivalent', Log::WARNING, self::LOG_CHANNEL);

			return false;
		}

		// The transaction is confirmed from CAPTURE_REQUESTED onwards, so the later CAPTURED and
		// PAID events carry nothing new. They are logged and dropped, which also blocks any
		// downgrade of an already acquired payment.
		if ($transaction->getStatus() === TransactionStatus::CONFIRMED)
		{
			Log::add('Ignoring Worldline callback for transaction ' . $transaction->getId() . ' : already CONFIRMED (status=' . $status . ', category=' . $category . ')', Log::INFO, self::LOG_CHANNEL);

			return false;
		}

		$transaction->setStatus($new_status);
		$transaction->setUpdatedAt(date('Y-m-d H:i:s'));
		$transaction->setUpdatedBy($user_id);

		try
		{
			$updated = $transaction_repository->saveTransaction($transaction, $user_id);
		}
		catch (\Exception $e)
		{
			Log::add('Error updating transaction from Worldline callback: ' . $e->getMessage(), Log::ERROR, self::LOG_CHANNEL);

			return false;
		}

		if ($updated)
		{
			Log::add('Transaction ' . $transaction->getId() . ' updated to ' . $new_status->value . ' from Worldline callback', Log::INFO, self::LOG_CHANNEL);
		}
		else
		{
			Log::add('Failed to update transaction ' . $transaction->getId() . ' from Worldline callback', Log::ERROR, self::LOG_CHANNEL);
		}

		if ($new_status === TransactionStatus::FAILED)
		{
			$transaction_repository->logFailureReason($transaction, 'Worldline status ' . $status, $user_id, ['status' => $status, 'category' => $category]);
		}

		return $updated;
	}
}
