<?php

namespace Tchooz\Repositories\Payment;

use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\DiscountEntity;
use Tchooz\Entities\Payment\DiscountType;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Repositories\Payment\DiscountRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\TransactionRepository;
use \Tchooz\Repositories\Payment\CurrencyRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Synchronizers\Payment\Sogecommerce;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\GenericEvent;

require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');

class CartRepository
{
	private DatabaseDriver $db;

	private PaymentRepository $payment_repository;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.cart.php'], Log::ALL, ['com_emundus.repository.cart']);

		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->payment_repository = new PaymentRepository();
	}

	private function getCartPaymentStep(CartEntity $cart): ?PaymentStepEntity
	{
		$payment_step = null;

		if (!empty($cart->getFnum())) {
			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new \EmundusModelWorkflow();
			$step = $m_workflow->getPaymentStepFromFnum($cart->getFnum());

			if (!empty($step)) {
				$payment_repository = new PaymentRepository();
				$payment_step = $payment_repository->getPaymentStepById($step->id);
			}
		}

		return $payment_step;
	}

	public function getCampaignIdFromCart(CartEntity $cart): int
	{
		$campaign_id = 0;

		if (!empty($cart) && !empty($cart->getFnum()))
		{
			$query = $this->db->createQuery();
			$query->clear()
				->select('campaign_id')
				->from('#__emundus_campaign_candidature')
				->where('fnum = ' . $this->db->quote($cart->getFnum()));

			$this->db->setQuery($query);
			$campaign_id = $this->db->loadResult();
		}

		return $campaign_id;
	}

	public function createCart(string $fnum, int $step_id): int
	{
		$cart_id = 0;

		$cart = new CartEntity(0);
		$cart->setFnum($fnum);
		$cart->setPublished(1);
		$file_campaign_id = $this->getCampaignIdFromCart($cart);

		if (!empty($step_id)) {
			$payment_repository = new PaymentRepository();
			$payment_step = $payment_repository->getPaymentStepById($step_id);
			if (!empty($payment_step)) {
				$cart->setPaymentMethods($payment_step->getPaymentMethods());
				$cart->setSelectedPaymentMethod($payment_step->getPaymentMethods()[0]);
				$cart->setPaymentStep($payment_step);

				foreach ($payment_step->getProducts() as $product) {
					if ((empty($product->getCampaigns()) || in_array($file_campaign_id, $product->getCampaigns())))
					{
						$cart->addAvailableProduct($product);
						if ($product->getMandatory()) {
							$cart->addProduct($product);
						}
					}
				}

				switch($payment_step->getAdvanceType()) {
					case 1: // free choice
						$cart->setAllowedToPayAdvance(true);
						$cart->setAdvanceAmount($payment_step->getAdvanceAmount());
						$cart->setAdvanceAmountType($payment_step->getAdvanceAmountType());
						break;
					case 2: // forced to pay advance
						$cart->setAllowedToPayAdvance(true);
						$cart->setAdvanceAmount($payment_step->getAdvanceAmount());
						$cart->setAdvanceAmountType($payment_step->getAdvanceAmountType());

						if ($cart->getPayAdvance() == 0)
						{
							$cart->setPayAdvance(1);
						}
						break;
					case 0: // forbidd pay advance
					default:
						$cart->setAllowedToPayAdvance(false);

						if ($cart->getPayAdvance() == 1) {
							$cart->setPayAdvance(0);
						}
						break;
				}
			}

			$query = $this->db->createQuery();
			$query->select($this->db->quoteName(['applicant_id']))
				->from($this->db->quoteName('jos_emundus_campaign_candidature'))
				->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$user_id = $this->db->loadResult();
			$contact_repository = new ContactRepository($this->db);
			$customer = $contact_repository->getByUserId($user_id);

			if (empty($customer)) {
				$customer = $this->createCartUser($user_id);
			}

			$cart->setCustomer($customer);
			$cart->calculateTotal();
			$saved = $this->saveCart($cart);

			if ($saved) {
				$cart_id = $cart->getId();
			}
		}

		return $cart_id;
	}

	public function fillCart(CartEntity $cart_entity, int $step_id = 0): ?CartEntity
	{
		$payment_repository = new PaymentRepository();
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->db->quoteName('jos_emundus_cart'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($cart_entity->getId()));

		try {
			$this->db->setQuery($query);
			$cart = $this->db->loadObject();

			if ($cart) {
				$cart_entity->setPublished($cart->published);
				$cart_entity->setFnum($cart->fnum);
				$cart_entity->setPayAdvance($cart->pay_advance);

				if (!empty($cart->step_id)) {
					$cart_entity->setStepId($cart->step_id);
				} else {
					$cart_entity->setStepId($step_id);
				}

				// Load Currency
				$addon = $payment_repository->getAddon();
				$payment_configuration = $addon->getConfiguration();

				if (!empty($payment_configuration['currency_id']))
				{
					$currency_repository = new CurrencyRepository();
					$currency = $currency_repository->getCurrencyById($payment_configuration['currency_id']);

					if (!empty($currency)) {
						$cart_entity->setCurrency($currency);
					}
				}

				// Load products
				$query = $this->db->createQuery();
				$query->select($this->db->quoteName(['product_id']))
					->from($this->db->quoteName('jos_emundus_cart_product'))
					->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart_entity->getId()));
				$this->db->setQuery($query);
				$product_ids = $this->db->loadColumn();

				if ($product_ids) {
					foreach ($product_ids as $product_id) {
						try {
							$product_entity = new ProductEntity($product_id);

							if (!empty($product_entity->getId())) {
								$cart_entity->addProduct($product_entity);
							}
						} catch (\Exception $e) {
							Log::add('Error loading cart product : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.cart');
						}
					}
				}
				$contact_repository = new ContactRepository($this->db);
				$contact_entity = $contact_repository->getByUserId($cart->user_id);

				if (empty($contact_entity)) {
					$contact_entity = $this->createCartUser($cart->user_id);
				}

				$cart_entity->setCustomer($contact_entity);

				if (!empty($cart->payment_method_id)) {
					$cart_entity->setSelectedPaymentMethod(new PaymentMethodEntity($cart->payment_method_id));

					if ($cart_entity->getSelectedPaymentMethod()->getName() === 'sepa') {
						try {
							$cart_entity->setNumberInstallmentDebit($cart->number_installment_debit);
						} catch (\Exception $e) {
							// do nothing, stays at one
						}
						$cart_entity->setInstallmentMonthday($cart->installment_monthday);
					}
				}

				$query->clear()
					->select('*')
					->from($this->db->quoteName('jos_emundus_price_alteration'))
					->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart_entity->getId()));
				$this->db->setQuery($query);
				$price_alterations = $this->db->loadObjectList();

				if (!empty($price_alterations)) {
					$discount_repository = new DiscountRepository();

					foreach($price_alterations as $key => $alteration) {
						$alteration->amount = (float)$alteration->amount;
						$price_alterations[$key] = new AlterationEntity($alteration->id, $cart_entity->getId(), null, null, $alteration->description, $alteration->amount, AlterationType::from($alteration->type), $alteration->created_by, new \DateTime($alteration->created_at), $alteration->updated_by, new \DateTime($alteration->updated_at));

						if (!empty($alteration->discount_id)) {
							$discount = $discount_repository->getDiscountById($alteration->discount_id);
							$price_alterations[$key]->setDiscount($discount);
						}

						if (!empty($alteration->product_id)) {
							$product = new ProductEntity($alteration->product_id);
							$price_alterations[$key]->setProduct($product);
						}
					}

					$cart_entity->setPriceAlterations($price_alterations);
				}
			} else {
				throw new \Exception('Cart not found');
			}
		} catch (\Exception $e) {
			Log::add('Error loading cart: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.cart');
		}

		if (!empty($step_id)) {
			$payment_step = $payment_repository->getPaymentStepById($step_id);

			if (!empty($payment_step)) {
				$file_campaign_id = $this->getCampaignIdFromCart($cart_entity);
				$cart_entity->setPaymentStep($payment_step);
				$cart_entity->setPaymentMethods($payment_step->getPaymentMethods());

				if ($payment_step->getAdjustBalance() === 1 && !empty($payment_step->getAdjustBalanceStepId())) {
					$this->loadAdjustBalanceStep($cart_entity, $payment_step->getAdjustBalanceStepId());
				}

				foreach ($payment_step->getProducts() as $product) {
					if ((empty($product->getCampaigns()) || in_array($file_campaign_id, $product->getCampaigns()))) {
						$cart_entity->addAvailableProduct($product);
						if ($product->getMandatory()) {
							$cart_entity->addProduct($product);
						}
					}
				}

				switch($payment_step->getAdvanceType()) {
					case 1: // free choice
						$cart_entity->setAllowedToPayAdvance(true);
						$cart_entity->setAdvanceAmount($payment_step->getAdvanceAmount());
						$cart_entity->setAdvanceAmountType($payment_step->getAdvanceAmountType());
						break;
					case 2: // forced to pay advance
						$cart_entity->setAllowedToPayAdvance(true);
						$cart_entity->setAdvanceAmount($payment_step->getAdvanceAmount());
						$cart_entity->setAdvanceAmountType($payment_step->getAdvanceAmountType());

						if ($cart_entity->getPayAdvance() == 0)
						{
							$cart_entity->setPayAdvance(1);
							$this->saveCart($cart_entity);
						}
						break;
					case 0: // forbidd pay advance
					default:
						$cart_entity->setAllowedToPayAdvance(false);

						if ($cart_entity->getPayAdvance() == 1) {
							$cart_entity->setPayAdvance(0);
							$this->saveCart($cart_entity);
						}
						break;
				}
			}
		}

		$cart_entity->calculateTotal();

		return $cart_entity;
	}

	public function loadAdjustBalanceStep(CartEntity $cart_entity, int $adjust_balance_step_id): CartEntity
	{
		$already_added_adjust_balance = false;
		foreach ($cart_entity->getPriceAlterations() as $alteration) {
			if ($alteration->getType() === AlterationType::ADJUST_BALANCE) {
				$already_added_adjust_balance = true;
			}
		}

		if ($already_added_adjust_balance) {
			return $cart_entity; // already added, no need to load again
		}

		$transaction_repository = new TransactionRepository();
		$transaction = $transaction_repository->getTransactionByCartAndStep($cart_entity, $adjust_balance_step_id, TransactionStatus::CONFIRMED);

		if (!empty($transaction))
		{
			$data = $transaction->getData();
			$data = json_decode($data, true);

			$payment_step = $cart_entity->getPaymentStep();
			$step_mandatory_products = $payment_step->getProducts();
			foreach ($data['products'] as $product)
			{
				$product_entity = new ProductEntity($product['id']);
				$product_entity->setMandatory(1);
				$cart_entity->addAvailableProduct($product_entity);
				$cart_entity->addProduct($product_entity);
				$step_mandatory_products[] = $product_entity;
			}
			$payment_step->setProducts($step_mandatory_products);

			if (!empty($data['alterations'])) {
				$discount_repository = new DiscountRepository();
				foreach ($data['alterations'] as $alteration) {
					if (!empty($alteration['product_id'])) {
						$product = new ProductEntity($alteration['product_id']);
					} else {
						$product = null;
					}

					if (!empty($alteration['discount_id'])) {
						$discount = $discount_repository->getDiscountById($alteration['discount_id']);
					} else {
						$discount = null;
					}

					$alteration_entity = new AlterationEntity($alteration['id'], $cart_entity->getId(), $product, $discount, $alteration['description'], $alteration['amount'], AlterationType::from($alteration['type']));
					$cart_entity->addAlteration($alteration_entity);
				}
			}

			// create an alteration for the advance amount
			if (!empty($transaction->getAmount())) {
				// add it only if not already added
				$already_paid_alteration = new AlterationEntity(
					0,
					$cart_entity->getId(),
					null,
					null,
					Text::_('COM_EMUNDUS_CART_ADJUST_BALANCE_ADVANCE'),
					-$transaction->getAmount(),
					AlterationType::ADJUST_BALANCE,
					$transaction->getCreatedBy()
				);

				$added = $cart_entity->addAlteration($already_paid_alteration);

				if (!$added)
				{
					throw new \Exception('Error adding adjust balance alteration');
				}
			}

			$cart_entity->setPaymentStep($payment_step);
		}

		return $cart_entity;
	}

	private function createCartUser(int $user_id): ContactEntity
	{
		$contact_repository = new ContactRepository($this->db);
		$contact_entity = $contact_repository->getByUserId($user_id);

		if (empty($contact_entity)) {
			$query = $this->db->createQuery();

			$query->select($this->db->quoteName(['jeu.user_id', 'jeu.firstname', 'jeu.lastname', 'ju.email']))
				->from($this->db->quoteName('jos_emundus_users', 'jeu'))
				->leftJoin($this->db->quoteName('jos_users', 'ju') . ' ON ' . $this->db->quoteName('jeu.user_id') . ' = ' . $this->db->quoteName('ju.id'))
				->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));

			$this->db->setQuery($query);
			$contact = $this->db->loadObject();

			if ($contact) {
				$contact_entity = new ContactEntity($contact->email, $contact->lastname, $contact->firstname, null, 0, $user_id);
				$contact_repository->flush($contact_entity);
			} else {
				throw new \Exception('Customer not found');
			}
		}

		return $contact_entity;
	}

	public function saveCart(CartEntity $cart_entity, $user_id = 0): bool
	{
		$saved = false;

		$query = $this->db->createQuery();

		if (empty($cart_entity->getId())) {
			$step_value = 'NULL';

			if (!empty($cart_entity->getStepId())) {
				$step_value = $this->db->quote($cart_entity->getStepId());
			}

			$query->insert($this->db->quoteName('jos_emundus_cart'))
				->columns($this->db->quoteName(['fnum', 'step_id', 'total', 'user_id', 'published', 'payment_method_id', 'created_at', 'created_by', 'pay_advance', 'number_installment_debit', 'installment_monthday']))
				->values($this->db->quote($cart_entity->getFnum()) . ', ' . $step_value . ', ' . $this->db->quote($cart_entity->getTotal()) . ', ' . $this->db->quote($cart_entity->getCustomer()->getUserId()) . ', ' . $this->db->quote($cart_entity->getPublished()) . ', ' . $this->db->quote($cart_entity->getSelectedPaymentMethod()->getId()) . ', ' . $this->db->quote(date('Y-m-d H:i:s')) . ', ' . $this->db->quote($user_id). ', '. $this->db->quote($cart_entity->getPayAdvance()) . ', ' . $this->db->quote($cart_entity->getNumberInstallmentDebit()) . ', ' . $this->db->quote($cart_entity->getInstallmentMonthday()));

			$this->db->setQuery($query);
			$saved = $this->db->execute();

			if ($saved) {
				$cart_entity->setId($this->db->insertid());

				// save products
				foreach ($cart_entity->getProducts() as $product) {
					$query->clear()
						->insert($this->db->quoteName('jos_emundus_cart_product'))
						->columns($this->db->quoteName(['cart_id', 'product_id']))
						->values($this->db->quote($cart_entity->getId()) . ', ' . $this->db->quote($product->getId()));
					$this->db->setQuery($query);
					$this->db->execute();
				}

				foreach ($cart_entity->getPriceAlterations() as $alteration)
				{
					$product_id = null;
					if (empty($alteration->getProduct()))
					{
						$product_id = 'NULL';
					} else {
						$product_id = $this->db->quote($alteration->getProduct()->getId());
					}

					if (empty($alteration->getDiscount()))
					{
						$discount_id = 'NULL';
					} else {
						$discount_id = $this->db->quote($alteration->getDiscount()->getId());
					}

					$created_at = empty($alteration->getCreatedAt()) ? date('Y-m-d H:i:s') : $alteration->getCreatedAt()->format('Y-m-d H:i:s');
					$created_by = empty($alteration->getCreatedBy()) ? $user_id : $alteration->getCreatedBy();

					$query->clear()
						->insert($this->db->quoteName('jos_emundus_price_alteration'))
						->columns($this->db->quoteName(['cart_id', 'discount_id', 'amount', 'description', 'type', 'created_at', 'created_by', 'product_id', 'updated_at', 'updated_by']))
						->values($this->db->quote($cart_entity->getId()) . ', ' . $discount_id . ', ' . $this->db->quote($alteration->getAmount()) . ', ' . $this->db->quote($alteration->getDescription()) . ', ' . $this->db->quote($alteration->getType()->value) . ', ' . $this->db->quote($created_at) . ', ' . $this->db->quote($created_by) . ', ' . $product_id . ', ' . $this->db->quote(date('Y-m-d H:i:s')) . ', ' . $this->db->quote($user_id));

					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
		}
		else
		{
			$query->update($this->db->quoteName('jos_emundus_cart'))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($cart_entity->published))
				->set($this->db->quoteName('total') . ' = ' . $this->db->quote($cart_entity->getTotal()))
				->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($cart_entity->getCustomer()->getUserId()))
				->set($this->db->quoteName('fnum') . ' = ' . $this->db->quote($cart_entity->fnum))
				->set($this->db->quoteName('pay_advance') . ' = ' . $this->db->quote($cart_entity->getPayAdvance()))
				->set($this->db->quoteName('number_installment_debit') . ' = ' . $this->db->quote($cart_entity->getNumberInstallmentDebit()))
				->set($this->db->quoteName('installment_monthday') . ' = ' . $this->db->quote($cart_entity->getInstallmentMonthday()))
				->set($this->db->quoteName('updated_at') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
				->set($this->db->quoteName('updated_by') . ' = ' . $this->db->quote($user_id))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($cart_entity->getId()));

			if (!empty($cart_entity->getStepId())) {
				$query->set($this->db->quoteName('step_id') . ' = ' . $this->db->quote($cart_entity->getStepId()));
			} else {
				$query->set($this->db->quoteName('step_id') . ' = NULL');
			}

			if (!empty($cart_entity->getSelectedPaymentMethod())) {
				$query->set($this->db->quoteName('payment_method_id') . ' = ' . $this->db->quote($cart_entity->getSelectedPaymentMethod()->getId()));
			} else {
				$query->set($this->db->quoteName('payment_method_id') . ' = NULL');
			}

			$this->db->setQuery($query);
			$saved = $this->db->execute();

			if ($saved) {
				// reset products
				$query->clear()
					->delete($this->db->quoteName('jos_emundus_cart_product'))
					->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart_entity->getId()));

				$this->db->setQuery($query);
				$this->db->execute();

				// save products
				foreach ($cart_entity->getProducts() as $product) {
					$query->clear()
						->insert($this->db->quoteName('jos_emundus_cart_product'))
						->columns($this->db->quoteName(['cart_id', 'product_id']))
						->values($this->db->quote($cart_entity->getId()) . ', ' . $this->db->quote($product->getId()));
					$this->db->setQuery($query);
					$this->db->execute();
				}

				// reset price alterations
				$query->clear()
					->delete($this->db->quoteName('jos_emundus_price_alteration'))
					->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart_entity->getId()));
				$this->db->setQuery($query);
				$this->db->execute();

				// save price alterations
				foreach ($cart_entity->getPriceAlterations() as $alteration)
				{
					if (empty($alteration->getProduct())) {
						$product_id = 'NULL';
					} else {
						$product_id = $this->db->quote($alteration->getProduct()->getId());
					}

					if (empty($alteration->getDiscount())) {
						$discount_id = 'NULL';
					} else {
						$discount_id = $this->db->quote($alteration->getDiscount()->getId());
					}

					$created_at = !empty($alteration->getCreatedAt()) ? $alteration->getCreatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
					$created_by = !empty($alteration->getCreatedBy()) ? $alteration->getCreatedBy() : $user_id;
					$updated_at = date('Y-m-d H:i:s');
					$updated_by = $user_id;

					$query->clear()
						->insert($this->db->quoteName('jos_emundus_price_alteration'))
						->columns($this->db->quoteName(['cart_id', 'discount_id', 'amount', 'description', 'type', 'created_at', 'created_by', 'product_id', 'updated_at', 'updated_by']))
						->values($this->db->quote($cart_entity->getId()) . ', ' . $discount_id . ', ' . $this->db->quote($alteration->getAmount()) . ', ' . $this->db->quote($alteration->getDescription()) . ', ' . $this->db->quote($alteration->getType()->value) . ', ' . $this->db->quote($created_at) . ', ' . $this->db->quote($created_by) . ', ' . $product_id . ', ' . $this->db->quote($updated_at) . ', ' . $this->db->quote($updated_by));

					$this->db->setQuery($query);
					$added = $this->db->execute();
				}
			}
		}

		if ($saved) {
			PluginHelper::importPlugin('emundus');
			$dispatcher = Factory::getApplication()->getDispatcher();
			$onAfterEmundusCartUpdate = new GenericEvent('onCallEventHandler', ['onAfterEmundusCartUpdate', ['fnum' => $cart_entity->getFnum(), 'cart' => $cart_entity]]);
			$dispatcher->dispatch('onCallEventHandler', $onAfterEmundusCartUpdate);
		}

		return $saved;
	}

	public function getCartByFnum(string $fnum, int $step_id, int $user_id = 0): ?CartEntity
	{
		$cart = null;

		if (empty($user_id)) {
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		if (!empty($fnum)) {
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName('id') . ', ' . $this->db->quoteName('step_id'))
				->from($this->db->quoteName('jos_emundus_cart'))
				->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$cart_data = $this->db->loadAssoc();

			if (!empty($cart_data['id'])) {
				$cart = new CartEntity($cart_data['id']);
				$cart = $this->fillCart($cart, $step_id);

				if (empty($cart_data['step_id'])) {
					$cart->setStepId($step_id);
					$this->saveCart($cart);
				} else if ($cart_data['step_id'] != $step_id) {
					$this->resetCart($cart, $user_id);
					$cart->setStepId($step_id);
					$this->saveCart($cart);
					$cart = $this->fillCart($cart, $step_id);
				}
			} else {
				$cart_id = $this->createCart($fnum, $step_id);
				$cart = $this->getCartById($cart_id);
			}
		}

		return $cart;
	}

	public function getCartById(int $cart_id, int $step_id = 0, int $user_id = 0): ?CartEntity
	{
		$cart = null;

		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		if (!empty($cart_id))
		{
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName('fnum') . ', ' . $this->db->quoteName('step_id'))
				->from($this->db->quoteName('jos_emundus_cart'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($cart_id));

			$this->db->setQuery($query);
			$cart_data = $this->db->loadAssoc();

			if (!empty($cart_data['fnum'])) {
				if (empty($step_id)) {
					if (!class_exists('EmundusModelWorkflow')) {
						require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
					}
					$m_workflow = new \EmundusModelWorkflow();
					$step = $m_workflow->getPaymentStepFromFnum($cart_data['fnum']);
					$step_id = $step->id;
				}

				$cart = new CartEntity($cart_id);
				if (!empty($step_id)) {
					$cart = $this->fillCart($cart, $step_id);

					if (empty($cart_data['step_id'])) {
						$cart->setStepId($step_id);
						$this->saveCart($cart);
					} else if ($cart_data['step_id'] != $step_id) {
						$this->resetCart($cart, $user_id);
						$cart->setStepId($step_id);
						$this->saveCart($cart);
						$cart = $this->fillCart($cart, $step_id);
					}
				} else {
					$cart = $this->fillCart($cart);
				}
			}
		}

		return $cart;
	}

	public function addProduct(CartEntity $cart, int $product_id, int $user_id): bool
	{
		$added = false;

		$product = new ProductEntity($product_id);

		if (!empty($product->getId())) {
			$query = $this->db->createQuery();
			$query->insert($this->db->quoteName('jos_emundus_cart_product'))
				->columns($this->db->quoteName(['cart_id', 'product_id', 'updated_by', 'updated_at']))
				->values($this->db->quote($cart->getId()) . ', ' . $this->db->quote($product_id) . ', ' .  $this->db->quote($user_id) . ', ' . $this->db->quote(date('Y-m-d H:i:s')));
			$this->db->setQuery($query);
			$added = $this->db->execute();

			if ($added) {
				$cart->addProduct($product);

				$details = ['updated' => [['element' => $product->getLabel() . ' ' . $product->getDisplayedPrice()]]];
				\EmundusModelLogs::log($user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'u', 'COM_EMUNDUS_ADD_PRODUCT_TO_CART', json_encode($details));
			}
		}

		return $added;
	}

	public function removeProduct(CartEntity $cart, int $product_id, int $user_id): bool
	{
		$removed = false;

		foreach ($cart->getProducts() as $product) {
			if ($product->getId() == $product_id) {
				$query = $this->db->createQuery();
				$query->delete($this->db->quoteName('jos_emundus_cart_product'))
					->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart->getId()))
					->where($this->db->quoteName('product_id') . ' = ' . $this->db->quote($product_id));
				$this->db->setQuery($query);
				$removed = $this->db->execute();

				if ($removed) {
					$cart->removeProduct($product);

					$details = ['deleted' => [['element' => $product->getLabel() . ' ' . $product->getDisplayedPrice()]]];
					\EmundusModelLogs::log($user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'd', 'COM_EMUNDUS_REMOVE_PRODUCT_FROM_CART', json_encode($details));
				}
				break;
			}
		}

		return $removed;
	}

	/**
	 * @param   CartEntity        $cart
	 * @param   AlterationEntity  $alteration
	 * @param   int               $user_id
	 *
	 * @return bool
	 */
	public function addAlteration(CartEntity $cart, AlterationEntity $alteration, int $user_id): bool
	{
		$added = $cart->addAlteration($alteration);

		if ($added) {
			$added = $this->saveCart($cart, $user_id);

			if ($added)
			{
				$details = ['updated' => [['element' => $alteration->getDescription()]]];
				\EmundusModelLogs::log($user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'u', 'COM_EMUNDUS_ADD_ALTERATION_TO_CART', json_encode($details));
			}
		}

		return $added;
	}

	public function removeAlteration(CartEntity $cart, AlterationEntity $alteration, int $user_id): bool
	{
		$removed = false;

		foreach ($cart->getPriceAlterations() as $key => $price_alteration) {
			if ($price_alteration->getId() == $alteration->getId()) {
				$cart->removeAlteration($alteration);
				$removed = $this->saveCart($cart, $user_id);

				if ($removed) {
					$details = ['deleted' => [['element' => $alteration->getDescription()]]];
					\EmundusModelLogs::log($user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'd', 'COM_EMUNDUS_REMOVE_ALTERATION_FROM_CART', json_encode(['alteration_id' => $alteration->getId()]));
				}
			}
		}

		return $removed;

	}

	public function updateProductPriceForCart(CartEntity $cart, int $product_id, float $new_price): bool
	{
		$updated = false;

		foreach ($cart->getProducts() as $product) {
			if ($product->getId() == $product_id) {
				$product->price = $new_price;

				$query = $this->db->createQuery();
				$query->update($this->db->quoteName('jos_emundus_cart_product'))
					->set($this->db->quoteName('price') . ' = ' . $this->db->quote($new_price))
					->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart->getId()))
					->where($this->db->quoteName('product_id') . ' = ' . $this->db->quote($product_id));
				$this->db->setQuery($query);
				$updated = $this->db->execute();

				if ($updated) {
					$cart->calculateTotal();
				}

				break;
			}
		}

		return $updated;
	}

	public function updateInstallmentDebitNumber(CartEntity $cart, int $number, int $user_id): bool
	{
		$updated = false;

		if ($cart->getId() && $number > 0)
		{
			$old_number = $cart->getNumberInstallmentDebit();

			$cart->setNumberInstallmentDebit($number);
			$updated = $this->saveCart($cart);

			if ($updated) {
				$details = ['updated' => [['old' => $old_number, 'new' => $number]]];
				\EmundusModelLogs::log($user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'u', 'COM_EMUNDUS_UPDATE_CART_INSTALLMENT_NUMBER', json_encode($details));
			}
		}

		return $updated;
	}

	public function updateInstallmentMonthday(CartEntity $cart, int $month, int $user_id): bool
	{
		$updated = false;

		if ($cart->getId() && $month > 0)
		{
			$old_month = $cart->getInstallmentMonthday();

			$cart->setInstallmentMonthday($month);
			$updated = $this->saveCart($cart);

			if ($updated) {
				$details = ['updated' => [['old' => $old_month, 'new' => $month]]];
				\EmundusModelLogs::log($user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'u', 'COM_EMUNDUS_UPDATE_CART_INSTALLMENT_MONTHDAY', json_encode($details));
			}
		}

		return $updated;
	}

	public function createTransaction(CartEntity $cart): TransactionEntity
	{
		$transaction = new TransactionEntity();
		$transaction->setCartId($cart->getId());

		if ($cart->getPayAdvance() == 1) {
			$transaction->setAmount($cart->getTotalAdvance());
		} else {
			$transaction->setAmount($cart->getTotal());
		}

		$transaction->setCurrency($cart->getCurrency()); // Assuming all products have the same currency
		$transaction->setPaymentMethod($cart->getSelectedPaymentMethod());
		$transaction->setStatus(TransactionStatus::INITIATED);
		$payment_step = $this->getCartPaymentStep($cart);
		$transaction->setSynchronizerId($payment_step->getSynchronizerId());
		$transaction->generateExternalReference();
		$transaction->setNumberInstallmentDebit($cart->getNumberInstallmentDebit());
		$transaction->setStepId($cart->getPaymentStep()->getId());
		$transaction->setFnum($cart->getFnum());

		$data = ['products' => [], 'alterations' => []];
		foreach ($cart->getProducts() as $product) {
			$data['products'][] = $product->serialize();
		}
		foreach ($cart->getPriceAlterations() as $alteration) {
			$data['alterations'][] = $alteration->serialize();
		}

		if ($transaction->getPaymentMethod()->getName() === 'sepa') {
			$data['installment'] = [
				'number_installment_debit' => $transaction->getNumberInstallmentDebit(),
				'installment_monthday' => $payment_step->getInstallmentMonthday() > 0 ? $payment_step->getInstallmentMonthday() : $cart->getInstallmentMonthday(),
				'installment_effect_date' => !empty($payment_step->getInstallmentEffectDate()) ? $payment_step->getInstallmentEffectDate() : date('Y-m-d'),
				'amounts_by_iteration' => $this->payment_repository::generateAmountsByIterations($transaction->getAmount(), $transaction->getNumberInstallmentDebit()),
			];
		}

		$transaction->setData(json_encode($data));

		return $transaction;
	}

	/**
	 * @param   CartEntity  $cart
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function verifyCart(CartEntity $cart, int $user_id): bool
	{
		$selected_method = $cart->getSelectedPaymentMethod();

		if (empty($selected_method)) {
			throw new \Exception(Text::_('COM_EMUNDUS_CART_NO_PAYMENT_METHOD_SELECTED'));
		}

		$selected_method_authorized = false;
		foreach ($cart->getPaymentMethods() as $payment_method) {
			if ($payment_method->getId() == $selected_method->getId()) {
				$selected_method_authorized = true;
			}
		}

		if (!$selected_method_authorized) {
			throw new \Exception(Text::_('COM_EMUNDUS_CART_PAYMENT_METHOD_NOT_AUTHORIZED'));
		}

		if ($cart->getSelectedPaymentMethod()->name === 'sepa') {
			if (!$cart->numberInstallmentRespectRules($cart->getNumberInstallmentDebit())) {
				throw new \Exception(Text::_('COM_EMUNDUS_CART_INSTALLMENT_DEBIT_NUMBER_NOT_RESPECT_RULES'));
			}
		}

		if (empty($cart->getProducts())) {
			throw new \Exception(Text::_('COM_EMUNDUS_CART_EMPTY'));
		}

		return true;
	}

	public function checkoutCart(CartEntity $cart, int $current_user_id): array
	{
		$data = [];
		$app = Factory::getApplication();

		try {
			$transaction_repository = new TransactionRepository();
			$transaction = $this->createTransaction($cart);
			$transaction_repository->saveTransaction($transaction, $current_user_id);
		} catch (\Exception $e) {
			Log::add('Error creating transaction: ' . $e->getMessage() . '. Cart ID ' . $cart->getId() . ', fnum ' . $cart->getFnum(), Log::ERROR, 'com_emundus.repository.cart');
			throw new \Exception(Text::_('COM_EMUNDUS_ERROR_CREATING_TRANSACTION'));
		}

		\EmundusModelLogs::log($current_user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'u', 'COM_EMUNDUS_UPDATE_CHECKOUT_CART', json_encode(['cart_id' => $cart->getId(), 'transaction_id' => $transaction->getId()]));

		if (!class_exists('EmundusHelperMenu')) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/menu.php');
		}

		if ($transaction->getAmount() === 0.0)
		{
			$transaction->setStatus(TransactionStatus::CONFIRMED);
			$saved = $transaction_repository->saveTransaction($transaction, $current_user_id);

			if ($saved) {
				$data = ['transaction_confirmed' => true, 'message' => Text::_('COM_EMUNDUS_TRANSACTION_WAITING_FOR_VALIDATION'), 'redirect' => \EmundusHelperMenu::getHomepageLink()];
			} else {
				Log::add('Error confirming empty transaction : Cart ID ' . $cart->getId() . ', fnum ' . $cart->getFnum(), Log::ERROR, 'com_emundus.repository.cart');
			}
		}
		else if (!empty($transaction->getSynchronizerId()))
		{
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName(['type']))
				->from($this->db->quoteName('jos_emundus_setup_sync'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($transaction->getSynchronizerId()));

			$this->db->setQuery($query);
			$sync_type = $this->db->loadResult();

			$synchronizer = null;
			switch ($sync_type) {
				case 'sogecommerce':
					$synchronizer = new Sogecommerce();
					break;
				default:
					break;
			}

			if (!empty($synchronizer)) {
				if ($cart->getSelectedPaymentMethod()->getName() === 'cheque' || $cart->getSelectedPaymentMethod()->getName() === 'transfer') {
					// pass the transaction as waiting, no synchronizer, manual payment
					$transaction->setStatus(TransactionStatus::WAITING);
					$saved = $transaction_repository->saveTransaction($transaction, $current_user_id);

					if ($saved) {
						$data = [
							'transaction_confirmed' => true,
							'status' => $transaction->getStatus()->value,
							'message' => Text::_('COM_EMUNDUS_TRANSACTION_WAITING_FOR_VALIDATION'),
							'redirect' => \EmundusHelperMenu::getHomepageLink()
						];
					} else {
						Log::add('Error saving transaction : Cart ID ' . $cart->getId() . ', fnum ' . $cart->getFnum(), Log::ERROR, 'com_emundus.repository.cart');
						throw new \Exception(Text::_('COM_EMUNDUS_ERROR_CONFIRMING_TRANSACTION'));
					}
				} else {
					try {
						$data = $synchronizer->prepareCheckout($transaction, $cart);
					} catch (\Exception $e) {
						Log::add('Error preparing checkout: ' . $e->getMessage() . '. Cart ID ' . $cart->getId() . ', fnum ' . $cart->getFnum(), Log::ERROR, 'com_emundus.repository.cart');
						throw new \Exception(Text::_('COM_EMUNDUS_ERROR_PREPARING_CHECKOUT'));
					}
				}
			} else {
				Log::add('No synchronizer available for this payment step, cart_id ' . $cart->getId() . ' and fnum ' . $cart->getFnum(), Log::ERROR, 'com_emundus.repository.cart');
				throw new \Exception(Text::_('COM_EMUNDUS_NO_SYNCHRONIZER'));
			}
		} else {
			throw new \Exception(Text::_('COM_EMUNDUS_NO_SYNCHRONIZER'));
		}

		return $data;
	}

	public function selectPaymentMethod(CartEntity $cart, int $payment_method_id, array $allowed_payment_methods, int $user_id): bool
	{
		$selected = false;

		$old_payment_method = $cart->getSelectedPaymentMethod();
		foreach ($allowed_payment_methods as $payment_method) {
			if ($payment_method->getId() == $payment_method_id) {
				$cart->setSelectedPaymentMethod($payment_method);
				$new_payment_method = $payment_method;

				try {
					$selected = $this->saveCart($cart);
				} catch (\Exception $e) {
					Log::add('Error selecting payment method: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.cart');
					throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SELECTING_PAYMENT_METHOD'));
				}

				break;
			}
		}

		if ($selected)
		{
			$details = [
				'updated' => [
					[
						'old' => !empty($old_payment_method)  ? $old_payment_method->getLabel() : '',
					    'new' => $new_payment_method->getLabel()
					]
				]
			];
			\EmundusModelLogs::log($user_id,  $cart->getCustomer()->getUserId(), $cart->getFnum(), $this->payment_repository->getActionId(), 'u', 'COM_EMUNDUS_UPDATE_SELECTED_CART_PAYMENT_METHOD', json_encode($details));
		}

		return $selected;
	}

	public function canUserUpdateCart(CartEntity $cart, int $user_id): bool
	{
		$can_user_update_cart = true;

		if (!empty($cart->getId()) && !empty($user_id)) {
			$payment_repository = new PaymentRepository();

			if (!class_exists('EmundusHelperAccess')) {
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
			}

			if ($cart->getCustomer()->getUserId() == $user_id || \EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'u', $user_id, $cart->getFnum())) {
				$query = $this->db->createQuery();
				$query->select('status')
					->from($this->db->quoteName('jos_emundus_campaign_candidature'))
					->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($cart->getFnum()));

				$this->db->setQuery($query);
				$current_status = $this->db->loadResult();

				if (!in_array($current_status, $cart->getPaymentStep()->getEntryStatus())) {
					$can_user_update_cart = false;
				}

				if ($can_user_update_cart) {
					$transaction_repository = new TransactionRepository();
					$transaction = $transaction_repository->getTransactionByCart($cart);

					if (!empty($transaction)) {
						if ($transaction->getStatus() === TransactionStatus::CONFIRMED || $transaction->getStatus() === TransactionStatus::WAITING) {
							$can_user_update_cart = false;
						}
					}
				}
			} else {
				$can_user_update_cart = false;
			}
		}

		return $can_user_update_cart;
	}

	/**
	 * @param   CartEntity  $cart
	 * @param   int         $user_id
	 *
	 * @return bool
	 */
	public function resetCart(CartEntity $cart, int $user_id): bool
	{
		$reset = false;

		if (!empty($cart->getId()) && !empty($user_id)) {
			$cart->setProducts([]);
			$cart->setPriceAlterations([]);
			$cart->setPayAdvance(0);
			$cart->setTotal(0);
			$cart->setNumberInstallmentDebit(1);
			$cart->setUpdatedBy($user_id);
			$cart->setSelectedPaymentMethod(null);
			$cart->setInstallmentMonthday(1);

			$reset = $this->saveCart($cart, $user_id);
		}

		return $reset;
	}
}
