<?php
/**
 * @package     Tchooz\Factories\Label
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Label;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Factories\DBFactory;

class LabelFactory implements DBFactory
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

	public static function buildEntity(object $dbObject): LabelEntity
	{
		return new LabelEntity(
			label: $dbObject->label ?? '',
			class: $dbObject->class ?? '',
			ordering: (int) ($dbObject->ordering ?? 0),
			id: $dbObject->id,
			category: $dbObject->category ?? '',
		);
	}
}