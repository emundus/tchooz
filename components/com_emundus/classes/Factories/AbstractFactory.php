<?php
/**
 * @package     Tchooz\Factories
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories;

use Joomla\Database\DatabaseDriver;
use Tchooz\Factories\Cache\RelationCache;

/**
 * Abstract class for all factories
 *
 * Responsibilities :
 * - Relations declaration
 * - Static cache of relations (RelationCache / Identity Map)
 * - Array conversion to object (backward compatibility)
 * - fromDbObject/fromdbObjects methods
 * - Subrelation configuration if needed
 *
 * Each factory need to :
 * 1. Define RELATIONS const with the list of supported relations, by default empty
 * 2. Implement buildEntity() to build entity from object
 * 3. Implement loadRelation() to load each relation
 * 4. Implement getRelationCacheKey() to define the cache key for each relation (ex: campaign_id)
 * 5. Optionnally : implement preloadRelations() to have a batch loading of relations
 */
abstract class AbstractFactory implements BatchDBFactory
{
	/**
	 * Relations supported
	 * Each entry is a relation name (ex: CampaignRepository::NAME).
	 *
	 * @var string[]
	 */
	protected const RELATIONS = [];

	/**
	 * Subrelation configuration (advanced usage only to optimize queries in complex object graphs).
	 *
	 * Allows you to control the relations loaded by sub-factories/repositories
	 * when this factory loads its own relations
	 *
	 * Format :
	 *   [
	 *     RelationName::class => [
	 *       'withRelations'   => bool|array,   // true, false, or relations list
	 *       'exceptRelations' => array,         // relations to exclude (only if withRelations is true or array)
	 *     ]
	 *   ]
	 *
	 * Example :
	 *   // ApplicationFile component loaded via this factory will NOT retrieve its campaign
	 *   protected array $subRelations = [
	 *     ApplicationFileRepository::NAME => [
	 *       'exceptRelations' => [CampaignRepository::NAME],
	 *     ],
	 *   ];
	 */
	protected array $subRelations = [];

	/**
	 * Configures the sub-relations for a given relationship.
	 *
	 * @param string     $relation        The name of the parent relationship
	 * @param bool|array $withRelations   Relations to be loaded into the sub-entity
	 * @param array      $exceptRelations Relations to be excluded in the sub-entity
	 * @return static
	 */
	public function withSubRelations(string $relation, bool|array $withRelations = true, array $exceptRelations = []): static
	{
		$this->subRelations[$relation] = [
			'withRelations'   => $withRelations,
			'exceptRelations' => $exceptRelations,
		];

		return $this;
	}

	/**
	 * Disables all sub-relations of a given relation.
	 *
	 * @param string $relation
	 * @return static
	 */
	public function withoutSubRelations(string $relation): static
	{
		$this->subRelations[$relation] = [
			'withRelations'   => false,
			'exceptRelations' => [],
		];

		return $this;
	}

	/**
	 * Returns the sub-relation configuration for a given relation.
	 *
	 * @param string $relation
	 * @return array{withRelations: bool|array, exceptRelations: array}
	 */
	protected function getSubRelationConfig(string $relation): array
	{
		return $this->subRelations[$relation] ?? [
			'withRelations'   => true,
			'exceptRelations' => [],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function fromDbObject(
		object|array $dbObject,
		bool|array $withRelations = true,
		array $exceptRelations = [],
		?DatabaseDriver $db = null
	): mixed
	{
		$dbObject = $this->normalizeToObject($dbObject);

		$relationsToLoad = $this->resolveRelations($withRelations, $exceptRelations);
		$relations = $this->loadRelationsForObject($dbObject, $relationsToLoad);

		return $this->buildEntity($dbObject, $relations);
	}

	/**
	 * @inheritDoc
	 *
	 * Uses preloading to batch-load shared relations
	 * and then constructs each entity with the cache already populated.
	 */
	public function fromDbObjects(
		array $dbObjects,
		bool|array $withRelations = true,
		array $exceptRelations = [],
		?DatabaseDriver $db = null
	): array
	{
		$dbObjects = array_map(fn($obj) => $this->normalizeToObject($obj), $dbObjects);

		$relationsToLoad = $this->resolveRelations($withRelations, $exceptRelations);

		if (!empty($relationsToLoad) && !empty($dbObjects))
		{
			$this->preloadRelations($dbObjects, $relationsToLoad);
		}

		$entities = [];
		foreach ($dbObjects as $dbObject)
		{
			$relations = $this->loadRelationsForObject($dbObject, $relationsToLoad);
			$entities[] = $this->buildEntity($dbObject, $relations);
		}

		return $entities;
	}

	/**
	 * Build the final entity from the database data and the loaded relations.
	 *
	 * @param object $dbObject  Standardised raw data
	 * @param array  $relations The relationships [relationship_name => value]
	 * @return mixed The entity
	 */
	abstract public function buildEntity(object $dbObject, array $relations): mixed;

	/**
	 * Retrieves an individual relation for a given object.
	 *
	 * @param string $relation Relation name
	 * @param object $dbObject Source object
	 * @return mixed The value of the relationship (entity, array, etc.)
	 */
	abstract protected function loadRelation(string $relation, object $dbObject): mixed;

	/**
	 * Returns the cache key for a given relation and object.
	 * E.g.: for the 'campaign' relation, return $dbObject->campaign_id
	 *
	 * @param string $relation Relation name
	 * @param object $dbObject Source object
	 * @return string|int Cache key
	 */
	abstract protected function getRelationCacheKey(string $relation, object $dbObject): string|int;

	/**
	 * Batch-load relations for a collection of objects.
	 * Override this method to optimize queries.
	 *
	 * @param array    $dbObjects        The complete collection of DB items
	 * @param string[] $relationsToLoad  Relations to be loaded
	 */
	protected function preloadRelations(array $dbObjects, array $relationsToLoad): void
	{
	}

	/**
	 * Resolves the list of relations to be loaded.
	 *
	 * @param bool|array $withRelations   true = all, false = none, array = explicit list
	 * @param array      $exceptRelations Relations to exclude
	 * @return string[]
	 */
	protected function resolveRelations(bool|array $withRelations, array $exceptRelations): array
	{
		if ($withRelations === false)
		{
			return [];
		}

		$relations = is_array($withRelations) ? $withRelations : static::RELATIONS;

		return array_values(array_diff($relations, $exceptRelations));
	}

	/**
	 * Loads all requested relations for an object,
	 * using the static cache.
	 *
	 * @param object   $dbObject
	 * @param string[] $relationsToLoad
	 *
	 * @return array [relation_name => value]
	 */
	private function loadRelationsForObject(object $dbObject, array $relationsToLoad): array
	{
		$relations = [];

		foreach ($relationsToLoad as $relation)
		{
			if (!$this->isSupportedRelation($relation))
			{
				continue;
			}


			$cacheKey = $this->getRelationCacheKey($relation, $dbObject);

			$cacheNs  = $this->getCacheNamespace($relation);

			$subConfig   = $this->getSubRelationConfig($relation);
			$subConfigKey = $this->buildSubConfigCacheKey($subConfig);
			$fullCacheKey = $cacheKey . $subConfigKey;

			$relations[$relation] = RelationCache::remember(
				$cacheNs,
				$fullCacheKey,
				fn() => $this->loadRelation($relation, $dbObject)
			);
		}
		
		return $relations;
	}

	/**
	 * Checks whether a relation is supported by this factory.
	 */
	private function isSupportedRelation(string $relation): bool
	{
		return in_array($relation, static::RELATIONS, true);
	}

	/**
	 * Returns the cache namespace for a relation.
	 *
	 *  Uses the relation name directly as the namespace.
	 *  This allows caches to be shared between factories:
	 *  - ApplicationChoicesFactory loads campaign id=5 → caches in "campaign.5"
	 *  - ApplicationFileFactory loads campaign id=5 → HIT in "campaign.5"
	 */
	private function getCacheNamespace(string $relation): string
	{
		return $relation;
	}

	/**
	 * Constructs a cache key suffix based on the sub-relation configuration.
	 * Allows entities loaded with different configurations to be distinguished.
	 *
	 */
	private function buildSubConfigCacheKey(array $subConfig): string
	{
		if ($subConfig['withRelations'] === true && empty($subConfig['exceptRelations']))
		{
			return '';
		}

		return '|' . md5(serialize($subConfig));
	}

	/**
	 * Converts the input into a stdClass object.
	 */
	private function normalizeToObject(object|array $data): object
	{
		return is_array($data) ? (object) $data : $data;
	}
}


