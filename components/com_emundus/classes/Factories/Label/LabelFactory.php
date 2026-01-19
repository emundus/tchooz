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

		return new LabelEntity(
			label: $dbObject->label,
			class: $dbObject->class,
			ordering: $dbObject->ordering,
			id: $dbObject->id
		);
	}

	public function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$entities[] = $this->fromDbObject($dbObject, $withRelations, $exceptRelations, $db);
		}

		return $entities;
	}
}