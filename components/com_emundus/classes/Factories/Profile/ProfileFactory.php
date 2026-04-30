<?php
/**
 * @package     Tchooz\Factories\Groups
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Profile;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Profile\ProfileEntity;
use Tchooz\Factories\DBFactory;

class ProfileFactory implements DBFactory
{

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ProfileEntity
	{
		if (is_array($dbObject))
		{
			$dbObject = (object) $dbObject;
		}

		return self::buildEntity($dbObject);
	}

	public static function fromDbObjects(array $dbObjects, bool|array $withRelations = true): array
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = self::buildEntity($dbObject);
		}

		return $entities;
	}

	public static function buildEntity(object $dbObject): ProfileEntity
	{
		return new ProfileEntity(
			$dbObject->id,
			$dbObject->label,
			$dbObject->description,
			$dbObject->published,
			$dbObject->menutype,
			$dbObject->acl_aro_groups,
			$dbObject->class
		);
	}
}