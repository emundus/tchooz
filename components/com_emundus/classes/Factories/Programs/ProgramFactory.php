<?php
/**
 * @package     Tchooz\Factories\Programs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Programs;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\DBFactory;

class ProgramFactory
{

	public static function fromDbObjects(array $dbObjects, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$programs = [];

		foreach ($dbObjects as $dbObject)
		{
			$programs[] = self::fromDbObject($dbObject, $withRelations, $exceptRelations, $db);
		}

		return $programs;
	}

	public static function fromDbObject(object|array $dbObject, $withRelations = true, $exceptRelations = [], ?DatabaseDriver $db = null): ProgramEntity
	{
		if(is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		return new ProgramEntity(
			code: $dbObject['code'],
			label: $dbObject['label'],
			id: (int) $dbObject['id'],
			published: (bool) $dbObject['published'],
			notes: $dbObject['notes'],
			programmes: $dbObject['programmes'],
			synthesis: $dbObject['synthesis'],
			applyOnline: (bool) $dbObject['apply_online'],
			ordering: (int) $dbObject['ordering'],
			logo: $dbObject['logo'],
			color: $dbObject['color']
		);
	}
}