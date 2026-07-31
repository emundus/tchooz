<?php

namespace Unit\Component\Emundus\Class\Factories\Groups;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\Cache\RelationCache;
use Tchooz\Factories\Groups\GroupFactory;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Groups
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Groups\GroupFactory
 */
class GroupFactoryTest extends UnitTestCase
{
	private GroupFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		RelationCache::flush();
		$this->factory = new GroupFactory();
	}

	protected function tearDown(): void
	{
		RelationCache::flush();
		parent::tearDown();
	}

	/**
	 * Injects a mock repository into the factory via reflection.
	 */
	private function injectRepository(GroupFactory $factory, string $propertyName, object $mock): void
	{
		$ref = new \ReflectionClass($factory);
		$prop = $ref->getProperty($propertyName);
		$prop->setAccessible(true);
		$prop->setValue($factory, $mock);
	}

	private function createDbObject(array $overrides = []): object
	{
		return (object) array_merge([
			'id'                  => 1,
			'label'               => 'Test Group',
			'description'         => 'A test group',
			'published'           => 1,
			'class'               => 'label-blue-2',
			'anonymize'           => 0,
			'filter_status'       => 0,
			'programs'            => '',
			'statuses'            => '',
			'visible_groups'      => '',
			'visible_attachments' => '',
		], $overrides);
	}

	// =====================
	// buildEntity tests
	// =====================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityReturnsGroupEntity(): void
	{
		$dbObject = $this->createDbObject();

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertInstanceOf(GroupEntity::class, $entity);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsIdCorrectly(): void
	{
		$dbObject = $this->createDbObject(['id' => 42]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals(42, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsLabelCorrectly(): void
	{
		$dbObject = $this->createDbObject(['label' => 'Evaluateurs']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals('Evaluateurs', $entity->getLabel());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsDescriptionCorrectly(): void
	{
		$dbObject = $this->createDbObject(['description' => 'Groupe des évaluateurs']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals('Groupe des évaluateurs', $entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsDescriptionDefaultsToEmptyString(): void
	{
		$dbObject = $this->createDbObject();
		unset($dbObject->description);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals('', $entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsPublishedTrueCorrectly(): void
	{
		$dbObject = $this->createDbObject(['published' => 1]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertTrue($entity->isPublished());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsPublishedFalseCorrectly(): void
	{
		$dbObject = $this->createDbObject(['published' => 0]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertFalse($entity->isPublished());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsAnonymizeTrueCorrectly(): void
	{
		$dbObject = $this->createDbObject(['anonymize' => 1]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertTrue($entity->isAnonymize());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsAnonymizeFalseCorrectly(): void
	{
		$dbObject = $this->createDbObject(['anonymize' => 0]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertFalse($entity->isAnonymize());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsFilterStatusTrueCorrectly(): void
	{
		$dbObject = $this->createDbObject(['filter_status' => 1]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertTrue($entity->isFilterStatus());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsFilterStatusFalseCorrectly(): void
	{
		$dbObject = $this->createDbObject(['filter_status' => 0]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertFalse($entity->isFilterStatus());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsClassCorrectly(): void
	{
		$dbObject = $this->createDbObject(['class' => 'label-red-1']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals('label-red-1', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityClassDefaultsWhenMissing(): void
	{
		$dbObject = $this->createDbObject();
		unset($dbObject->class);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals('label-blue-2', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithoutRepositoriesReturnsEmptyPrograms(): void
	{
		$dbObject = $this->createDbObject(['programs' => 'PROG1,PROG2']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEmpty($entity->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithoutRepositoriesReturnsEmptyStatuses(): void
	{
		$dbObject = $this->createDbObject(['statuses' => '1,2']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEmpty($entity->getStatuses());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithEmptyProgramsReturnsEmptyArray(): void
	{
		$dbObject = $this->createDbObject(['programs' => '']);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->never())->method('getItemsByFields');

		$entity = $this->factory->buildEntity($dbObject, [], $programRepository);

		$this->assertEmpty($entity->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithEmptyStatusesReturnsEmptyArray(): void
	{
		$dbObject = $this->createDbObject(['statuses' => '']);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->never())->method('getItemsByFields');

		$entity = $this->factory->buildEntity($dbObject, [], null, $statusRepository);

		$this->assertEmpty($entity->getStatuses());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithProgramRepositoryLoadsPrograms(): void
	{
		$dbObject = $this->createDbObject(['programs' => 'PROG1,PROG2']);

		$mockProgram1 = $this->createMock(ProgramEntity::class);
		$mockProgram2 = $this->createMock(ProgramEntity::class);

		$entity = $this->factory->buildEntity($dbObject, [ProgramRepository::NAME => [$mockProgram1, $mockProgram2]]);

		$this->assertCount(2, $entity->getPrograms());
		$this->assertSame($mockProgram1, $entity->getPrograms()[0]);
		$this->assertSame($mockProgram2, $entity->getPrograms()[1]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithStatusRepositoryLoadsStatuses(): void
	{
		$dbObject = $this->createDbObject(['statuses' => '1,2']);

		$mockStatus1 = $this->createMock(StatusEntity::class);
		$mockStatus2 = $this->createMock(StatusEntity::class);

		$entity = $this->factory->buildEntity($dbObject, [StatusRepository::NAME => [$mockStatus1, $mockStatus2]]);

		$this->assertCount(2, $entity->getStatuses());
		$this->assertSame($mockStatus1, $entity->getStatuses()[0]);
		$this->assertSame($mockStatus2, $entity->getStatuses()[1]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityParsesVisibleGroupsCorrectly(): void
	{
		$dbObject = $this->createDbObject(['visible_groups' => '1,2,3']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals([1, 2, 3], $entity->getVisibleGroups());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleGroupsFiltersZeroValues(): void
	{
		$dbObject = $this->createDbObject(['visible_groups' => '1,0,3']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals([1, 3], $entity->getVisibleGroups());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleGroupsEmptyReturnsEmptyArray(): void
	{
		$dbObject = $this->createDbObject(['visible_groups' => '']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEmpty($entity->getVisibleGroups());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityParsesVisibleAttachmentsCorrectly(): void
	{
		$dbObject = $this->createDbObject(['visible_attachments' => '10,20,30']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals([10, 20, 30], $entity->getVisibleAttachments());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleAttachmentsFiltersZeroValues(): void
	{
		$dbObject = $this->createDbObject(['visible_attachments' => '5,0,15']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals([5, 15], $entity->getVisibleAttachments());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleAttachmentsEmptyReturnsEmptyArray(): void
	{
		$dbObject = $this->createDbObject(['visible_attachments' => '']);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEmpty($entity->getVisibleAttachments());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsAllPropertiesCorrectly(): void
	{
		$dbObject = $this->createDbObject([
			'id'                  => 99,
			'label'               => 'Full Group',
			'description'         => 'Full description',
			'published'           => 1,
			'class'               => 'label-green-3',
			'anonymize'           => 1,
			'filter_status'       => 1,
			'programs'            => '',
			'statuses'            => '',
			'visible_groups'      => '5,10',
			'visible_attachments' => '20,30',
		]);

		$entity = $this->factory->buildEntity($dbObject, []);

		$this->assertEquals(99, $entity->getId());
		$this->assertEquals('Full Group', $entity->getLabel());
		$this->assertEquals('Full description', $entity->getDescription());
		$this->assertTrue($entity->isPublished());
		$this->assertEquals('label-green-3', $entity->getClass());
		$this->assertTrue($entity->isAnonymize());
		$this->assertTrue($entity->isFilterStatus());
		$this->assertEmpty($entity->getPrograms());
		$this->assertEmpty($entity->getStatuses());
		$this->assertEquals([5, 10], $entity->getVisibleGroups());
		$this->assertEquals([20, 30], $entity->getVisibleAttachments());
	}

	// =====================
	// fromDbObject tests
	// =====================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObject
	 */
	public function testFromDbObjectWithObject(): void
	{
		$dbObject = $this->createDbObject(['id' => 7]);

		$entity = $this->factory->fromDbObject($dbObject, false);

		$this->assertInstanceOf(GroupEntity::class, $entity);
		$this->assertEquals(7, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObject
	 */
	public function testFromDbObjectWithArray(): void
	{
		$dbArray = [
			'id'                  => 3,
			'label'               => 'Array Group',
			'description'         => 'From array',
			'published'           => 0,
			'class'               => 'label-red-1',
			'anonymize'           => 1,
			'filter_status'       => 0,
			'programs'            => '',
			'statuses'            => '',
			'visible_groups'      => '',
			'visible_attachments' => '',
		];

		$entity = $this->factory->fromDbObject($dbArray, false);

		$this->assertInstanceOf(GroupEntity::class, $entity);
		$this->assertEquals(3, $entity->getId());
		$this->assertEquals('Array Group', $entity->getLabel());
		$this->assertEquals('From array', $entity->getDescription());
		$this->assertFalse($entity->isPublished());
		$this->assertEquals('label-red-1', $entity->getClass());
		$this->assertTrue($entity->isAnonymize());
		$this->assertFalse($entity->isFilterStatus());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObject
	 */
	public function testFromDbObjectWithoutRelationsDoesNotLoadPrograms(): void
	{
		$dbObject = $this->createDbObject(['programs' => 'PROG1']);

		$entity = $this->factory->fromDbObject($dbObject, false);

		$this->assertEmpty($entity->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObject
	 */
	public function testFromDbObjectWithRelationsInstantiatesRepositories(): void
	{
		$dbObject = $this->createDbObject([
			'id'                  => 50,
			'programs'            => '',
			'statuses'            => '',
			'visible_groups'      => '',
			'visible_attachments' => '',
		]);

		$entity = $this->factory->fromDbObject($dbObject, true);

		$this->assertInstanceOf(GroupEntity::class, $entity);
		$this->assertEquals(50, $entity->getId());
	}

	// =====================
	// fromDbObjects tests
	// =====================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithEmptyArrayReturnsEmptyArray(): void
	{
		$entities = $this->factory->fromDbObjects([], false);

		$this->assertIsArray($entities);
		$this->assertEmpty($entities);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithoutRelationsReturnsEntities(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 1, 'label' => 'Group 1']),
			$this->createDbObject(['id' => 2, 'label' => 'Group 2']),
			$this->createDbObject(['id' => 3, 'label' => 'Group 3']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, false);

		$this->assertCount(3, $entities);
		foreach ($entities as $entity) {
			$this->assertInstanceOf(GroupEntity::class, $entity);
		}
		$this->assertEquals(1, $entities[0]->getId());
		$this->assertEquals('Group 1', $entities[0]->getLabel());
		$this->assertEquals(2, $entities[1]->getId());
		$this->assertEquals('Group 2', $entities[1]->getLabel());
		$this->assertEquals(3, $entities[2]->getId());
		$this->assertEquals('Group 3', $entities[2]->getLabel());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObjects
	 */
	public function testFromDbObjectsPreservesOrderAndData(): void
	{
		$dbObjects = [
			$this->createDbObject([
				'id'                  => 10,
				'label'               => 'Premier',
				'description'         => 'Desc 1',
				'published'           => 1,
				'anonymize'           => 0,
				'filter_status'       => 1,
				'visible_groups'      => '1,2',
				'visible_attachments' => '3,4',
			]),
			$this->createDbObject([
				'id'                  => 20,
				'label'               => 'Deuxième',
				'description'         => 'Desc 2',
				'published'           => 0,
				'anonymize'           => 1,
				'filter_status'       => 0,
				'visible_groups'      => '',
				'visible_attachments' => '',
			]),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, false);

		$this->assertEquals(10, $entities[0]->getId());
		$this->assertEquals('Premier', $entities[0]->getLabel());
		$this->assertEquals('Desc 1', $entities[0]->getDescription());
		$this->assertTrue($entities[0]->isPublished());
		$this->assertFalse($entities[0]->isAnonymize());
		$this->assertTrue($entities[0]->isFilterStatus());
		$this->assertEquals([1, 2], $entities[0]->getVisibleGroups());
		$this->assertEquals([3, 4], $entities[0]->getVisibleAttachments());

		$this->assertEquals(20, $entities[1]->getId());
		$this->assertEquals('Deuxième', $entities[1]->getLabel());
		$this->assertEquals('Desc 2', $entities[1]->getDescription());
		$this->assertFalse($entities[1]->isPublished());
		$this->assertTrue($entities[1]->isAnonymize());
		$this->assertFalse($entities[1]->isFilterStatus());
		$this->assertEmpty($entities[1]->getVisibleGroups());
		$this->assertEmpty($entities[1]->getVisibleAttachments());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithSingleElement(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 99, 'label' => 'Unique']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, false);

		$this->assertCount(1, $entities);
		$this->assertEquals(99, $entities[0]->getId());
		$this->assertEquals('Unique', $entities[0]->getLabel());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithRelationsInstantiatesRepositories(): void
	{
		$dbObjects = [
			$this->createDbObject([
				'id'                  => 1,
				'programs'            => '',
				'statuses'            => '',
				'visible_groups'      => '',
				'visible_attachments' => '',
			]),
			$this->createDbObject([
				'id'                  => 2,
				'programs'            => '',
				'statuses'            => '',
				'visible_groups'      => '',
				'visible_attachments' => '',
			]),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, true);

		$this->assertCount(2, $entities);
		foreach ($entities as $entity) {
			$this->assertInstanceOf(GroupEntity::class, $entity);
		}
		$this->assertEquals(1, $entities[0]->getId());
		$this->assertEquals(2, $entities[1]->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithRelationsAndExistingPrograms(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('code')
			->from($db->quoteName('#__emundus_setup_programmes'))
			->where($db->quoteName('published') . ' = 1')
			->setLimit(1);
		$db->setQuery($query);
		$programCode = $db->loadResult();

		if (empty($programCode)) {
			$this->markTestSkipped('No published program found in the database');
		}

		$dbObjects = [
			$this->createDbObject([
				'id'       => 1,
				'programs' => $programCode,
			]),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, true);

		$this->assertCount(1, $entities);
		$this->assertNotEmpty($entities[0]->getPrograms(), 'Programs should be loaded when withRelations is true and program code exists');
		$this->assertInstanceOf(ProgramEntity::class, $entities[0]->getPrograms()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithRelationsAndExistingStatuses(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('step')
			->from($db->quoteName('#__emundus_setup_status'))
			->setLimit(2);
		$db->setQuery($query);
		$statusStep = $db->loadColumn();

		if ($statusStep === null) {
			$this->markTestSkipped('No status found in the database');
		}

		$dbObjects = [
			$this->createDbObject([
				'id'       => 1,
				'statuses' => implode(',', $statusStep),
			]),
		];

		$entities = $this->factory->fromDbObjects($dbObjects);

		$this->assertCount(1, $entities);
		$this->assertNotEmpty($entities[0]->getStatuses(), 'Statuses should be loaded when withRelations is true and status step exists');
		$this->assertInstanceOf(StatusEntity::class, $entities[0]->getStatuses()[0]);
	}

	// ===========================================
	// buildEntity – scalar relation wrapping tests
	// ===========================================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWrapsScalarProgramRelationInArray(): void
	{
		$dbObject = $this->createDbObject(['programs' => 'PROG1']);
		$mockProgram = $this->createMock(ProgramEntity::class);

		$entity = $this->factory->buildEntity($dbObject, [ProgramRepository::NAME => $mockProgram]);

		$this->assertCount(1, $entity->getPrograms());
		$this->assertSame($mockProgram, $entity->getPrograms()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWrapsScalarStatusRelationInArray(): void
	{
		$dbObject = $this->createDbObject(['statuses' => '1']);
		$mockStatus = $this->createMock(StatusEntity::class);

		$entity = $this->factory->buildEntity($dbObject, [StatusRepository::NAME => $mockStatus]);

		$this->assertCount(1, $entity->getStatuses());
		$this->assertSame($mockStatus, $entity->getStatuses()[0]);
	}

	// ======================================
	// loadRelation tests (via fromDbObject)
	// ======================================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationProgramWithEmptyPrograms(): void
	{
		$dbObject = $this->createDbObject(['programs' => '']);

		$entity = $this->factory->fromDbObject($dbObject, [ProgramRepository::NAME]);

		$this->assertEmpty($entity->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationProgramCallsRepositoryGetByCode(): void
	{
		$mockProgram = $this->createMock(ProgramEntity::class);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->once())
			->method('getByCode')
			->with('PROG1')
			->willReturn($mockProgram);

		$this->injectRepository($this->factory, 'programRepository', $programRepository);

		$dbObject = $this->createDbObject(['programs' => 'PROG1']);
		$entity = $this->factory->fromDbObject($dbObject, [ProgramRepository::NAME]);

		$this->assertCount(1, $entity->getPrograms());
		$this->assertSame($mockProgram, $entity->getPrograms()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationProgramWithMultipleCodes(): void
	{
		$mockProgram1 = $this->createMock(ProgramEntity::class);
		$mockProgram2 = $this->createMock(ProgramEntity::class);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->exactly(2))
			->method('getByCode')
			->willReturnCallback(function (string $code) use ($mockProgram1, $mockProgram2) {
				return $code === 'PROG1' ? $mockProgram1 : $mockProgram2;
			});

		$this->injectRepository($this->factory, 'programRepository', $programRepository);

		$dbObject = $this->createDbObject(['programs' => 'PROG1,PROG2']);
		$entity = $this->factory->fromDbObject($dbObject, [ProgramRepository::NAME]);

		$this->assertCount(2, $entity->getPrograms());
		$this->assertSame($mockProgram1, $entity->getPrograms()[0]);
		$this->assertSame($mockProgram2, $entity->getPrograms()[1]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationProgramUsesRelationCacheWhenAvailable(): void
	{
		$mockProgram = $this->createMock(ProgramEntity::class);
		RelationCache::set(ProgramRepository::NAME, 'CACHED_PROG', $mockProgram);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->never())->method('getByCode');

		$this->injectRepository($this->factory, 'programRepository', $programRepository);

		$dbObject = $this->createDbObject(['programs' => 'CACHED_PROG']);
		$entity = $this->factory->fromDbObject($dbObject, [ProgramRepository::NAME]);

		$this->assertCount(1, $entity->getPrograms());
		$this->assertSame($mockProgram, $entity->getPrograms()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationStatusWithEmptyStatus(): void
	{
		$dbObject = $this->createDbObject(['status' => '', 'statuses' => '']);

		$entity = $this->factory->fromDbObject($dbObject, [StatusRepository::NAME]);

		$this->assertEmpty($entity->getStatuses());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationStatusCallsRepositoryGetByStep(): void
	{
		$mockStatus = $this->createMock(StatusEntity::class);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->once())
			->method('getByStep')
			->with(1)
			->willReturn($mockStatus);

		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObject = $this->createDbObject(['status' => '1', 'statuses' => '1']);
		$entity = $this->factory->fromDbObject($dbObject, [StatusRepository::NAME]);

		$this->assertCount(1, $entity->getStatuses());
		$this->assertSame($mockStatus, $entity->getStatuses()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationStatusWithMultipleSteps(): void
	{
		$mockStatus1 = $this->createMock(StatusEntity::class);
		$mockStatus2 = $this->createMock(StatusEntity::class);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->exactly(2))
			->method('getByStep')
			->willReturnCallback(function (int $step) use ($mockStatus1, $mockStatus2) {
				return $step === 1 ? $mockStatus1 : $mockStatus2;
			});

		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObject = $this->createDbObject(['status' => '1,2', 'statuses' => '1,2']);
		$entity = $this->factory->fromDbObject($dbObject, [StatusRepository::NAME]);

		$this->assertCount(2, $entity->getStatuses());
		$this->assertSame($mockStatus1, $entity->getStatuses()[0]);
		$this->assertSame($mockStatus2, $entity->getStatuses()[1]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationStatusUsesRelationCacheWhenAvailable(): void
	{
		$mockStatus = $this->createMock(StatusEntity::class);
		RelationCache::set(StatusRepository::NAME, '5', $mockStatus);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->never())->method('getByStep');

		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObject = $this->createDbObject(['status' => '5', 'statuses' => '5']);
		$entity = $this->factory->fromDbObject($dbObject, [StatusRepository::NAME]);

		$this->assertCount(1, $entity->getStatuses());
		$this->assertSame($mockStatus, $entity->getStatuses()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testLoadRelationReturnsNullForUnknownRelation(): void
	{
		$dbObject = $this->createDbObject();

		// Use fromDbObject with an unsupported relation name — the AbstractFactory skips unsupported relations
		// so the entity should have empty programs and statuses
		$entity = $this->factory->fromDbObject($dbObject, ['unknown_relation']);

		$this->assertInstanceOf(GroupEntity::class, $entity);
		$this->assertEmpty($entity->getPrograms());
		$this->assertEmpty($entity->getStatuses());
	}

	// =========================================
	// getRelationCacheKey tests (via fromDbObjects)
	// =========================================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::getRelationCacheKey
	 */
	public function testGetRelationCacheKeyForPrograms(): void
	{
		$mockProgram = $this->createMock(ProgramEntity::class);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->method('getByCode')->willReturn($mockProgram);

		$this->injectRepository($this->factory, 'programRepository', $programRepository);

		$dbObject1 = $this->createDbObject(['id' => 1, 'programs' => 'PROG1']);
		$dbObject2 = $this->createDbObject(['id' => 2, 'programs' => 'PROG1']);

		// Both objects share the same programs string, so the cache key is the same.
		// The repository should only be called once because the second object hits the cache.
		$programRepository->expects($this->once())->method('getByCode');

		$entities = $this->factory->fromDbObjects([$dbObject1, $dbObject2], [ProgramRepository::NAME]);

		$this->assertCount(2, $entities);
		$this->assertCount(1, $entities[0]->getPrograms());
		$this->assertCount(1, $entities[1]->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::getRelationCacheKey
	 */
	public function testGetRelationCacheKeyForStatuses(): void
	{
		$mockStatus = $this->createMock(StatusEntity::class);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->method('getByStep')->willReturn($mockStatus);

		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObject1 = $this->createDbObject(['id' => 1, 'status' => '1', 'statuses' => '1']);
		$dbObject2 = $this->createDbObject(['id' => 2, 'status' => '1', 'statuses' => '1']);

		$statusRepository->expects($this->once())->method('getByStep');

		$entities = $this->factory->fromDbObjects([$dbObject1, $dbObject2], [StatusRepository::NAME]);

		$this->assertCount(2, $entities);
		$this->assertCount(1, $entities[0]->getStatuses());
		$this->assertCount(1, $entities[1]->getStatuses());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::getRelationCacheKey
	 */
	public function testGetRelationCacheKeyDefaultsToEmptyStringWhenFieldMissing(): void
	{
		$dbObject = $this->createDbObject(['id' => 1]);
		unset($dbObject->programs);
		unset($dbObject->statuses);

		// Should not fail — defaults to '' for missing fields
		$entity = $this->factory->fromDbObject($dbObject, [ProgramRepository::NAME]);

		$this->assertInstanceOf(GroupEntity::class, $entity);
	}

	// =========================================
	// preloadRelations tests (via fromDbObjects)
	// =========================================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::preloadRelations
	 */
	public function testPreloadRelationsProgramsCallsRepositoryOncePerUniqueCode(): void
	{
		$mockProgram1 = $this->createMock(ProgramEntity::class);
		$mockProgram2 = $this->createMock(ProgramEntity::class);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->exactly(2))
			->method('getByCode')
			->willReturnCallback(function (string $code) use ($mockProgram1, $mockProgram2) {
				return $code === 'PROG1' ? $mockProgram1 : $mockProgram2;
			});

		$this->injectRepository($this->factory, 'programRepository', $programRepository);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'programs' => 'PROG1,PROG2']),
			$this->createDbObject(['id' => 2, 'programs' => 'PROG1']),
			$this->createDbObject(['id' => 3, 'programs' => 'PROG2']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, [ProgramRepository::NAME]);

		$this->assertCount(3, $entities);
		// PROG1 and PROG2 should each be loaded only once via preload
		$this->assertCount(2, $entities[0]->getPrograms());
		$this->assertCount(1, $entities[1]->getPrograms());
		$this->assertCount(1, $entities[2]->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::preloadRelations
	 */
	public function testPreloadRelationsStatusesCallsRepositoryOncePerUniqueStep(): void
	{
		$mockStatus1 = $this->createMock(StatusEntity::class);
		$mockStatus2 = $this->createMock(StatusEntity::class);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->exactly(2))
			->method('getByStep')
			->willReturnCallback(function (int $step) use ($mockStatus1, $mockStatus2) {
				return $step === 1 ? $mockStatus1 : $mockStatus2;
			});

		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'status' => '1,2', 'statuses' => '1,2']),
			$this->createDbObject(['id' => 2, 'status' => '1', 'statuses' => '1']),
			$this->createDbObject(['id' => 3, 'status' => '2', 'statuses' => '2']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, [StatusRepository::NAME]);

		$this->assertCount(3, $entities);
		$this->assertCount(2, $entities[0]->getStatuses());
		$this->assertCount(1, $entities[1]->getStatuses());
		$this->assertCount(1, $entities[2]->getStatuses());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::preloadRelations
	 */
	public function testPreloadRelationsSkipsAlreadyCachedPrograms(): void
	{
		$mockProgram = $this->createMock(ProgramEntity::class);
		RelationCache::set(ProgramRepository::NAME, 'CACHED', $mockProgram);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->never())->method('getByCode');

		$this->injectRepository($this->factory, 'programRepository', $programRepository);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'programs' => 'CACHED']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, [ProgramRepository::NAME]);

		$this->assertCount(1, $entities);
		$this->assertCount(1, $entities[0]->getPrograms());
		$this->assertSame($mockProgram, $entities[0]->getPrograms()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::preloadRelations
	 */
	public function testPreloadRelationsSkipsAlreadyCachedStatuses(): void
	{
		$mockStatus = $this->createMock(StatusEntity::class);
		RelationCache::set(StatusRepository::NAME, '10', $mockStatus);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->never())->method('getByStep');

		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'status' => '10', 'statuses' => '10']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, [StatusRepository::NAME]);

		$this->assertCount(1, $entities);
		$this->assertCount(1, $entities[0]->getStatuses());
		$this->assertSame($mockStatus, $entities[0]->getStatuses()[0]);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::preloadRelations
	 */
	public function testPreloadRelationsWithEmptyProgramsDoesNotCallRepository(): void
	{
		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->never())->method('getByCode');

		$this->injectRepository($this->factory, 'programRepository', $programRepository);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'programs' => '']),
			$this->createDbObject(['id' => 2, 'programs' => '']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, [ProgramRepository::NAME]);

		$this->assertCount(2, $entities);
		$this->assertEmpty($entities[0]->getPrograms());
		$this->assertEmpty($entities[1]->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::preloadRelations
	 */
	public function testPreloadRelationsWithEmptyStatusesDoesNotCallRepository(): void
	{
		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->never())->method('getByStep');

		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'status' => '', 'statuses' => '']),
			$this->createDbObject(['id' => 2, 'status' => '', 'statuses' => '']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, [StatusRepository::NAME]);

		$this->assertCount(2, $entities);
		$this->assertEmpty($entities[0]->getStatuses());
		$this->assertEmpty($entities[1]->getStatuses());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::preloadRelations
	 * @covers \Tchooz\Factories\Groups\GroupFactory::loadRelation
	 */
	public function testPreloadRelationsBothProgramsAndStatuses(): void
	{
		$mockProgram = $this->createMock(ProgramEntity::class);
		$mockStatus = $this->createMock(StatusEntity::class);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->once())
			->method('getByCode')
			->with('P1')
			->willReturn($mockProgram);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->once())
			->method('getByStep')
			->with(3)
			->willReturn($mockStatus);

		$this->injectRepository($this->factory, 'programRepository', $programRepository);
		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'programs' => 'P1', 'status' => '3', 'statuses' => '3']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects, true);

		$this->assertCount(1, $entities);
		$this->assertCount(1, $entities[0]->getPrograms());
		$this->assertSame($mockProgram, $entities[0]->getPrograms()[0]);
		$this->assertCount(1, $entities[0]->getStatuses());
		$this->assertSame($mockStatus, $entities[0]->getStatuses()[0]);
	}

	// ======================================================
	// fromDbObject with exceptRelations tests
	// ======================================================

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObject
	 */
	public function testFromDbObjectWithExceptRelationsExcludesPrograms(): void
	{
		$mockStatus = $this->createMock(StatusEntity::class);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->method('getByStep')->willReturn($mockStatus);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->never())->method('getByCode');

		$this->injectRepository($this->factory, 'programRepository', $programRepository);
		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObject = $this->createDbObject(['programs' => 'PROG1', 'status' => '1', 'statuses' => '1']);

		$entity = $this->factory->fromDbObject($dbObject, true, [ProgramRepository::NAME]);

		$this->assertEmpty($entity->getPrograms());
		$this->assertCount(1, $entity->getStatuses());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::fromDbObject
	 */
	public function testFromDbObjectWithExceptRelationsExcludesStatuses(): void
	{
		$mockProgram = $this->createMock(ProgramEntity::class);

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->method('getByCode')->willReturn($mockProgram);

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->never())->method('getByStep');

		$this->injectRepository($this->factory, 'programRepository', $programRepository);
		$this->injectRepository($this->factory, 'statusRepository', $statusRepository);

		$dbObject = $this->createDbObject(['programs' => 'PROG1', 'status' => '1', 'statuses' => '1']);

		$entity = $this->factory->fromDbObject($dbObject, true, [StatusRepository::NAME]);

		$this->assertCount(1, $entity->getPrograms());
		$this->assertEmpty($entity->getStatuses());
	}
}

