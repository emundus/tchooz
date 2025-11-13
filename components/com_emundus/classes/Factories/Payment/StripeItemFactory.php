<?php

namespace Tchooz\Factories\Payment;

use Tchooz\Entities\Payment\CurrencyEntity;

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
}