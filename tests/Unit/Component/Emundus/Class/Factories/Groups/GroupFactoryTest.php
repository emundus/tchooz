<?php

namespace Unit\Component\Emundus\Class\Factories\Groups;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Factories\Groups\GroupFactory;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;
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
		$this->factory = new GroupFactory();
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

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertInstanceOf(GroupEntity::class, $entity);
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsIdCorrectly(): void
	{
		$dbObject = $this->createDbObject(['id' => 42]);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals(42, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsLabelCorrectly(): void
	{
		$dbObject = $this->createDbObject(['label' => 'Evaluateurs']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals('Evaluateurs', $entity->getLabel());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsDescriptionCorrectly(): void
	{
		$dbObject = $this->createDbObject(['description' => 'Groupe des évaluateurs']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals('Groupe des évaluateurs', $entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsDescriptionDefaultsToEmptyString(): void
	{
		$dbObject = $this->createDbObject();
		unset($dbObject->description);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals('', $entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsPublishedTrueCorrectly(): void
	{
		$dbObject = $this->createDbObject(['published' => 1]);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertTrue($entity->isPublished());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsPublishedFalseCorrectly(): void
	{
		$dbObject = $this->createDbObject(['published' => 0]);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertFalse($entity->isPublished());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsAnonymizeTrueCorrectly(): void
	{
		$dbObject = $this->createDbObject(['anonymize' => 1]);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertTrue($entity->isAnonymize());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsAnonymizeFalseCorrectly(): void
	{
		$dbObject = $this->createDbObject(['anonymize' => 0]);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertFalse($entity->isAnonymize());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsFilterStatusTrueCorrectly(): void
	{
		$dbObject = $this->createDbObject(['filter_status' => 1]);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertTrue($entity->isFilterStatus());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsFilterStatusFalseCorrectly(): void
	{
		$dbObject = $this->createDbObject(['filter_status' => 0]);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertFalse($entity->isFilterStatus());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityMapsClassCorrectly(): void
	{
		$dbObject = $this->createDbObject(['class' => 'label-red-1']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals('label-red-1', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityClassDefaultsWhenMissing(): void
	{
		$dbObject = $this->createDbObject();
		unset($dbObject->class);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals('label-blue-2', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithoutRepositoriesReturnsEmptyPrograms(): void
	{
		$dbObject = $this->createDbObject(['programs' => 'PROG1,PROG2']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEmpty($entity->getPrograms());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityWithoutRepositoriesReturnsEmptyStatuses(): void
	{
		$dbObject = $this->createDbObject(['statuses' => '1,2']);

		$entity = GroupFactory::buildEntity($dbObject);

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

		$entity = GroupFactory::buildEntity($dbObject, $programRepository);

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

		$entity = GroupFactory::buildEntity($dbObject, null, $statusRepository);

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

		$programRepository = $this->createMock(ProgramRepository::class);
		$programRepository->expects($this->once())
			->method('getItemsByFields')
			->with(['code' => ['PROG1', 'PROG2']], true)
			->willReturn([$mockProgram1, $mockProgram2]);

		$entity = GroupFactory::buildEntity($dbObject, $programRepository);

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

		$statusRepository = $this->createMock(StatusRepository::class);
		$statusRepository->expects($this->once())
			->method('getItemsByFields')
			->with(['step' => ['1', '2']], true)
			->willReturn([$mockStatus1, $mockStatus2]);

		$entity = GroupFactory::buildEntity($dbObject, null, $statusRepository);

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

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals([1, 2, 3], $entity->getVisibleGroups());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleGroupsFiltersZeroValues(): void
	{
		$dbObject = $this->createDbObject(['visible_groups' => '1,0,3']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals([1, 3], $entity->getVisibleGroups());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleGroupsEmptyReturnsEmptyArray(): void
	{
		$dbObject = $this->createDbObject(['visible_groups' => '']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEmpty($entity->getVisibleGroups());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityParsesVisibleAttachmentsCorrectly(): void
	{
		$dbObject = $this->createDbObject(['visible_attachments' => '10,20,30']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals([10, 20, 30], $entity->getVisibleAttachments());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleAttachmentsFiltersZeroValues(): void
	{
		$dbObject = $this->createDbObject(['visible_attachments' => '5,0,15']);

		$entity = GroupFactory::buildEntity($dbObject);

		$this->assertEquals([5, 15], $entity->getVisibleAttachments());
	}

	/**
	 * @covers \Tchooz\Factories\Groups\GroupFactory::buildEntity
	 */
	public function testBuildEntityVisibleAttachmentsEmptyReturnsEmptyArray(): void
	{
		$dbObject = $this->createDbObject(['visible_attachments' => '']);

		$entity = GroupFactory::buildEntity($dbObject);

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

		$entity = GroupFactory::buildEntity($dbObject);

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
		$entities = GroupFactory::fromDbObjects([], false);

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

		$entities = GroupFactory::fromDbObjects($dbObjects, false);

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

		$entities = GroupFactory::fromDbObjects($dbObjects, false);

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

		$entities = GroupFactory::fromDbObjects($dbObjects, false);

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

		$entities = GroupFactory::fromDbObjects($dbObjects, true);

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

		$entities = GroupFactory::fromDbObjects($dbObjects, true);

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

		$entities = GroupFactory::fromDbObjects($dbObjects, true);

		$this->assertCount(1, $entities);
		$this->assertNotEmpty($entities[0]->getStatuses(), 'Statuses should be loaded when withRelations is true and status step exists');
		$this->assertInstanceOf(StatusEntity::class, $entities[0]->getStatuses()[0]);
	}
}

