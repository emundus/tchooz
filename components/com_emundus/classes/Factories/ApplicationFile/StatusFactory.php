<?php
/**
 * @package     Tchooz\Factories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\ApplicationFile;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Factories\DBFactory;

class StatusFactory implements DBFactory
{
	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = self::buildEntity($dbObject);
		}

		return $entities;
	}

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		if(is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject);
	}

	public static function buildEntity(object $dbObject): StatusEntity
	{
		return new StatusEntity(
			id: $dbObject->id,
			step: $dbObject->step,
			label: $dbObject->value,
			ordering: $dbObject->ordering,
			color: $dbObject->class,
		);
	}
}