<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\ExternalReferenceEntity;

class ExternalReferenceFactory implements DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ExternalReferenceEntity
	{
		if(is_object($dbObject)) {
			$dbObject = (array) $dbObject;
		}

		return new ExternalReferenceEntity(
			column: $dbObject['column'],
			intern_id: $dbObject['intern_id'],
			reference: $dbObject['reference'],
			id: $dbObject['id'] ?? 0
		);
	}

}