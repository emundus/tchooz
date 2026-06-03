<?php

namespace Tchooz\Factories\Label;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Label\LabelAssociationEntity;
use Tchooz\Factories\DBFactory;

class LabelAssociationFactory implements DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject);
	}

	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = self::buildEntity($dbObject);
		}

		return $entities;
	}

	public static function buildEntity(object $dbObject): LabelAssociationEntity
	{
		// get object data under "esat." keys
		$labelEntityObject = [];
		foreach ($dbObject as $key => $value)
		{
			if (str_starts_with($key, 'esat_'))
			{
				$labelEntityObject[str_replace('esat_', '', $key)] = $value;
			}
		}

		return new LabelAssociationEntity(
			$dbObject->id ?? 0,
			$dbObject->id_tag ?? 0,
			$dbObject->fnum ?? 0,
			!empty($dbObject->date_time) ? new \DateTimeImmutable($dbObject->date_time) : new \DateTimeImmutable(),
			!empty($dbObject->user_id) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($dbObject->user_id) : null,
			!empty($labelEntityObject) ? LabelFactory::buildEntity((object)$labelEntityObject) : null
		);
	}
}