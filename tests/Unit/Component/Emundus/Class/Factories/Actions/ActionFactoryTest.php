<?php

namespace Unit\Component\Emundus\Class\Factories\Actions;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Enums\Actions\ActionTypeEnum;
use Tchooz\Factories\Actions\ActionFactory;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Actions\ActionFactory
 */
class ActionFactoryTest extends UnitTestCase
{
	private ActionFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		$this->factory = new ActionFactory();
	}

	private function createDbObject(array $overrides = []): object
	{
		return (object) array_merge([
			'id'          => 1,
			'name'        => 'test_action',
			'label'       => 'Test Action',
			'multi'       => 0,
			'c'           => 1,
			'r'           => 1,
			'u'           => 1,
			'd'           => 0,
			'ordering'    => 5,
			'status'      => 1,
			'description' => 'A test action description',
			'type'        => 'file',
		], $overrides);
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityReturnsActionEntity(): void
	{
		$dbObject = $this->createDbObject();

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertInstanceOf(ActionEntity::class, $entity);
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityMapsScalarFieldsCorrectly(): void
	{
		$dbObject = $this->createDbObject([
			'id'          => 42,
			'name'        => 'my_action',
			'label'       => 'My Action Label',
			'ordering'    => 10,
			'status'      => 1,
			'description' => 'Some description',
		]);

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertEquals(42, $entity->getId());
		$this->assertEquals('my_action', $entity->getName());
		$this->assertEquals('My Action Label', $entity->getLabel());
		$this->assertEquals(10, $entity->getOrdering());
		$this->assertTrue($entity->isStatus());
		$this->assertEquals('Some description', $entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityMapsCrudCorrectly(): void
	{
		$dbObject = $this->createDbObject([
			'multi' => 1,
			'c'     => 1,
			'r'     => 0,
			'u'     => 1,
			'd'     => 0,
		]);

		$entity = ActionFactory::buildEntity($dbObject);
		$crud   = $entity->getCrud();

		$this->assertInstanceOf(CrudEntity::class, $crud);
		$this->assertEquals(1, $crud->getMulti());
		$this->assertEquals(1, $crud->getCreate());
		$this->assertEquals(0, $crud->getRead());
		$this->assertEquals(1, $crud->getUpdate());
		$this->assertEquals(0, $crud->getDelete());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityMapsTypeFile(): void
	{
		$dbObject = $this->createDbObject(['type' => 'file']);

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertEquals(ActionTypeEnum::FILE, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityMapsTypePlatform(): void
	{
		$dbObject = $this->createDbObject(['type' => 'platform']);

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertEquals(ActionTypeEnum::PLATFORM, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityMapsTypeUsers(): void
	{
		$dbObject = $this->createDbObject(['type' => 'users']);

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertEquals(ActionTypeEnum::USERS, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityWithUnknownTypeFallsBackToFile(): void
	{
		$dbObject = $this->createDbObject(['type' => 'unknown_type']);

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertEquals(ActionTypeEnum::FILE, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityWithNullDescription(): void
	{
		$dbObject = $this->createDbObject();
		unset($dbObject->description);

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertNull($entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::buildEntity
	 */
	public function testBuildEntityWithStatusFalse(): void
	{
		$dbObject = $this->createDbObject(['status' => 0]);

		$entity = ActionFactory::buildEntity($dbObject);

		$this->assertFalse($entity->isStatus());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::fromDbObject
	 */
	public function testFromDbObjectWithObject(): void
	{
		$dbObject = $this->createDbObject([
			'id'   => 7,
			'name' => 'object_action',
		]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(ActionEntity::class, $entity);
		$this->assertEquals(7, $entity->getId());
		$this->assertEquals('object_action', $entity->getName());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::fromDbObject
	 */
	public function testFromDbObjectWithArray(): void
	{
		$dbArray = [
			'id'          => 3,
			'name'        => 'array_action',
			'label'       => 'Array Action',
			'multi'       => 0,
			'c'           => 0,
			'r'           => 1,
			'u'           => 0,
			'd'           => 0,
			'ordering'    => 2,
			'status'      => 1,
			'description' => 'From array',
			'type'        => 'platform',
		];

		$entity = $this->factory->fromDbObject($dbArray);

		$this->assertInstanceOf(ActionEntity::class, $entity);
		$this->assertEquals(3, $entity->getId());
		$this->assertEquals('array_action', $entity->getName());
		$this->assertEquals('Array Action', $entity->getLabel());
		$this->assertEquals(ActionTypeEnum::PLATFORM, $entity->getType());
		$this->assertEquals('From array', $entity->getDescription());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::fromDbObjects
	 */
	public function testFromDbObjectsReturnsArrayOfEntities(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 1, 'name' => 'action_1']),
			$this->createDbObject(['id' => 2, 'name' => 'action_2']),
			$this->createDbObject(['id' => 3, 'name' => 'action_3']),
		];

		$entities = ActionFactory::fromDbObjects($dbObjects);

		$this->assertCount(3, $entities);
		foreach ($entities as $entity) {
			$this->assertInstanceOf(ActionEntity::class, $entity);
		}
		$this->assertEquals(1, $entities[0]->getId());
		$this->assertEquals('action_1', $entities[0]->getName());
		$this->assertEquals(2, $entities[1]->getId());
		$this->assertEquals('action_2', $entities[1]->getName());
		$this->assertEquals(3, $entities[2]->getId());
		$this->assertEquals('action_3', $entities[2]->getName());
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithEmptyArrayReturnsEmptyArray(): void
	{
		$entities = ActionFactory::fromDbObjects([]);

		$this->assertIsArray($entities);
		$this->assertEmpty($entities);
	}

	/**
	 * @covers \Tchooz\Factories\Actions\ActionFactory::fromDbObjects
	 */
	public function testFromDbObjectsPreservesOrderAndData(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 10, 'name' => 'first', 'type' => 'file', 'ordering' => 1]),
			$this->createDbObject(['id' => 20, 'name' => 'second', 'type' => 'platform', 'ordering' => 2]),
			$this->createDbObject(['id' => 30, 'name' => 'third', 'type' => 'users', 'ordering' => 3]),
		];

		$entities = ActionFactory::fromDbObjects($dbObjects);

		$this->assertEquals(10, $entities[0]->getId());
		$this->assertEquals(ActionTypeEnum::FILE, $entities[0]->getType());
		$this->assertEquals(1, $entities[0]->getOrdering());

		$this->assertEquals(20, $entities[1]->getId());
		$this->assertEquals(ActionTypeEnum::PLATFORM, $entities[1]->getType());
		$this->assertEquals(2, $entities[1]->getOrdering());

		$this->assertEquals(30, $entities[2]->getId());
		$this->assertEquals(ActionTypeEnum::USERS, $entities[2]->getType());
		$this->assertEquals(3, $entities[2]->getOrdering());
	}
}