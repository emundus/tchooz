<?php
/**
 * @package     Tchooz\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

abstract class EmundusFactory
{
	protected const RELATIONS = [];

	protected function loadRequestedRelations(array $object, bool|array $withRelations, array $exceptRelations): array
	{
		$relationsToLoad = $this->buildRelationsToLoad($withRelations, $exceptRelations);

		$results = [];
		foreach ($relationsToLoad as $relation)
		{
			if (!$this->isSupportedRelation($relation))
			{
				continue;
			}
			$results[$relation] = $this->loadRelation($relation, $object);
		}

		return $results;
	}

	protected function buildRelationsToLoad(bool|array $withRelations, array $exceptRelations): array
	{
		if (is_array($withRelations))
		{
			$relationsToLoad = array_diff($withRelations, $exceptRelations);
		}
		else
		{
			$relationsToLoad = $withRelations ? array_diff(static::RELATIONS, $exceptRelations) : [];
		}

		return $relationsToLoad;
	}

	protected function isSupportedRelation(string $relation): bool
	{
		return in_array($relation, static::RELATIONS, true);
	}

	abstract protected function loadRelation(string $relation, array $object): mixed;

}