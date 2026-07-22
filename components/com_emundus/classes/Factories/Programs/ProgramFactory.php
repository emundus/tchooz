<?php
/**
 * @package     Tchooz\Factories\Programs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Programs;

use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\AbstractFactory;

class ProgramFactory extends AbstractFactory
{
	public function buildEntity(object $dbObject, array $relations): ProgramEntity
	{
		return new ProgramEntity(
			code: $dbObject->code,
			label: $dbObject->label,
			id: (int) $dbObject->id,
			published: (bool) $dbObject->published,
			notes: $dbObject->notes,
			programmes: $dbObject->programmes,
			synthesis: $dbObject->synthesis,
			applyOnline: $dbObject->apply_online == 1,
			ordering: (int) $dbObject->ordering,
			logo: $dbObject->logo,
			color: $dbObject->color,
			longDescription: $dbObject->long_description ?? '',
			mustOpenRights: $dbObject->must_open_rights == 1,
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		return null;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return spl_object_id($dbObject);
	}
}