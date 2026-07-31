<?php
/**
 * @package     Tchooz\Factories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\ApplicationFile;

use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Factories\AbstractFactory;

class StatusFactory extends AbstractFactory
{
	public function buildEntity(object $dbObject, array $relations): StatusEntity
	{
		return new StatusEntity(
			id: $dbObject->id,
			step: $dbObject->step,
			label: $dbObject->value,
			ordering: $dbObject->ordering,
			color: $dbObject->class,
		);
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		return null;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return '';
	}
}