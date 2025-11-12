<?php

namespace Tchooz\Factories\Payment;

use Tchooz\Entities\Payment\CurrencyEntity;

class CurrencyFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<CurrencyEntity>
	 */
	public static function fromDbObjects(array $dbObjects): array
	{
		$currencies = [];

		if (!empty($dbObjects))
		{
			foreach ($dbObjects as $dbObject) {
				$currencies[] = new CurrencyEntity(
					(int) $dbObject->id,
					(string) $dbObject->name,
					(string) $dbObject->symbol,
					(string) $dbObject->iso3,
					(int) $dbObject->published,
					isset($dbObject->iso4217) ? (string) $dbObject->iso4217 : null
				);
			}
		}

		return $currencies;
	}
}