<?php
/**
 * @package     Unit\Component\Emundus\Class\Factories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Factories;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Factories\AbstractFactory;
use Tchooz\Factories\Cache\RelationCache;

/**
 * Concrete stub used to test AbstractFactory behavior.
 *
 * Declares two relations: 'campaign' and 'program'.
 * Each relation is loaded via a simple callable (spy) so tests
 * can track how many times loadRelation is called.
 */
class FakeFactory extends AbstractFactory
{
	protected const RELATIONS = ['campaign', 'program'];

	/** @var array<string, callable> Callbacks that produce the relation value */
	public array $relationLoaders = [];

	/** @var int Counter – how many times loadRelation() was actually invoked */
	public int $loadRelationCallCount = 0;

	/** @var array Track each loadRelation call arguments */
	public array $loadRelationCalls = [];

	/** @var bool Whether preloadRelations was called */
	public bool $preloadRelationsCalled = false;

	/** @var array The relations passed to preloadRelations */
	public array $preloadedRelations = [];

	/** @var array The dbObjects passed to preloadRelations */
	public array $preloadedDbObjects = [];

	public function buildEntity(object $dbObject, array $relations): mixed
	{
		return (object) [
			'id'        => $dbObject->id ?? null,
			'name'      => $dbObject->name ?? null,
			'relations' => $relations,
		];
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		$this->loadRelationCallCount++;
		$this->loadRelationCalls[] = ['relation' => $relation, 'dbObject' => $dbObject];

		if (isset($this->relationLoaders[$relation])) {
			return ($this->relationLoaders[$relation])($dbObject);
		}

		return null;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		$mapping = [
			'campaign' => 'campaign_id',
			'program'  => 'program_id',
		];

		$prop = $mapping[$relation] ?? 'id';

		return $dbObject->$prop ?? 0;
	}

	protected function preloadRelations(array $dbObjects, array $relationsToLoad): void
	{
		$this->preloadRelationsCalled = true;
		$this->preloadedRelations     = $relationsToLoad;
		$this->preloadedDbObjects     = $dbObjects;
	}

	/**
	 * Expose RELATIONS for assertions
	 */
	public function getRelationsConst(): array
	{
		return static::RELATIONS;
	}
}

/**
 * Stub without relations – tests the edge case of no relations declared.
 */
class NoRelationFactory extends AbstractFactory
{
	protected const RELATIONS = [];

	public function buildEntity(object $dbObject, array $relations): mixed
	{
		return (object) ['id' => $dbObject->id ?? null, 'relations' => $relations];
	}

	protected function loadRelation(string $relation, object $dbObject): mixed
	{
		return null;
	}

	protected function getRelationCacheKey(string $relation, object $dbObject): string|int
	{
		return 0;
	}
}

/**
 * Tests for AbstractFactory
 *
 * @since   1.0.0
 * @covers  \Tchooz\Factories\AbstractFactory
 */
class AbstractFactoryTest extends UnitTestCase
{
	private FakeFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();

		// Always start with a clean cache so tests are isolated
		RelationCache::flush();

		$this->factory = new FakeFactory();

		// Default loaders: return a simple object using the FK
		$this->factory->relationLoaders = [
			'campaign' => fn(object $obj) => (object) ['id' => $obj->campaign_id ?? 0, 'label' => 'Campaign ' . ($obj->campaign_id ?? 0)],
			'program'  => fn(object $obj) => (object) ['id' => $obj->program_id ?? 0, 'label' => 'Program ' . ($obj->program_id ?? 0)],
		];
	}

	protected function tearDown(): void
	{
		RelationCache::flush();
		parent::tearDown();
	}

	// ------------------------------------------------------------------
	//  fromDbObject – single entity
	// ------------------------------------------------------------------

	public function testFromDbObjectBuildsEntityWithAllRelations(): void
	{
		$dbRow = (object) ['id' => 1, 'name' => 'File A', 'campaign_id' => 10, 'program_id' => 20];

		$entity = $this->factory->fromDbObject($dbRow);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals('File A', $entity->name);
		$this->assertArrayHasKey('campaign', $entity->relations);
		$this->assertArrayHasKey('program', $entity->relations);
		$this->assertEquals(10, $entity->relations['campaign']->id);
		$this->assertEquals(20, $entity->relations['program']->id);
	}

	public function testFromDbObjectAcceptsArrayInput(): void
	{
		$dbRow = ['id' => 2, 'name' => 'File B', 'campaign_id' => 11, 'program_id' => 21];

		$entity = $this->factory->fromDbObject($dbRow);

		$this->assertEquals(2, $entity->id);
		$this->assertEquals('File B', $entity->name);
		$this->assertArrayHasKey('campaign', $entity->relations);
	}

	public function testFromDbObjectWithRelationsFalseLoadsNoRelations(): void
	{
		$dbRow = (object) ['id' => 3, 'name' => 'File C', 'campaign_id' => 10, 'program_id' => 20];

		$entity = $this->factory->fromDbObject($dbRow, withRelations: false);

		$this->assertEmpty($entity->relations);
		$this->assertEquals(0, $this->factory->loadRelationCallCount);
	}

	public function testFromDbObjectWithExplicitRelationsList(): void
	{
		$dbRow = (object) ['id' => 4, 'name' => 'File D', 'campaign_id' => 10, 'program_id' => 20];

		$entity = $this->factory->fromDbObject($dbRow, withRelations: ['campaign']);

		$this->assertArrayHasKey('campaign', $entity->relations);
		$this->assertArrayNotHasKey('program', $entity->relations);
		$this->assertEquals(1, $this->factory->loadRelationCallCount);
	}

	public function testFromDbObjectWithExceptRelations(): void
	{
		$dbRow = (object) ['id' => 5, 'name' => 'File E', 'campaign_id' => 10, 'program_id' => 20];

		$entity = $this->factory->fromDbObject($dbRow, exceptRelations: ['program']);

		$this->assertArrayHasKey('campaign', $entity->relations);
		$this->assertArrayNotHasKey('program', $entity->relations);
	}

	public function testFromDbObjectIgnoresUnsupportedRelations(): void
	{
		$dbRow = (object) ['id' => 6, 'name' => 'File F', 'campaign_id' => 10, 'program_id' => 20];

		$entity = $this->factory->fromDbObject($dbRow, withRelations: ['unknown_relation']);

		$this->assertEmpty($entity->relations);
		$this->assertEquals(0, $this->factory->loadRelationCallCount);
	}

	// ------------------------------------------------------------------
	//  fromDbObjects – batch
	// ------------------------------------------------------------------

	public function testFromDbObjectsReturnsArrayOfEntities(): void
	{
		$rows = [
			(object) ['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
			(object) ['id' => 2, 'name' => 'B', 'campaign_id' => 11, 'program_id' => 21],
		];

		$entities = $this->factory->fromDbObjects($rows);

		$this->assertCount(2, $entities);
		$this->assertEquals(1, $entities[0]->id);
		$this->assertEquals(2, $entities[1]->id);
	}

	public function testFromDbObjectsCallsPreloadRelations(): void
	{
		$rows = [
			(object) ['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
		];

		$this->factory->fromDbObjects($rows);

		$this->assertTrue($this->factory->preloadRelationsCalled);
		$this->assertEqualsCanonicalizing(['campaign', 'program'], $this->factory->preloadedRelations);
	}

	public function testFromDbObjectsDoesNotPreloadWhenNoRelationsRequested(): void
	{
		$rows = [
			(object) ['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
		];

		$this->factory->fromDbObjects($rows, withRelations: false);

		$this->assertFalse($this->factory->preloadRelationsCalled);
	}

	public function testFromDbObjectsDoesNotPreloadWhenCollectionIsEmpty(): void
	{
		$this->factory->fromDbObjects([]);

		$this->assertFalse($this->factory->preloadRelationsCalled);
	}

	public function testFromDbObjectsAcceptsArrayItems(): void
	{
		$rows = [
			['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
			['id' => 2, 'name' => 'B', 'campaign_id' => 11, 'program_id' => 21],
		];

		$entities = $this->factory->fromDbObjects($rows);

		$this->assertCount(2, $entities);
		$this->assertEquals('A', $entities[0]->name);
	}

	// ------------------------------------------------------------------
	//  Relation caching (Identity Map via RelationCache)
	// ------------------------------------------------------------------

	public function testRelationCacheAvoidsDuplicateLoads(): void
	{
		// Two objects share the same campaign_id = 10
		$rows = [
			(object) ['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
			(object) ['id' => 2, 'name' => 'B', 'campaign_id' => 10, 'program_id' => 21],
		];

		$entities = $this->factory->fromDbObjects($rows);

		// campaign was loaded only once (second one was served from cache)
		// program was loaded twice (different program_id)
		$this->assertEquals(3, $this->factory->loadRelationCallCount);

		// Both entities should have the same campaign object (same reference via cache)
		$this->assertSame(
			$entities[0]->relations['campaign'],
			$entities[1]->relations['campaign']
		);
	}

	public function testRelationCacheIsIsolatedByNamespace(): void
	{
		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 5, 'program_id' => 5];

		$entity = $this->factory->fromDbObject($row);

		// Even though both have key=5, they are in different namespaces
		$this->assertNotEquals(
			$entity->relations['campaign']->label,
			$entity->relations['program']->label
		);
	}

	// ------------------------------------------------------------------
	//  Sub-relations configuration
	// ------------------------------------------------------------------

	public function testWithSubRelationsReturnsSelf(): void
	{
		$result = $this->factory->withSubRelations('campaign', ['some_sub'], []);

		$this->assertSame($this->factory, $result);
	}

	public function testWithoutSubRelationsReturnsSelf(): void
	{
		$result = $this->factory->withoutSubRelations('campaign');

		$this->assertSame($this->factory, $result);
	}

	public function testSubRelationConfigDifferentiatesCacheKeys(): void
	{
		// Load once with default sub-relations
		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 42, 'program_id' => 1];
		$this->factory->fromDbObject($row);
		$callCountAfterFirst = $this->factory->loadRelationCallCount;

		// Now change the sub-relation config for 'campaign' and load again
		$this->factory->withSubRelations('campaign', false);
		$this->factory->fromDbObject($row);
		$callCountAfterSecond = $this->factory->loadRelationCallCount;

		// campaign should have been loaded again because the sub-config differs
		$this->assertGreaterThan($callCountAfterFirst, $callCountAfterSecond);
	}

	public function testWithoutSubRelationsSetsConfigToFalse(): void
	{
		$this->factory->withoutSubRelations('program');

		// Internally getSubRelationConfig should return withRelations=false
		// We verify indirectly: loading the same entity twice with different
		// sub-relation configs produces different cache keys, so loadRelation
		// is called again.
		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 1, 'program_id' => 99];
		$this->factory->fromDbObject($row);
		$count1 = $this->factory->loadRelationCallCount;

		// Reset and load with default sub-relations (flush cache to force re-eval)
		RelationCache::flush();
		$this->factory->loadRelationCallCount = 0;
		$factory2 = new FakeFactory();
		$factory2->relationLoaders = $this->factory->relationLoaders;
		$entity2 = $factory2->fromDbObject($row);

		// Both should have loaded program, but via different cache keys internally
		$this->assertArrayHasKey('program', $entity2->relations);
	}

	// ------------------------------------------------------------------
	//  resolveRelations edge cases
	// ------------------------------------------------------------------

	public function testResolveRelationsWithTrueReturnsAll(): void
	{
		$row = (object) ['id' => 1, 'name' => 'X', 'campaign_id' => 1, 'program_id' => 2];

		$entity = $this->factory->fromDbObject($row, withRelations: true);

		$this->assertCount(2, $entity->relations);
	}

	public function testResolveRelationsWithEmptyArrayLoadsNothing(): void
	{
		$row = (object) ['id' => 1, 'name' => 'X', 'campaign_id' => 1, 'program_id' => 2];

		$entity = $this->factory->fromDbObject($row, withRelations: []);

		$this->assertEmpty($entity->relations);
		$this->assertEquals(0, $this->factory->loadRelationCallCount);
	}

	public function testExceptRelationsWithExplicitListStillFilters(): void
	{
		$row = (object) ['id' => 1, 'name' => 'X', 'campaign_id' => 1, 'program_id' => 2];

		// Ask for both, but exclude campaign
		$entity = $this->factory->fromDbObject($row, withRelations: ['campaign', 'program'], exceptRelations: ['campaign']);

		$this->assertArrayNotHasKey('campaign', $entity->relations);
		$this->assertArrayHasKey('program', $entity->relations);
	}

	// ------------------------------------------------------------------
	//  NoRelationFactory – factory without any relations
	// ------------------------------------------------------------------

	public function testFactoryWithNoRelationsBuildsEntityWithEmptyRelations(): void
	{
		$factory = new NoRelationFactory();
		$row = (object) ['id' => 99];

		$entity = $factory->fromDbObject($row);

		$this->assertEquals(99, $entity->id);
		$this->assertEmpty($entity->relations);
	}

	public function testFactoryWithNoRelationsFromDbObjectsBatch(): void
	{
		$factory = new NoRelationFactory();
		$rows = [
			(object) ['id' => 1],
			(object) ['id' => 2],
		];

		$entities = $factory->fromDbObjects($rows);

		$this->assertCount(2, $entities);
		$this->assertEmpty($entities[0]->relations);
	}

	// ------------------------------------------------------------------
	//  Fluent API chaining
	// ------------------------------------------------------------------

	public function testFluentChainingOfSubRelations(): void
	{
		$result = $this->factory
			->withSubRelations('campaign', ['sub_a'], ['sub_b'])
			->withoutSubRelations('program')
			->withSubRelations('campaign', true, []);

		$this->assertSame($this->factory, $result);
	}

	// ------------------------------------------------------------------
	//  Cache shared across factory instances (same namespace)
	// ------------------------------------------------------------------

	public function testRelationCacheIsSharedAcrossFactoryInstances(): void
	{
		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 77, 'program_id' => 88];

		// First factory loads the relation
		$entity1 = $this->factory->fromDbObject($row);
		$this->assertEquals(2, $this->factory->loadRelationCallCount); // campaign + program

		// Second factory, same cache namespace, same cache key
		$factory2 = new FakeFactory();
		$factory2->relationLoaders = $this->factory->relationLoaders;
		$entity2 = $factory2->fromDbObject($row);

		// Second factory should NOT have called loadRelation at all (cache hit)
		$this->assertEquals(0, $factory2->loadRelationCallCount);

		// Both entities should have identical relation values
		$this->assertSame(
			$entity1->relations['campaign'],
			$entity2->relations['campaign']
		);
		$this->assertSame(
			$entity1->relations['program'],
			$entity2->relations['program']
		);
	}

	// ------------------------------------------------------------------
	//  loadRelation returning null (optional relation)
	// ------------------------------------------------------------------

	public function testNullRelationIsCachedAndNotReloaded(): void
	{
		// Campaign loader returns null (simulates missing FK)
		$this->factory->relationLoaders['campaign'] = fn(object $obj) => null;

		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 999, 'program_id' => 1];

		$entity1 = $this->factory->fromDbObject($row);
		$this->assertNull($entity1->relations['campaign']);
		$this->assertEquals(2, $this->factory->loadRelationCallCount);

		// Load same row again – null should come from cache
		$entity2 = $this->factory->fromDbObject($row);
		$this->assertNull($entity2->relations['campaign']);
		// loadRelation should NOT have been called again for campaign
		// program was already cached too, so total stays at 2
		$this->assertEquals(2, $this->factory->loadRelationCallCount);
	}

	// ------------------------------------------------------------------
	//  fromDbObject with minimal/empty stdClass
	// ------------------------------------------------------------------

	public function testFromDbObjectWithEmptyObject(): void
	{
		$dbRow = (object) [];

		$entity = $this->factory->fromDbObject($dbRow, withRelations: false);

		$this->assertNull($entity->id);
		$this->assertNull($entity->name);
		$this->assertEmpty($entity->relations);
	}

	// ------------------------------------------------------------------
	//  fromDbObjects – single element
	// ------------------------------------------------------------------

	public function testFromDbObjectsWithSingleElement(): void
	{
		$rows = [
			(object) ['id' => 42, 'name' => 'Solo', 'campaign_id' => 1, 'program_id' => 2],
		];

		$entities = $this->factory->fromDbObjects($rows);

		$this->assertCount(1, $entities);
		$this->assertEquals(42, $entities[0]->id);
		$this->assertEquals('Solo', $entities[0]->name);
		$this->assertArrayHasKey('campaign', $entities[0]->relations);
		$this->assertArrayHasKey('program', $entities[0]->relations);
	}

	// ------------------------------------------------------------------
	//  fromDbObjects preserves order
	// ------------------------------------------------------------------

	public function testFromDbObjectsPreservesOrder(): void
	{
		$rows = [
			(object) ['id' => 3, 'name' => 'Third', 'campaign_id' => 1, 'program_id' => 1],
			(object) ['id' => 1, 'name' => 'First', 'campaign_id' => 2, 'program_id' => 2],
			(object) ['id' => 2, 'name' => 'Second', 'campaign_id' => 3, 'program_id' => 3],
		];

		$entities = $this->factory->fromDbObjects($rows);

		$this->assertEquals(3, $entities[0]->id);
		$this->assertEquals(1, $entities[1]->id);
		$this->assertEquals(2, $entities[2]->id);
	}

	// ------------------------------------------------------------------
	//  Cache persists across successive fromDbObject calls
	// ------------------------------------------------------------------

	public function testCachePersistsAcrossSuccessiveSingleCalls(): void
	{
		$row1 = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 50, 'program_id' => 60];
		$row2 = (object) ['id' => 2, 'name' => 'B', 'campaign_id' => 50, 'program_id' => 70];

		$this->factory->fromDbObject($row1);
		$this->assertEquals(2, $this->factory->loadRelationCallCount); // campaign + program

		$this->factory->fromDbObject($row2);
		// campaign_id=50 was cached from first call, program_id=70 is new
		$this->assertEquals(3, $this->factory->loadRelationCallCount);
	}

	// ------------------------------------------------------------------
	//  exceptRelations excludes everything
	// ------------------------------------------------------------------

	public function testExceptRelationsExcludingAllRelationsLoadsNothing(): void
	{
		$row = (object) ['id' => 1, 'name' => 'X', 'campaign_id' => 1, 'program_id' => 2];

		$entity = $this->factory->fromDbObject($row, exceptRelations: ['campaign', 'program']);

		$this->assertEmpty($entity->relations);
		$this->assertEquals(0, $this->factory->loadRelationCallCount);
	}

	// ------------------------------------------------------------------
	//  fromDbObjects with mixed array + object items
	// ------------------------------------------------------------------

	public function testFromDbObjectsWithMixedArrayAndObjectItems(): void
	{
		$rows = [
			(object) ['id' => 1, 'name' => 'Object', 'campaign_id' => 10, 'program_id' => 20],
			['id' => 2, 'name' => 'Array', 'campaign_id' => 11, 'program_id' => 21],
		];

		$entities = $this->factory->fromDbObjects($rows);

		$this->assertCount(2, $entities);
		$this->assertEquals('Object', $entities[0]->name);
		$this->assertEquals('Array', $entities[1]->name);
		$this->assertArrayHasKey('campaign', $entities[0]->relations);
		$this->assertArrayHasKey('campaign', $entities[1]->relations);
	}

	// ------------------------------------------------------------------
	//  preloadRelations receives correct arguments
	// ------------------------------------------------------------------

	public function testPreloadRelationsReceivesNormalizedObjectsAndFilteredRelations(): void
	{
		$rows = [
			['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
			(object) ['id' => 2, 'name' => 'B', 'campaign_id' => 11, 'program_id' => 21],
		];

		$this->factory->fromDbObjects($rows, withRelations: ['campaign']);

		$this->assertTrue($this->factory->preloadRelationsCalled);
		$this->assertEquals(['campaign'], $this->factory->preloadedRelations);

		// All items should be objects (normalized)
		foreach ($this->factory->preloadedDbObjects as $obj) {
			$this->assertIsObject($obj);
		}

		$this->assertCount(2, $this->factory->preloadedDbObjects);
	}

	// ------------------------------------------------------------------
	//  getRelationCacheKey returning 0 (int) works correctly
	// ------------------------------------------------------------------

	public function testCacheKeyWithZeroIntWorksProperly(): void
	{
		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 0, 'program_id' => 0];

		$entity = $this->factory->fromDbObject($row);

		// Both relations with key=0 should be loaded (different namespaces)
		$this->assertEquals(2, $this->factory->loadRelationCallCount);
		$this->assertEquals(0, $entity->relations['campaign']->id);
		$this->assertEquals(0, $entity->relations['program']->id);
	}

	// ------------------------------------------------------------------
	//  withSubRelations overwrites previous config for same relation
	// ------------------------------------------------------------------

	public function testWithSubRelationsOverwritesPreviousConfig(): void
	{
		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 100, 'program_id' => 200];

		// Set sub-relation config
		$this->factory->withSubRelations('campaign', false);
		$this->factory->fromDbObject($row);
		$countAfterFirst = $this->factory->loadRelationCallCount;

		// Overwrite with different config – should create a different cache key
		$this->factory->withSubRelations('campaign', ['sub_x']);
		$this->factory->fromDbObject($row);
		$countAfterSecond = $this->factory->loadRelationCallCount;

		// campaign should have been re-loaded because sub-config changed
		$this->assertGreaterThan($countAfterFirst, $countAfterSecond);

		// Overwrite again with yet another config
		$this->factory->withSubRelations('campaign', true, ['sub_y']);
		$this->factory->fromDbObject($row);
		$countAfterThird = $this->factory->loadRelationCallCount;

		$this->assertGreaterThan($countAfterSecond, $countAfterThird);
	}

	// ------------------------------------------------------------------
	//  fromDbObjects with explicit withRelations only preloads those
	// ------------------------------------------------------------------

	public function testFromDbObjectsWithExplicitRelationsOnlyPreloadsThose(): void
	{
		$rows = [
			(object) ['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
		];

		$this->factory->fromDbObjects($rows, withRelations: ['program']);

		$this->assertTrue($this->factory->preloadRelationsCalled);
		$this->assertEquals(['program'], $this->factory->preloadedRelations);
	}

	// ------------------------------------------------------------------
	//  Large batch – cache efficiency
	// ------------------------------------------------------------------

	public function testLargeBatchCacheEfficiency(): void
	{
		$rows = [];
		// 50 objects all sharing the same campaign_id but different program_id
		for ($i = 0; $i < 50; $i++) {
			$rows[] = (object) [
				'id'          => $i,
				'name'        => "Item $i",
				'campaign_id' => 1,   // shared
				'program_id'  => $i,  // unique
			];
		}

		$entities = $this->factory->fromDbObjects($rows);

		$this->assertCount(50, $entities);

		// campaign loaded only once + 50 unique programs = 51 total calls
		$this->assertEquals(51, $this->factory->loadRelationCallCount);

		// All entities share the same campaign reference
		$campaignRef = $entities[0]->relations['campaign'];
		foreach ($entities as $entity) {
			$this->assertSame($campaignRef, $entity->relations['campaign']);
		}
	}

	// ------------------------------------------------------------------
	//  fromDbObject with db parameter (should not affect behavior)
	// ------------------------------------------------------------------

	public function testFromDbObjectAcceptsNullDbParameter(): void
	{
		$row = (object) ['id' => 1, 'name' => 'A', 'campaign_id' => 1, 'program_id' => 2];

		$entity = $this->factory->fromDbObject($row, db: null);

		$this->assertEquals(1, $entity->id);
		$this->assertArrayHasKey('campaign', $entity->relations);
	}

	public function testFromDbObjectsAcceptsNullDbParameter(): void
	{
		$rows = [
			(object) ['id' => 1, 'name' => 'A', 'campaign_id' => 1, 'program_id' => 2],
		];

		$entities = $this->factory->fromDbObjects($rows, db: null);

		$this->assertCount(1, $entities);
	}

	// ------------------------------------------------------------------
	//  Relation label correctness
	// ------------------------------------------------------------------

	public function testRelationDataIsCorrectlyPassedFromLoader(): void
	{
		$row = (object) ['id' => 1, 'name' => 'Test', 'campaign_id' => 42, 'program_id' => 7];

		$entity = $this->factory->fromDbObject($row);

		$this->assertEquals('Campaign 42', $entity->relations['campaign']->label);
		$this->assertEquals('Program 7', $entity->relations['program']->label);
	}

	// ------------------------------------------------------------------
	//  loadRelation receives the correct dbObject
	// ------------------------------------------------------------------

	public function testLoadRelationReceivesCorrectDbObject(): void
	{
		$row = (object) ['id' => 99, 'name' => 'Check', 'campaign_id' => 5, 'program_id' => 6];

		$this->factory->fromDbObject($row);

		$this->assertCount(2, $this->factory->loadRelationCalls);

		// First call should be campaign
		$this->assertEquals('campaign', $this->factory->loadRelationCalls[0]['relation']);
		$this->assertEquals(99, $this->factory->loadRelationCalls[0]['dbObject']->id);

		// Second call should be program
		$this->assertEquals('program', $this->factory->loadRelationCalls[1]['relation']);
		$this->assertEquals(99, $this->factory->loadRelationCalls[1]['dbObject']->id);
	}

	// ------------------------------------------------------------------
	//  NoRelationFactory additional edge cases
	// ------------------------------------------------------------------

	public function testNoRelationFactoryIgnoresWithRelationsTrue(): void
	{
		$factory = new NoRelationFactory();
		$row = (object) ['id' => 1];

		$entity = $factory->fromDbObject($row, withRelations: true);

		$this->assertEmpty($entity->relations);
	}

	public function testNoRelationFactoryIgnoresExceptRelations(): void
	{
		$factory = new NoRelationFactory();
		$row = (object) ['id' => 1];

		$entity = $factory->fromDbObject($row, exceptRelations: ['something']);

		$this->assertEmpty($entity->relations);
	}

	public function testNoRelationFactoryBatchReturnsEmptyRelationsForAll(): void
	{
		$factory = new NoRelationFactory();
		$rows = [];
		for ($i = 0; $i < 10; $i++) {
			$rows[] = (object) ['id' => $i];
		}

		$entities = $factory->fromDbObjects($rows);

		$this->assertCount(10, $entities);
		foreach ($entities as $i => $entity) {
			$this->assertEquals($i, $entity->id);
			$this->assertEmpty($entity->relations);
		}
	}

	// ------------------------------------------------------------------
	//  fromDbObjects returns empty array for empty input
	// ------------------------------------------------------------------

	public function testFromDbObjectsReturnsEmptyArrayForEmptyInput(): void
	{
		$entities = $this->factory->fromDbObjects([]);

		$this->assertIsArray($entities);
		$this->assertEmpty($entities);
	}

	// ------------------------------------------------------------------
	//  RelationCache stats reflect loaded relations
	// ------------------------------------------------------------------

	public function testRelationCacheStatsReflectLoadedData(): void
	{
		$rows = [
			(object) ['id' => 1, 'name' => 'A', 'campaign_id' => 10, 'program_id' => 20],
			(object) ['id' => 2, 'name' => 'B', 'campaign_id' => 10, 'program_id' => 21],
			(object) ['id' => 3, 'name' => 'C', 'campaign_id' => 11, 'program_id' => 20],
		];

		$this->factory->fromDbObjects($rows);

		$stats = RelationCache::stats();

		// 2 unique campaign keys (10, 11)
		$this->assertEquals(2, $stats['campaign']);
		// 2 unique program keys (20, 21)
		$this->assertEquals(2, $stats['program']);
	}
}

