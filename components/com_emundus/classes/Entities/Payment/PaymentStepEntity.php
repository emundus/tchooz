<?php

namespace Tchooz\Entities\Payment;

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use stringEncode\Exception;
use Tchooz\Entities\Payment\ProductEntity;

require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');

class PaymentStepEntity
{
	private int $id;
	private int $workflow_id = 0;
	private string $label = '';
	private string $description = '';
	private int $type = 0;
	private array $entry_status = [];
	private ?int $output_status = null;

	private int $state = 1;

	private int $adjust_balance = 0;

	/**
	 * Current step will adjust the balance of the selected step, if user paid only a part of the total amount (advance)
	 */
	private int $adjust_balance_step_id = 0;

	private array $products = [];

	private array $discounts = [];

	private array $installment_rules = [];

	private array $payment_methods = [];

	private int $synchronizer_id = 0;

	/**
	 * 0: do not allow applicant to pay an advance
	 * 1: give the choice to pay an advance or not
	 * 2: force the applicant to pay an advance only
	 * @var int
	 */
	private int $advance_type = 0;

	/**
	 * Authorise the applicant or not to determine the advance amount
	 * @var int
	 */
	private int $is_advance_amount_editable_by_applicant = 0;

	private float $advance_amount = 0.00;

	private int $installment_monthday = 0;

	private string|null $installment_effect_date = null;

	private DiscountType $advance_amount_type = DiscountType::FIXED;

	public function __construct(int $id = 0)
	{
		$this->id = $id;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getWorkflowId(): int
	{
		return $this->workflow_id;
	}

	public function setWorkflowId(int $workflow_id): void
	{
		$this->workflow_id = $workflow_id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function setDescription(string $description): void
	{
		if (!empty($description)) {
			if(!class_exists('HtmlSanitizerSingleton')) {
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
			}

			$sanitizer = HtmlSanitizerSingleton::getInstance();
			$description = $sanitizer->sanitizeFor('body', $description);
		}

		$this->description = $description;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getType(): int
	{
		return $this->type;
	}

	public function setType(int $type): void
	{
		$this->type = $type;
	}

	public function getEntryStatus(): array
	{
		return $this->entry_status;
	}

	public function setEntryStatus(array $entry_status): void
	{
		foreach ($entry_status as $key => $status) {
			$entry_status[$key] = (int) $status;
		}

		$this->entry_status = $entry_status;
	}

	public function getOutputStatus(): ?int
	{
		return $this->output_status;
	}

	public function setOutputStatus(?int $output_status): void
	{
		$this->output_status = $output_status;
	}

	public function getState(): int
	{
		return $this->state;
	}

	public function setState(int $state): void
	{
		$this->state = $state;
	}

	public function getAdjustBalance(): int
	{
		return $this->adjust_balance;
	}

	public function setAdjustBalance(int $adjust_balance): void
	{
		$this->adjust_balance = $adjust_balance;

		if ($this->adjust_balance === 1) {
			$this->setAdvanceType(0);
			$this->setAdvanceAmount(0);
			$this->setAdvanceAmountType(DiscountType::FIXED);
			$this->setIsAdvanceAmountEditableByApplicant(0);
		}
	}

	public function getAdjustBalanceStepId(): int
	{
		return $this->adjust_balance_step_id;
	}

	public function setAdjustBalanceStepId(int $adjust_balance_step_id): void
	{
		$this->adjust_balance_step_id = $adjust_balance_step_id;
	}

	public function getProducts(): array
	{
		return $this->products;
	}

	public function setProducts(array $products): void
	{
		foreach ($products as $product)
		{
			if (!($product instanceof ProductEntity))
			{
				throw new \InvalidArgumentException('Product must be an instance of ProductEntity');
			}
		}

		$this->products = $products;
	}

	public function getDiscounts(): array
	{
		return $this->discounts;
	}

	public function setDiscounts(array $discounts): void
	{
		$this->discounts = $discounts;
	}

	public function getPaymentMethods(): array
	{
		return $this->payment_methods;
	}

	public function setPaymentMethods(array $payment_methods): void
	{
		foreach($payment_methods as $payment_method)
		{
			if (!($payment_method instanceof PaymentMethodEntity))
			{
				throw new \InvalidArgumentException('Payment method must be an instance of PaymentMethodEntity');
			}
		}

		$this->payment_methods = $payment_methods;
	}

	public function getSynchronizerId(): int
	{
		return $this->synchronizer_id;
	}

	public function setSynchronizerId(int $synchronizer_id): void
	{
		$this->synchronizer_id = $synchronizer_id;
	}

	public function getAdvanceType(): int
	{
		return $this->advance_type;
	}

	public function setAdvanceType(int $advance_type): void
	{
		if ($advance_type < 0 || $advance_type > 2) {
			throw new \InvalidArgumentException('Invalid advance type');
		}

		if ($this->getAdjustBalance() && $advance_type != 0) {
			throw new \InvalidArgumentException('Advance type cannot be forced when adjust balance is enabled');
		}

		$this->advance_type = $advance_type;
	}

	public function isAdvanceAmountEditableByApplicant(): int
	{
		return $this->is_advance_amount_editable_by_applicant;
	}

	public function setIsAdvanceAmountEditableByApplicant(int $is_advance_amount_editable_by_applicant): void
	{
		$this->is_advance_amount_editable_by_applicant = $is_advance_amount_editable_by_applicant;
	}

	public function getAdvanceAmount(): int
	{
		return $this->advance_amount;
	}

	public function setAdvanceAmount(int $advance_amount): void
	{
		if ($advance_amount < 0) {
			throw new \InvalidArgumentException('Invalid advance amount');
		}

		$this->advance_amount = $advance_amount;
	}

	public function getAdvanceAmountType(): DiscountType
	{
		return $this->advance_amount_type;
	}

	public function setAdvanceAmountType(DiscountType $advance_amount_type): void
	{
		$this->advance_amount_type = $advance_amount_type;
	}

	public function setInstallmentRules(array $installment_rules): void
	{
		foreach ($installment_rules as $installment_rule) {
			// must bet set from_amount, to_amount, min_installments and max_installments
			if (!isset($installment_rule->from_amount) || !isset($installment_rule->to_amount) || !isset($installment_rule->min_installments) || !isset($installment_rule->max_installments)) {
				throw new Exception('COM_EMUNDUS_PAYMENT_STEP_WRONG_INSTALLMENT_RULE');
			}
		}

		$this->installment_rules = $installment_rules;
	}

	public function getInstallmentRules(): array
	{
		return $this->installment_rules;
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

	public function getInstallmentEffectDate(): ?string
	{
		return $this->installment_effect_date;
	}

	public function setInstallmentEffectDate(?string $installment_effect_date): void
	{
		if ($installment_effect_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $installment_effect_date)) {
			throw new \InvalidArgumentException('Invalid installment effective date format');
		}

		$this->installment_effect_date = $installment_effect_date;
	}

	public function getInstallmentDisplayEffectDate(): string
	{
		$displayed_date = '';

		if (!empty($this->installment_effect_date)) {
			if (!class_exists('EmundusHelperDate')) {
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/date.php');
			}
			$displayed_date = \EmundusHelperDate::displayDate($this->installment_effect_date, 'DATE_FORMAT_LC3', 0);
		}

		return $displayed_date;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'workflow_id' => $this->workflow_id,
			'label' => $this->label,
			'description' => $this->description,
			'type' => $this->type,
			'entry_status' => $this->entry_status,
			'output_status' => $this->output_status,
			'state' => $this->state,
			'adjust_balance' => $this->adjust_balance,
			'adjust_balance_step_id' => $this->adjust_balance_step_id,
			'products' => array_map(fn($product) => $product->serialize(), $this->products),
			'discounts' => array_map(fn($discount) => $discount->serialize(), $this->discounts),
			'payment_methods' => array_map(fn($payment_method) => $payment_method->serialize(), $this->payment_methods),
			'synchronizer_id' => $this->getSynchronizerId(),
			'advance_type' => $this->getAdvanceType(),
			'is_advance_amount_editable_by_applicant' => $this->is_advance_amount_editable_by_applicant,
			'advance_amount' => $this->getAdvanceAmount(),
			'advance_amount_type' => $this->advance_amount_type->value,
			'installment_monthday' => $this->installment_monthday,
			'installment_effect_date' => $this->installment_effect_date,
			'display_installment_effect_date' => $this->getInstallmentDisplayEffectDate(),
			'installment_rules' => $this->installment_rules
		];
	}
}