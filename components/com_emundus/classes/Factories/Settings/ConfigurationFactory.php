<?php
/**
 * @package     Tchooz\Factories\Settings
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Settings;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Settings\ConfigurationEntity;
use Tchooz\Factories\DBFactory;

class ConfigurationFactory implements DBFactory
{
	public function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];

		foreach ($dbObjects as $dbObject)
		{
			if (is_array($dbObject))
			{
				$dbObject = (object) $dbObject;
			}

			$entities[] = $this->buildEntity($dbObject);
		}

		return $entities;
	}

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ConfigurationEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return $this->buildEntity($dbObject);
	}

	private function buildEntity(object $dbObject): ConfigurationEntity
	{
		return new ConfigurationEntity(
			namekey: $dbObject->namekey ?? '',
			value: $dbObject->value ? json_decode($dbObject->value, true) : null,
			default: $dbObject->default ?? '',
		);
	}
}