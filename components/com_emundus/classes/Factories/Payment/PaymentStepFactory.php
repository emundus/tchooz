<?php

namespace Tchooz\Factories\Payment;

use Tchooz\Entities\Payment\DiscountType;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductCategoryRepository;
use Tchooz\Repositories\Payment\ProductRepository;

class PaymentStepFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<PaymentStepEntity>
	 */
	public static function fromDbObjects(array $dbObjects): array
	{
		$paymentSteps = [];

		if (!empty($dbObjects))
		{
			$productRepository = new ProductRepository();
			$productCategoryRepository = new ProductCategoryRepository();
			$paymentRepository = new PaymentRepository();
			$categories = $productCategoryRepository->getProductCategories();
			$paymentMethods = $paymentRepository->getPaymentMethods();

			foreach ($dbObjects as $dbObject) {
				if (isset($dbObject->entry_status)) {
					$dbObject->entry_status = explode(',', $dbObject->entry_status);
				}

				$products = [];
				$productIds = [];
				$mandatoryProductIds = [];

				if (!empty($dbObject->mandatory_product_ids)) {
					$mandatoryProductIds = explode(',', $dbObject->mandatory_product_ids);
					$productIds = $mandatoryProductIds;
				}
				if (!empty($dbObject->optional_product_ids)) {
					$productIds = array_merge($productIds, explode(',', $dbObject->optional_product_ids));
				}
				if (!empty($productIds)) {
					$products = $productRepository->getProducts(0, 1, ['p.id' => $productIds]);
					foreach ($products as $product)
					{
						$product->setMandatory(in_array($product->getId(), $mandatoryProductIds));
					}
				}

				$mandatoryCategories = [];
				$mandatoryCategoryIds = !empty($dbObject->product_category_ids) ? explode(',', $dbObject->product_category_ids) : [];
				$optionalCategories = [];
				$optionalCategoryIds = !empty($dbObject->optional_product_categories) ? explode(',', $dbObject->optional_product_categories) : [];

				foreach ($categories as $category)
				{
					if (in_array($category->getId(), $mandatoryCategoryIds))
					{
						$mandatoryCategories[] = $category;
					} else if (in_array($category->getId(), $optionalCategoryIds))
					{
						$optionalCategories[] = $category;
					}
				}

				$paymentMethodIds = !empty($dbObject->payment_methods) ? explode(',', $dbObject->payment_methods) : [];
				$stepPaymentMethods = [];
				foreach ($paymentMethodIds as $methodId)
				{
					foreach ($paymentMethods as $method)
					{
						if ($method->getId() == $methodId)
						{
							$stepPaymentMethods[] = $method;
							break;
						}
					}
				}

				$paymentSteps[] = new PaymentStepEntity(
					id: $dbObject->id,
					workflow_id: $dbObject->workflow_id,
					label: $dbObject->label,
					description: $dbObject->description,
					type: $dbObject->type,
					entry_status: !empty($dbObject->entry_status) ? $dbObject->entry_status : [],
					output_status: $dbObject->output_status,
					state: $dbObject->state,
					adjust_balance: $dbObject->adjust_balance ?? 0,
					adjust_balance_step_id: $dbObject->adjust_balance_step_id ?? 0,
					products: $products,
					discounts: !empty($dbObject->discounts) ? $dbObject->discounts : [],
					mandatory_product_categories: $mandatoryCategories,
					optional_product_categories: $optionalCategories,
					payment_methods: $stepPaymentMethods,
					synchronizer_id: $dbObject->synchronizer_id ?? 0,
					advance_type: $dbObject->advance_type ?? 0,
					is_advance_amount_editable_by_applicant: $dbObject->is_advance_amount_editable_by_applicant ?? 0,
					advance_amount: $dbObject->advance_amount ?? 0,
					advance_amount_type: !empty($dbObject->advance_amount_type) ? DiscountType::from($dbObject->advance_amount_type) : DiscountType::FIXED,
					installment_monthday: $dbObject->installment_monthday ?? 0,
					installment_effect_date: $dbObject->installment_effect_date ?? '',
					installment_rules: !empty($dbObjects->installment_rules) && is_array($dbObjects->installment_rules) ? $dbObjects->installment_rules : [],
				);
			}
		}

		return $paymentSteps;
	}
}