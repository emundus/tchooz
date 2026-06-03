<?php

namespace Tchooz\Factories\ApplicationFile;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\ApplicationFile\ApplicationTagEntity;
use Tchooz\Factories\DBFactory;

class ApplicationTagFactory implements DBFactory
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
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject);
	}

	public static function buildEntity(object $dbObject): ApplicationTagEntity
	{
		return new ApplicationTagEntity(
			id: (int) $dbObject->id,
			label: $dbObject->label ?? '',
			color: $dbObject->class ?? '',
			ordering: (int) ($dbObject->ordering ?? 0),
			category: $dbObject->category ?? '',
		);
	}
}