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

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		if(is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		return new StatusEntity(
			id: $dbObject['id'],
			step: $dbObject['step'],
			label: $dbObject['value'],
			ordering: $dbObject['ordering'],
			color: $dbObject['class'],
		);
	}
}