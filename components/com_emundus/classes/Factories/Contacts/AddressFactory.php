<?php
/**
 * @package     Tchooz\Factories\AddressFactory
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Contacts;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Factories\DBFactory;

class AddressFactory implements DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): AddressEntity
	{
		if (is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		return new AddressEntity(
			id: $dbObject['id'],
			locality: $dbObject['locality'] ?? null,
			region: $dbObject['region'] ?? null,
			street_address: $dbObject['street_address'] ?? null,
			extended_address: $dbObject['extended_address'] ?? null,
			postal_code: $dbObject['postal_code'] ?? null,
			description: $dbObject['description'] ?? null,
			country: $dbObject['country'] ?? 0
		);
	}
}