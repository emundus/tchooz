<?php

namespace Tchooz\Factories\Payment;

use Tchooz\Entities\Payment\ProductCategoryEntity;

class ProductCategoryFactory
{

	public static function fromDbObjects(array $db_objects): array
	{
		$product_categories = [];

		if (!empty($db_objects))
		{
			foreach ($db_objects as $db_object) {
				$product_categories[] = new ProductCategoryEntity(
					$db_object->id,
					$db_object->label,
					$db_object->published
				);
			}
		}

		return $product_categories;
	}
}