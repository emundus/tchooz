<?php

namespace Tchooz\Factories\Payment;

use Tchooz\Entities\Payment\PaymentMethodEntity;

class PaymentMethodFactory
{

	/**
	 * @param   array  $objects
	 *
	 * @return array<PaymentMethodEntity>
	 */
	public function fromDbObjects(array $objects): array
	{
		$methods = [];

		foreach ($objects as $object) {
			$method = new PaymentMethodEntity(
				$object->id,
				$object->name,
				$object->label,
				$object->description,
				$object->published,
			!empty($object->services) ? explode(',', $object->services) : []
			);
			$methods[] = $method;
		}

		return $methods;
	}
}