<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Entities\Payment\DiscountEntity;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Payment\DiscountRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;

class onAfterEmundusCartUpdateDefinition extends EventDefinition
{
	const TOTAL_KEY = 'total';
	const PRODUCTS_KEY = 'products';
	const DISCOUNTS_KEY = 'discounts';
	const SELECTED_PAYMENT_METHOD_KEY = 'selected_payment_method';
	const PAY_ADVANCE_KEY = 'pay_advance';
	const NUMBER_INSTALLMENT_DEBIT_KEY = 'number_installment_debit';
	const AMOUNTS_BY_ITERATIONS_KEY = 'amounts_by_iterations';
	const PAYMENT_STEP_KEY = 'payment_step';

	public function __construct()
	{
		// todo: add customer key to allow condition on users

		parent::__construct(
			'onAfterEmundusCartUpdate',
			[
				new NumericField(self::TOTAL_KEY, Text::_('COM_EMUNDUS_TOTAL')),
				new ChoiceField(self::PRODUCTS_KEY, Text::_('COM_EMUNDUS_PRODUCTS'), $this->getProductsList(), false, true),
				new ChoiceField(self::DISCOUNTS_KEY, Text::_('COM_EMUNDUS_DISCOUNTS'), $this->getDiscountsList(), false, true),
				new ChoiceField(self::SELECTED_PAYMENT_METHOD_KEY, Text::_('COM_EMUNDUS_TRANSACTION_PAYMENT_METHOD'), $this->getPaymentMethodsList(), false, true),
				new YesnoField(self::PAY_ADVANCE_KEY, Text::_('COM_EMUNDUS_CART_PAYMENT_PAY_ADVANCE')),
				new NumericField(self::NUMBER_INSTALLMENT_DEBIT_KEY, Text::_('COM_EMUNDUS_TRANSACTION_INSTALLMENT_NUMBER_DEBIT')),
				new NumericField(self::AMOUNTS_BY_ITERATIONS_KEY, Text::_('COM_EMUNDUS_TRANSACTION_INSTALLMENT_AMOUNT')),
				new ChoiceField(self::PAYMENT_STEP_KEY, Text::_('COM_EMUNDUS_CURRENT_CART_STEP'), $this->getPaymentStepsList(), false, true),
			]
		);
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getProductsList(): array
	{
		$options = [];

		$productRepository = new ProductRepository();
		$products = $productRepository->getProducts(0);

		foreach ($products as $product)
		{
			assert($product instanceof ProductEntity);

			$options[] = new ChoiceFieldValue($product->getId(), $product->getLabel());
		}

		return $options;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getDiscountsList(): array
	{
		$options = [];

		$repository = new DiscountRepository();
		foreach ($repository->getDiscounts(0) as $discount)
		{
			assert($discount instanceof DiscountEntity);
			$options[] = new ChoiceFieldValue($discount->getId(), $discount->getLabel());
		}

		return $options;
	}

	private function getPaymentMethodsList(): array
	{
		$options = [];

		$repository = new PaymentRepository();
		foreach ($repository->getPaymentMethods() as $paymentMethod)
		{
			assert($paymentMethod instanceof PaymentMethodEntity);
			$options[] = new ChoiceFieldValue($paymentMethod->getId(), $paymentMethod->getLabel());
		}

		return $options;
	}

	private function getPaymentStepsList(): array
	{
		$options = [];

		if (!class_exists('EmundusModelWorkflow'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/workflow.php';
		}
		$m_workflow = new \EmundusModelWorkflow();
		$repository = new PaymentRepository();
		$steps = $m_workflow->getSteps(0, [$repository->getPaymentStepTypeId()]);

		foreach ($steps as $step)
		{
			assert($step instanceof StepEntity);
			$options[] = new ChoiceFieldValue($step->getId(), $step->getLabel());
		}


		return $options;
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}
}