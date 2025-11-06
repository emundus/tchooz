<?php

namespace Tchooz\Entities\Payment;

use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Payment\DiscountEntity;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Exception\EmundusAdjustBalanceAlreadyAddedException;

class CartEntity {
	private int $id = 0;
	private ?string $created_at = null;
	private int $created_by = 0;
	private ?string $updated_at = null;
	private int $updated_by = 0;
	public int $published = 1;
	private DatabaseDriver $db;

	public ?ContactEntity $customer = null;
	private array $products = [];
	private array $available_products = [];
	private float $total = 0.00;
	private float $total_advance = 0.00;
	private int $installment_monthday = 1;

	public string $fnum = '';

	/**
	 * @var int $step_id
	 * The current step id of the cart
	 * If current step id and payment step id are not the same, it means we have to reset the cart
	 */
	private int $step_id = 0;

	public array $payment_methods = [];
	private PaymentMethodEntity|null $selected_payment_method = null;

	private array $alterations = [];

	private ?CurrencyEntity $currency = null;

	private int $number_installment_debit = 1;

	private int $pay_advance = 0;
	private bool $allowed_to_pay_advance = false;
	private int $advance_amount = 0;
	private ?DiscountType $advance_amount_type = null;

	private PaymentStepEntity|null $payment_step = null;

	public function __construct(int $id)
	{
		Log::addLogger(['text_file' => 'com_emundus.entity.cart.php'], Log::ALL, ['com_emundus.entity.cart']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->id = $id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setCreatedAt(string $created_at): void
	{
		$this->created_at = $created_at;
	}

	public function getCreatedAt(bool $formatted = false): ?string
	{
		$created_at = $this->created_at;

		if ($formatted) {
			$created_at = \EmundusHelperDate::displayDate($this->created_at, 'DATE_FORMAT_LC2', 0);
		}

		return $created_at;
	}

	public function setCreatedBy(int $created_by): void
	{
		if ($created_by < 0) {
			throw new \InvalidArgumentException('Created by cannot be negative');
		}
		$this->created_by = $created_by;
	}

	public function getCreatedBy(): int
	{
		return $this->created_by;
	}

	public function setUpdatedAt(string $updated_at): void
	{
		$this->updated_at = $updated_at;
	}

	public function getUpdatedAt(bool $formatted = false): ?string
	{
		$updated_at = $this->updated_at;

		if ($formatted) {
			$updated_at = \EmundusHelperDate::displayDate($this->updated_at, 'DATE_FORMAT_LC2', 0);
		}

		return $updated_at;
	}

	public function setUpdatedBy(int $updated_by): void
	{
		if ($updated_by < 0) {
			throw new \InvalidArgumentException('Updated by cannot be negative');
		}
		$this->updated_by = $updated_by;
	}

	public function getUpdatedBy(): int
	{
		return $this->updated_by;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function setFnum(string $fnum): void
	{
		$this->fnum = $fnum;
	}

	public function getStepId(): int
	{
		return $this->step_id;
	}

	public function setStepId(int $step_id): void
	{
		if ($step_id < 0) {
			throw new \InvalidArgumentException('Step id cannot be negative');
		}
		$this->step_id = $step_id;
	}

	public function calculateTotal(): void
	{
		$total = 0.00;

		foreach ($this->products as $product) {
			$product_default_price = $product->getPrice();
			$product_price = $product_default_price;
			foreach($this->alterations as $alteration) {
				if (!empty($alteration->getProduct()) && $product->getId() == $alteration->getProduct()->getId()) {
					switch($alteration->getType()) {
						case AlterationType::FIXED:
							$product_price += $alteration->getAmount();
							break;
						case AlterationType::PERCENTAGE:
							$product_price += $product_price * ($alteration->getAmount() / 100);
							break;
					}
				}
			}

			$total += $product_price;
		}

		foreach($this->alterations as $alteration) {
			if (empty($alteration->getProduct())) {
				switch($alteration->getType()) {
					case AlterationType::FIXED:
					case AlterationType::ADJUST_BALANCE:
						$total += $alteration->getAmount();
						break;
					case AlterationType::PERCENTAGE:
						$total += $total * ($alteration->getAmount() / 100);
						break;
				}
			}
		}

		if ($total < 0) {
			$total = 0;
		}

		if ($this->isAllowedToPayAdvance()) {
			$this->calculateTotalAdvance();
		}

		// Save total to database
		$query = $this->db->createQuery();

		$query->update($this->db->quoteName('jos_emundus_cart'))
			->set($this->db->quoteName('total') . ' = ' . $this->db->quote($total))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($this->getId()));

		try {
			$this->db->setQuery($query);
			$updated = $this->db->execute();

			if (!$updated) {
				throw new \Exception('Failed to update cart total in database.');
			}

			$this->setTotal($total);
		} catch (\Exception $e) {
			Log::add('Error calculating total: ' . $e->getMessage(), Log::ERROR, 'com_emundus.entity.cart');
		}
	}

	public function calculateTotalAdvance() {
		$total = 0.00;

		if ($this->advance_amount_type == DiscountType::PERCENTAGE) {
			$total = $this->total * ($this->advance_amount / 100);
		} else {
			$total = $this->advance_amount;
		}

		$this->setTotalAdvance($total);
	}

	public function setCustomer(ContactEntity $customer): void
	{
		$this->customer = $customer;
	}

	public function getCustomer(): ContactEntity
	{
		return $this->customer;
	}

	public function setTotal(float $total): void
	{
		$this->total = $total;
	}

	public function getTotal(): float
	{
		$this->calculateTotal();

		return $this->total;
	}

	public function setTotalAdvance(float $total_advance): void
	{
		$this->total_advance = $total_advance;
	}

	public function getTotalAdvance(): float
	{
		return $this->total_advance;
	}

	public function getDisplayedTotal(): string
	{
		return $this->formatPrice($this->total);
	}

	private function formatPrice(float $price): string
	{
		$currency = $this->getCurrency();
		if ($currency) {
			return number_format($price, 2, '.', ' ') . ' ' . $currency->symbol;
		}

		return number_format($price, 2, '.', ' ');

	}

	public function getSelectedPaymentMethod(): ?PaymentMethodEntity
	{
		return $this->selected_payment_method;
	}

	public function setSelectedPaymentMethod(PaymentMethodEntity|null $payment_method): void
	{
		$this->selected_payment_method = $payment_method;
	}

	public function setProducts(array $products): void
	{
		foreach ($products as $product) {
			if (!($product instanceof ProductEntity)) {
				throw new \InvalidArgumentException('Products must be an array of ProductEntity objects');
			}
		}

		$this->products = $products;
	}

	public function getProducts(): array
	{
		return $this->products;
	}

	public function addProduct(ProductEntity $product): void
	{
		// only add if not already in it
		$already_in_cart = false;
		foreach ($this->products as $key => $cart_product) {
			if ($cart_product->getId() == $product->getId()) {
				$this->products[$key] = $product;
				$already_in_cart = true;
			}
		}

		if (!$already_in_cart) {
			$this->products[] = $product;
			$this->calculateTotal();
		}
	}

	public function removeProduct(ProductEntity $product): void
	{
		foreach ($this->products as $key => $cart_product) {
			if ($cart_product->getId() == $product->getId()) {
				unset($this->products[$key]);
				$this->products = array_values($this->products);
				$this->calculateTotal();
				break;
			}
		}
	}

	public function addAvailableProduct(ProductEntity $product): void
	{
		$this->available_products[] = $product;
	}

	public function getAvailableProducts(): array
	{
		return $this->available_products;
	}

	public function setCurrency(CurrencyEntity $currency): void
	{
		$this->currency = $currency;
	}

	public function getCurrency(): ?CurrencyEntity
	{
		$currency = $this->currency;

		if (empty($currency) && !empty($this->products)) {
			$currency = $this->products[0]->currency;
		}

		return $currency;
	}

	public function setPaymentMethods(array $payment_methods): void
	{
		foreach ($payment_methods as $payment_method) {
			if (!($payment_method instanceof PaymentMethodEntity)) {
				throw new \InvalidArgumentException('All items in payment_methods array must be instances of PaymentMethodEntity');
			}
		}

		$this->payment_methods = $payment_methods;
	}

	public function getPaymentMethods(): array
	{
		return $this->payment_methods;
	}

	public function setPublished(int $published): void
	{
		$this->published = $published;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function setPriceAlterations(array $alterations): void
	{
		foreach ($alterations as $alteration) {
			if (!($alteration instanceof AlterationEntity)) {
				throw new \InvalidArgumentException('All items in alterations array must be instances of AlterationEntity');
			}
		}

		// there can be only one adjustment balance alteration
		$nb_adjustment_balance = 0;
		foreach ($alterations as $key => $alteration) {
			if ($alteration->getType() === AlterationType::ADJUST_BALANCE) {
				$nb_adjustment_balance++;
				if ($nb_adjustment_balance > 1) {
					throw new \InvalidArgumentException('There can only be one adjustment balance alteration');
				}
			}
		}

		$this->alterations = $alterations;
	}

	public function getPriceAlterations(): array
	{
		return $this->alterations;
	}

	public function addAlteration(AlterationEntity $alteration): bool
	{
		$added = false;

		// alteration must at least have description, amount and type
		if (empty($alteration->getDescription()) || empty($alteration->getAmount()) || empty($alteration->getType())) {
			throw new \InvalidArgumentException('Alteration must have description, amount and type');
		}

		if ($alteration->getType() === AlterationType::ADJUST_BALANCE) {
			// verify there is not already another one, can only be one
			foreach ($this->alterations as $existing_alteration) {
				if ($existing_alteration->getType() === AlterationType::ADJUST_BALANCE) {
					throw new EmundusAdjustBalanceAlreadyAddedException('There can only be one adjustment balance alteration');
				}
			}
		}

		if (!empty($alteration->getDiscount()))
		{
			$already_added = false;
			foreach ($this->alterations as $existing_alteration) {

				if (!empty($existing_alteration->getDiscount()) && $existing_alteration->getDiscount()->getId() === $alteration->getDiscount()->getId())
				{
					$already_added = true;
				}
			}

			if (!$already_added) {
				$added = true;
				$this->alterations[] = $alteration;
			} else {
				$added = true;
			}
		}
		else
		{
			$this->alterations[] = $alteration;
			$added = true;
		}

		return $added;
	}

	public function removeAlteration(AlterationEntity $alteration_to_remove): void
	{
		$this->alterations = array_filter($this->alterations, function ($alteration) use ($alteration_to_remove) {
			return $alteration->getId() !== $alteration_to_remove->getId();
		});
		$this->alterations = array_values($this->alterations);
		$this->calculateTotal();
	}

	public function setNumberInstallmentDebit(int $number): void
	{
		if ($number < 0)
		{
			throw new \InvalidArgumentException('Number installment debit must be greater than 0');
		}

		$valid_number = $this->numberInstallmentRespectRules($number);

		if (!$valid_number) {
			throw new \InvalidArgumentException('Number installment debit does not respect the payment method rules');
		}

		$this->number_installment_debit = $number;
	}

	public function getNumberInstallmentDebit(): int
	{
		return $this->number_installment_debit;
	}

	public function numberInstallmentRespectRules(int $number): bool
	{
		$valid_number = true;

		if (!empty($this->payment_step)) {
			if (!empty($this->payment_step->getInstallmentRules()) && !empty($this->getSelectedPaymentMethod()) && $this->getSelectedPaymentMethod()->getName() === 'sepa') {
				$valid_number = false;

				foreach ($this->payment_step->getInstallmentRules() as $rule)
				{
					if ($this->getTotal() >= $rule->from_amount && $this->getTotal() <= $rule->to_amount) {
						if ($number >= $rule->min_installments && $number <= $rule->max_installments) {
							$valid_number = true;
							break;
						}
					}
				}
			} else if ($number > 1) {
				$valid_number = false;
			}
		}

		return $valid_number;
	}

	public function setPayAdvance(int $pay_advance): void
	{
		$this->pay_advance = $pay_advance === 1 ? 1 : 0;
	}

	public function getPayAdvance(): int
	{
		return $this->pay_advance;
	}

	public function setAllowedToPayAdvance(bool $allowed): void
	{
		$this->allowed_to_pay_advance = $allowed;
	}

	public function isAllowedToPayAdvance(): bool
	{
		return $this->allowed_to_pay_advance;
	}

	public function setAdvanceAmount(?int $advance_amount): void
	{
		$this->advance_amount = $advance_amount;
	}

	public function getAdvanceAmount(): ?int
	{
		return $this->advance_amount;
	}

	public function setAdvanceAmountType(DiscountType $advance_amount_type): void
	{
		$this->advance_amount_type = $advance_amount_type;
	}

	public function getAdvanceAmountType(): ?DiscountType
	{
		return $this->advance_amount_type;
	}

	public function setPaymentStep(PaymentStepEntity $payment_step): void
	{
		$this->payment_step = $payment_step;
	}

	public function getPaymentStep(): ?PaymentStepEntity
	{
		return $this->payment_step;
	}

	public function getInstallmentMonthday(): int
	{
		return $this->installment_monthday;
	}

	public function setInstallmentMonthday(int $installment_monthday): void
	{
		if ($installment_monthday < 0 || $installment_monthday > 31) {
			throw new \InvalidArgumentException('Invalid installment month day');
		}

		$this->installment_monthday = $installment_monthday;
	}

	public function serialize(): array
	{
		$customer = $this->customer->__serialize();

		$customer['address'] = null;
		$customer_addresses = $this->customer->getAddresses();
		if(!empty($customer_addresses))
		{
			$customer['address'] = $customer_addresses[0]?->__serialize();
		}

		return [
			'id' => $this->getId(),
			'displayed_total' => $this->getDisplayedTotal(),
			'total' => $this->getTotal(),
			'displayed_total_advance' => $this->formatPrice($this->total_advance),
			'total_advance' => $this->getTotalAdvance(),
			'pay_advance' => $this->getPayAdvance(),
			'currency' => $this->getCurrency()?->serialize(),
			'products' => array_map(function($product) {
				return $product->serialize();
			}, $this->products),
			'available_products' => array_map(function($product) {
				return $product->serialize();
			}, $this->available_products),
			'customer' => $customer,
			'payment_methods' => array_map(function($payment_method) {
				return $payment_method->serialize();
			}, $this->payment_methods),
			'selected_payment_method' => $this->selected_payment_method?->serialize(),
			'alterations' => array_map(function($alteration) {
				return $alteration->serialize();
			}, $this->alterations),
			'number_installment_debit' => $this->number_installment_debit,
			'amounts_by_iterations' => PaymentRepository::generateAmountsByIterations($this->getTotal(), $this->number_installment_debit),
			'installment_monthday' => $this->installment_monthday,
			'payment_step' => $this->payment_step?->serialize(),
		];
	}
}