<?php

namespace Unit\Component\Emundus\Class\Factories\Actions;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Enums\Actions\ActionTypeEnum;
use Tchooz\Factories\Actions\GroupAccessFactory;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Groups\GroupRepository;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Actions\GroupAccessFactory
 */
class GroupAccessFactoryTest extends UnitTestCase
{
	private GroupAccessFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		$this->factory = new GroupAccessFactory();
	}

	private function createDbObject(array $overrides = []): object
	{
		return (object) array_merge([
			'id'        => 1,
			'group_id'  => 10,
			'action_id' => 20,
			'c'         => 1,
			'r'         => 1,
			'u'         => 0,
			'd'         => 0,
		], $overrides);
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::buildEntity
	 */
	public function testBuildEntityReturnsGroupAccessEntity(): void
	{
		$dbObject = $this->createDbObject();

		$entity = GroupAccessFactory::buildEntity($dbObject);

		$this->assertInstanceOf(GroupAccessEntity::class, $entity);
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::buildEntity
	 */
	public function testBuildEntityWithoutRepositoriesReturnsNullGroupAndAction(): void
	{
		$dbObject = $this->createDbObject();

		$entity = GroupAccessFactory::buildEntity($dbObject);

		$this->assertNull($entity->getGroup());
		$this->assertNull($entity->getAction());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::buildEntity
	 */
	public function testBuildEntityMapsIdCorrectly(): void
	{
		$dbObject = $this->createDbObject(['id' => 42]);

		$entity = GroupAccessFactory::buildEntity($dbObject);

		$this->assertEquals(42, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::buildEntity
	 */
	public function testBuildEntityMapsCrudCorrectly(): void
	{
		$dbObject = $this->createDbObject([
			'c' => 1,
			'r' => 0,
			'u' => 1,
			'd' => 1,
		]);

		$entity = GroupAccessFactory::buildEntity($dbObject);
		$crud   = $entity->getCrud();

		$this->assertInstanceOf(CrudEntity::class, $crud);
		$this->assertEquals(0, $crud->getMulti());
		$this->assertEquals(1, $crud->getCreate());
		$this->assertEquals(0, $crud->getRead());
		$this->assertEquals(1, $crud->getUpdate());
		$this->assertEquals(1, $crud->getDelete());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::buildEntity
	 */
	public function testBuildEntityWithGroupRepository(): void
	{
		$dbObject = $this->createDbObject(['group_id' => 10]);

		$mockGroup = $this->createMock(GroupEntity::class);

		$groupRepository = $this->createMock(GroupRepository::class);
		$groupRepository->expects($this->once())
			->method('getById')
			->with(10)
			->willReturn($mockGroup);

		$entity = GroupAccessFactory::buildEntity($dbObject, $groupRepository);

		$this->assertSame($mockGroup, $entity->getGroup());
		$this->assertNull($entity->getAction());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::buildEntity
	 */
	public function testBuildEntityWithActionRepository(): void
	{
		$dbObject = $this->createDbObject(['action_id' => 20]);

		$mockAction = $this->createMock(ActionEntity::class);

		$actionRepository = $this->createMock(ActionRepository::class);
		$actionRepository->expects($this->once())
			->method('getById')
			->with(20)
			->willReturn($mockAction);

		$entity = GroupAccessFactory::buildEntity($dbObject, null, $actionRepository);

		$this->assertNull($entity->getGroup());
		$this->assertSame($mockAction, $entity->getAction());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::buildEntity
	 */
	public function testBuildEntityWithBothRepositories(): void
	{
		$dbObject = $this->createDbObject(['group_id' => 5, 'action_id' => 15]);

		$mockGroup  = $this->createMock(GroupEntity::class);
		$mockAction = $this->createMock(ActionEntity::class);

		$groupRepository = $this->createMock(GroupRepository::class);
		$groupRepository->expects($this->once())
			->method('getById')
			->with(5)
			->willReturn($mockGroup);

		$actionRepository = $this->createMock(ActionRepository::class);
		$actionRepository->expects($this->once())
			->method('getById')
			->with(15)
			->willReturn($mockAction);

		$entity = GroupAccessFactory::buildEntity($dbObject, $groupRepository, $actionRepository);

		$this->assertSame($mockGroup, $entity->getGroup());
		$this->assertSame($mockAction, $entity->getAction());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObject
	 */
	public function testFromDbObjectWithObject(): void
	{
		$dbObject = $this->createDbObject(['id' => 7]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(GroupAccessEntity::class, $entity);
		$this->assertEquals(7, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObject
	 */
	public function testFromDbObjectWithArray(): void
	{
		$dbArray = [
			'id'        => 3,
			'group_id'  => 10,
			'action_id' => 20,
			'c'         => 0,
			'r'         => 1,
			'u'         => 0,
			'd'         => 1,
		];

		$entity = $this->factory->fromDbObject($dbArray);

		$this->assertInstanceOf(GroupAccessEntity::class, $entity);
		$this->assertEquals(3, $entity->getId());
		$this->assertEquals(0, $entity->getCrud()->getCreate());
		$this->assertEquals(1, $entity->getCrud()->getRead());
		$this->assertEquals(0, $entity->getCrud()->getUpdate());
		$this->assertEquals(1, $entity->getCrud()->getDelete());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObject
	 */
	public function testFromDbObjectWithoutRelationsReturnsNullGroupAndAction(): void
	{
		$dbObject = $this->createDbObject();

		$entity = $this->factory->fromDbObject($dbObject, false);

		$this->assertNull($entity->getGroup());
		$this->assertNull($entity->getAction());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithEmptyArrayReturnsEmptyArray(): void
	{
		$entities = GroupAccessFactory::fromDbObjects([], false);

		$this->assertIsArray($entities);
		$this->assertEmpty($entities);
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithoutRelationsReturnsEntities(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 1]),
			$this->createDbObject(['id' => 2]),
			$this->createDbObject(['id' => 3]),
		];

		$entities = GroupAccessFactory::fromDbObjects($dbObjects, false);

		$this->assertCount(3, $entities);
		foreach ($entities as $entity) {
			$this->assertInstanceOf(GroupAccessEntity::class, $entity);
			$this->assertNull($entity->getGroup());
			$this->assertNull($entity->getAction());
		}
		$this->assertEquals(1, $entities[0]->getId());
		$this->assertEquals(2, $entities[1]->getId());
		$this->assertEquals(3, $entities[2]->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObjects
	 */
	public function testFromDbObjectsPreservesOrderAndCrudData(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 10, 'c' => 1, 'r' => 1, 'u' => 1, 'd' => 1]),
			$this->createDbObject(['id' => 20, 'c' => 0, 'r' => 0, 'u' => 0, 'd' => 0]),
		];

		$entities = GroupAccessFactory::fromDbObjects($dbObjects, false);

		$this->assertEquals(10, $entities[0]->getId());
		$this->assertEquals(1, $entities[0]->getCrud()->getCreate());
		$this->assertEquals(1, $entities[0]->getCrud()->getRead());
		$this->assertEquals(1, $entities[0]->getCrud()->getUpdate());
		$this->assertEquals(1, $entities[0]->getCrud()->getDelete());

		$this->assertEquals(20, $entities[1]->getId());
		$this->assertEquals(0, $entities[1]->getCrud()->getCreate());
		$this->assertEquals(0, $entities[1]->getCrud()->getRead());
		$this->assertEquals(0, $entities[1]->getCrud()->getUpdate());
		$this->assertEquals(0, $entities[1]->getCrud()->getDelete());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithRelationsInstantiatesRepositories(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_groups'))
			->setLimit(1);
		$db->setQuery($query);
		$groupId = (int) $db->loadResult();

		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_actions'))
			->setLimit(1);
		$db->setQuery($query);
		$actionId = (int) $db->loadResult();

		$this->assertGreaterThan(0, $groupId, 'A group must exist in the database for this test');
		$this->assertGreaterThan(0, $actionId, 'An action must exist in the database for this test');

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'group_id' => $groupId, 'action_id' => $actionId, 'c' => 1, 'r' => 1, 'u' => 0, 'd' => 0]),
		];

		$entities = GroupAccessFactory::fromDbObjects($dbObjects, true);

		$this->assertCount(1, $entities);
		$this->assertInstanceOf(GroupAccessEntity::class, $entities[0]);
		$this->assertNotNull($entities[0]->getGroup(), 'Group should be loaded when withRelations is true');
		$this->assertNotNull($entities[0]->getAction(), 'Action should be loaded when withRelations is true');
		$this->assertInstanceOf(GroupEntity::class, $entities[0]->getGroup());
		$this->assertInstanceOf(ActionEntity::class, $entities[0]->getAction());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\GroupAccessFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithRelationsAndMultipleEntities(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_groups'))
			->setLimit(1);
		$db->setQuery($query);
		$groupId = (int) $db->loadResult();

		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_actions'))
			->setLimit(1);
		$db->setQuery($query);
		$actionId = (int) $db->loadResult();

		$this->assertGreaterThan(0, $groupId);
		$this->assertGreaterThan(0, $actionId);

		$dbObjects = [
			$this->createDbObject(['id' => 1, 'group_id' => $groupId, 'action_id' => $actionId, 'c' => 1, 'r' => 1, 'u' => 1, 'd' => 1]),
			$this->createDbObject(['id' => 2, 'group_id' => $groupId, 'action_id' => $actionId, 'c' => 0, 'r' => 0, 'u' => 0, 'd' => 0]),
		];

		$entities = GroupAccessFactory::fromDbObjects($dbObjects);

		$this->assertCount(2, $entities);

		foreach ($entities as $entity) {
			$this->assertInstanceOf(GroupAccessEntity::class, $entity);
			$this->assertNotNull($entity->getGroup());
			$this->assertNotNull($entity->getAction());
		}

		$this->assertEquals(1, $entities[0]->getId());
		$this->assertEquals(2, $entities[1]->getId());
	}
}