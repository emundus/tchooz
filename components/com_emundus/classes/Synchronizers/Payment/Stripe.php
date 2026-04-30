<?php

namespace Tchooz\Synchronizers\Payment;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Stripe\StripeClient;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Exception\EmundusInvalidAmountException;
use Tchooz\Factories\Payment\StripeItemFactory;
use Tchooz\Repositories\Payment\TransactionRepository;

class Stripe
{
	const HANDLED_EVENTS = [
		'checkout.session.completed',
		'payment_intent.succeeded',
		'payment_intent.payment_failed'
	];

	private DatabaseDriver $db;

	private StripeClient $client;

	private int $sync_id = 0;

	private array $config = [];

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.stripe.php'], Log::ALL, ['com_emundus.stripe']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		try {
			$this->setConfig();

			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
			}

			if (!empty($this->config['authentication']['client_secret'])) {
				$client_secret = \EmundusHelperFabrik::decryptDatas($this->config['authentication']['client_secret']);
				$this->client = new StripeClient($client_secret);
			} else {
				throw new \Exception('Stripe client key is not set in configuration');
			}

			if (empty($this->config['authentication']['webhook_secret'])) {
				Log::add('Stripe webhook secret is not set in configuration', Log::WARNING, 'com_emundus.stripe');
			} else {
				$this->config['authentication']['webhook_secret'] = \EmundusHelperFabrik::decryptDatas($this->config['authentication']['webhook_secret']);
			}

		} catch (\Exception $e) {
			Log::add('Stripe client initialization failed: ' . $e->getMessage(), Log::ERROR, 'com_emundus.stripe');
			throw new \Exception('Failed to initialize Stripe client');
		}
	}

	private function setConfig(): void
	{
		$query = $this->db->getQuery(true);

		$query->select('id, config')
			->from('#__emundus_setup_sync')
			->where('type like ' . $this->db->quote('stripe'));

		try {
			$this->db->setQuery($query);
			$sync = $this->db->loadAssoc();
			$config = json_decode($sync['config'], true);

			if (!empty($config)) {
				$this->sync_id = (int) $sync['id'];
				$this->config = $config;
			}
		} catch (\Exception $e) {
			Log::add('Error on get stripe api config : ' . $e->getMessage(), Log::ERROR, 'com_emundus.stripe');
		}
	}

	public function prepareCheckout(TransactionEntity $transaction, CartEntity $cart): array
	{
		if ($cart->getTotal() < 0.50)
		{
			throw new EmundusInvalidAmountException(sprintf(Text::_('COM_EMUNDUS_STRIPE_MINIMUM_AMOUNT_ERROR'), $cart->getCurrency()->getSymbol()));
		}

		switch($transaction->getPaymentMethod()->name)
		{
			case 'sepa':
				// todo: implement kind of a subscription method for SEPA
				throw new \Exception('SEPA payment method is not supported yet with Stripe');
				break;
			default:
				// use card payment method
				$session = $this->createCheckoutSession($transaction, $cart);
				break;
		}

		return [
			'action' => $session->url,
			'method' => 'POST',
			'data' => [],
			'type' => 'redirect'
		];
	}

	private function createCheckoutSession(TransactionEntity $transaction, CartEntity $cart): ?\Stripe\Checkout\Session
	{
		$checkoutData = $this->getItemsFromCart($cart, $transaction);

		$sessionParams = [
			'line_items'           => $checkoutData['line_items'],
			'mode'                 => 'payment',
			'success_url'          => Uri::base() . 'index.php?option=com_emundus&controller=webhook&task=updatePaymentTransaction&sync_id=' . $this->sync_id . '&transaction_ref=' . $transaction->getExternalReference(),
			'cancel_url'           => Uri::base() . 'index.php?option=com_emundus&controller=webhook&task=updatePaymentTransaction&sync_id=' . $this->sync_id . '&transaction_ref=' . $transaction->getExternalReference(),
			'client_reference_id'  => $transaction->getExternalReference(),
			'payment_method_types' => ['card'],
			'customer_email'       => $cart->getCustomer()->getEmail()
		];

		if (!empty($checkoutData['discounts']))
		{
			$stripeCoupons = [];
			foreach ($checkoutData['discounts'] as $couponParams)
			{
				try
				{
					$coupon = $this->client->coupons->create($couponParams);
					$stripeCoupons[] = ['coupon' => $coupon->id];
				}
				catch (\Exception $e)
				{
					Log::add('Failed to create Stripe coupon: ' . $e->getMessage(), Log::ERROR, 'com_emundus.stripe');
					throw new \Exception('Failed to create Stripe discount coupon');
				}
			}
			$sessionParams['discounts'] = $stripeCoupons;
		}

		try {
			$session = $this->client->checkout->sessions->create($sessionParams);
		} catch (\Exception $e) {
			Log::add('Stripe checkout session creation failed: ' . $e->getMessage(), Log::ERROR, 'com_emundus.stripe');
			throw new \Exception('Failed to create Stripe checkout session');
		}

		return $session;
	}

	/**
	 * @param   CartEntity         $cart
	 * @param   TransactionEntity  $transaction
	 *
	 * @return array{line_items: array, discounts: array}
	 * @throws \Exception
	 */
	private function getItemsFromCart(CartEntity $cart, TransactionEntity $transaction): array
	{
		$factory = new StripeItemFactory();

		return $factory->buildCheckoutData($cart, $transaction->getCurrency());
	}

	public function verifySignature(string $payload): bool
	{
		$verified = false;

		\Stripe\Stripe::setApiKey($this->config['authentication']['client_secret'] ?? '');

		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$endpoint_secret = $this->config['authentication']['webhook_secret'] ?? '';

		if ($endpoint_secret) {
			try {
				\Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$endpoint_secret
				);

				$verified = true;
			} catch (\UnexpectedValueException $e)
			{
				Log::add('Invalid payload ' . $e->getMessage(), Log::ERROR, 'com_emundus.stripe');
			}
			catch(\Stripe\Exception\SignatureVerificationException $e) {
				Log::add('Invalid signature ' . $e->getMessage(), Log::ERROR, 'com_emundus.stripe');
			}
		}

		return $verified;
	}

	public function updateTransactionFromCallback(array $data, int $transaction_id, int $user_id): bool
	{
		$updated = false;

		if (!empty($transaction_id) && !empty($data))
		{
			$transaction_repository = new TransactionRepository();
			$transaction            = $transaction_repository->getById($transaction_id);

			Log::add('Transaction ref: ' . $transaction->getExternalReference(), Log::DEBUG, 'com_emundus.stripe');
			Log::add('Stripe client ref id: ' . $data['data']['object']['client_reference_id'], Log::DEBUG, 'com_emundus.stripe');

			if (!empty($transaction) && $transaction->getExternalReference() === $data['data']['object']['client_reference_id']) {
				if (!in_array($data['type'], self::HANDLED_EVENTS, true)) {
					Log::add('Received unhandled Stripe event type: ' . $data['type'], Log::WARNING, 'com_emundus.stripe');
					return false;
				}

				Log::add('Processing Stripe event type: ' . $data['type'], Log::INFO, 'com_emundus.stripe');

				switch ($data['type']) {
					case 'checkout.session.completed':
					case 'payment_intent.succeeded':
					case 'payment_intent.payment_failed':
						Log::add('Updating transaction status based on Stripe event: ' . $data['data']['object']['payment_status'], Log::INFO, 'com_emundus.stripe');

						$status = match ($data['data']['object']['payment_status'])
						{
							'paid' => TransactionStatus::CONFIRMED,
							'failed' => TransactionStatus::FAILED,
							default => TransactionStatus::CANCELLED,
						};

						Log::add('Setting transaction status to: ' . $status->value, Log::INFO, 'com_emundus.stripe');

						$transaction->setStatus($status);
						$transaction->setUpdatedAt(date('Y-m-d H:i:s'));
						$transaction->setUpdatedBy($user_id);
						try {
							$updated = $transaction_repository->saveTransaction($transaction, $user_id);
							if ($updated) {
								Log::add('Transaction ' . $transaction->getExternalReference() . ' updated to status ' . $status->value, Log::INFO, 'com_emundus.stripe');
							} else {
								Log::add('Failed to update transaction ' . $transaction->getExternalReference(), Log::ERROR, 'com_emundus.stripe');
							}
						} catch (\Exception $e) {
							Log::add('Error updating transaction: ' . $e->getMessage(), Log::ERROR, 'com_emundus.stripe');
						}

						break;
					default:
						Log::add('Unhandled Stripe event type: ' . $data['type'], Log::WARNING, 'com_emundus.stripe');
						return false;
				}
			} else {
				Log::add('Transaction external reference does not match callback data', Log::ERROR, 'com_emundus.stripe');
			}
		}

		return $updated;
	}
}
