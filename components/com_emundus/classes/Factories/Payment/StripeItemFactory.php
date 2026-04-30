<?php

namespace Tchooz\Factories\Payment;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\ProductEntity;

class StripeItemFactory
{
	/**
	 * @param   float           $amount
	 * @param   CurrencyEntity  $currency
	 * @param   string          $productName
	 * @param   string          $productDescription
	 * @param   int             $quantity
	 * @param   array           $metadata
	 *
	 * @return array
	 */
	public function createStripeItem(float $amount, CurrencyEntity $currency, string $productName, string $productDescription = '', int $quantity = 1, array $metadata = []): array
	{
		$product_data = ['name' => $productName,];

		if (!empty($productDescription)) {
			$product_data['description'] = $productDescription;
		}

		$item = [
			'price_data' => [
				'currency' => $currency->getIso3(),
				'product_data' => $product_data,
				'unit_amount' => (int)round($amount * 100), // Stripe expects amount in cents
			],
			'quantity' => $quantity
		];

		if (!empty($metadata))
		{
			$item['metadata'] = $metadata;
		}

		return $item;
	}

	/**
	 * Build Stripe coupon creation parameters for a discount.
	 * Always uses amount_off (fixed) because percentage coupons in Stripe
	 * apply to the full subtotal, not the running total like CartEntity does.
	 *
	 * @param   float           $amount    Absolute discount value (positive)
	 * @param   CurrencyEntity  $currency
	 * @param   string          $name
	 *
	 * @return array Parameters ready for Stripe coupon creation
	 */
	public function createCouponParams(float $amount, CurrencyEntity $currency, string $name): array
	{
		return [
			'amount_off' => (int) round($amount * 100),
			'currency'   => $currency->getIso3(),
			'duration'   => 'once',
			'name'       => !empty($name) ? $name : 'Discount',
		];
	}

	/**
	 * Convert a CartEntity into Stripe checkout data with proper line items and discount coupons.
	 *
	 * Products appear at their real price (after product-specific alterations only).
	 * Positive global alterations appear as separate fee line items.
	 * Negative global alterations appear as Stripe coupon parameters.
	 *
	 * @param   CartEntity      $cart
	 * @param   CurrencyEntity  $currency
	 *
	 * @return array{line_items: array, discounts: array}
	 * @throws \Exception if computed total doesn't match cart total
	 */
	public function buildCheckoutData(CartEntity $cart, CurrencyEntity $currency): array
	{
		$line_items = [];
		$discounts  = [];

		if ($cart->getPayAdvance() === 1)
		{
			$totalAdvance = $cart->calculateTotalAdvance()->getTotalAdvance();
			$line_items[] = $this->createStripeItem($totalAdvance, $currency, Text::_('COM_EMUNDUS_PAYMENT_ADVANCE_PAYMENT'));

			return ['line_items' => $line_items, 'discounts' => $discounts];
		}

		// Step 1: Build product line items with product-specific alterations applied
		foreach ($cart->getProducts() as $product)
		{
			if (!($product instanceof ProductEntity))
			{
				continue;
			}

			$product_price = $product->getPrice();

			foreach ($cart->getPriceAlterations() as $alteration)
			{
				if (!($alteration instanceof AlterationEntity))
				{
					continue;
				}

				if (!empty($alteration->getProduct()) && $alteration->getProduct()->getId() === $product->getId())
				{
					if ($alteration->getType() === AlterationType::FIXED)
					{
						$product_price += $alteration->getAmount();
					}
					elseif ($alteration->getType() === AlterationType::PERCENTAGE)
					{
						$product_price += $product_price * ($alteration->getAmount() / 100);
					}
				}
			}

			$line_items[] = $this->createStripeItem(
				max(0, $product_price),
				$currency,
				$product->getLabel(),
				$product->getDescription()
			);
		}

		// Step 2: Process global alterations as separate items (fees) or coupons (discounts)
		// Use a running total to mirror CartEntity::calculateTotal() sequential behaviour
		// so that percentage alterations are based on the correct intermediate total.
		$runningTotal = array_sum(array_map(
			fn($item) => $item['price_data']['unit_amount'] / 100,
			$line_items
		));

		foreach ($cart->getPriceAlterations() as $alteration)
		{
			if (!($alteration instanceof AlterationEntity))
			{
				continue;
			}

			// Skip product-specific and advance-only alterations
			if (!empty($alteration->getProduct()) || $alteration->getType() === AlterationType::ALTER_ADVANCE_AMOUNT)
			{
				continue;
			}

			// Resolve the actual monetary amount
			if ($alteration->getType() === AlterationType::PERCENTAGE)
			{
				$amount = $runningTotal * ($alteration->getAmount() / 100);
			}
			else
			{
				$amount = $alteration->getAmount();
			}

			if ($amount >= 0)
			{
				// Positive = fee/surcharge → separate line item
				$line_items[] = $this->createStripeItem($amount, $currency, $alteration->getDescription());
			}
			else
			{
				// Negative = discount → Stripe coupon (pass absolute value)
				$discounts[] = $this->createCouponParams(abs($amount), $currency, $alteration->getDescription());
			}

			$runningTotal += $amount;
		}

		// Step 3: Validate that items − discounts = cart total
		$itemsTotal = array_sum(array_map(
			fn($item) => $item['price_data']['unit_amount'] / 100,
			$line_items
		));
		$discountsTotal = array_sum(array_map(
			fn($d) => ($d['amount_off'] ?? 0) / 100,
			$discounts
		));

		$expectedTotal = round($itemsTotal - $discountsTotal, 2);
		$cartTotal     = round($cart->getTotal(), 2);

		if ($expectedTotal !== $cartTotal)
		{
			throw new \Exception(
				'Total amount mismatch: cart total is ' . $cartTotal . ', but checkout data computes ' . $expectedTotal
			);
		}

		return ['line_items' => $line_items, 'discounts' => $discounts];
	}
}
