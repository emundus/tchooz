<?php

namespace Tchooz\Factories\Payment;

use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Repositories\Payment\CurrencyRepository;
use Tchooz\Repositories\Payment\ProductCategoryRepository;

class ProductFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<ProductEntity>
	 */
	public static function fromDbObjects(array $dbObjects): array
	{
		$products = [];

		if (!empty($dbObjects))
		{
			$currencyRepository = new CurrencyRepository();
			$categoriesRepository = new ProductCategoryRepository();

			foreach ($dbObjects as $dbObject) {
				$products[] = new ProductEntity(
					(int) $dbObject->id,
					(string) $dbObject->label,
					(string) $dbObject->description,
					(float) $dbObject->price,
					$currencyRepository->getCurrencyById((int) $dbObject->currency_id),
					(bool) $dbObject->illimited,
					(int) $dbObject->quantity,
					$categoriesRepository->getProductCategoryById((int) $dbObject->category_id),
					!empty($dbObject->available_from) ? new \DateTime($dbObject->available_from) : null,
					!empty($dbObject->available_to) ? new \DateTime($dbObject->available_to) : null,
					!empty($dbObject->campaigns) ? explode(',', $dbObject->campaigns) : [],
					(bool) $dbObject->published
				);
			}
		}

		return $products;
	}
}