<?php

namespace Unit\Component\Emundus\Class\Factories\ApplicationFile;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Factories\ApplicationFile\StatusFactory;

/**
 * @package     Unit\Component\Emundus\Class\Factories\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\ApplicationFile\StatusFactory
 */
class StatusFactoryTest extends UnitTestCase
{
	private StatusFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		$this->factory = new StatusFactory();
	}

	private function createDbObject(array $overrides = []): object
	{
		return (object) array_merge([
			'id'       => 1,
			'step'     => 1,
			'value'    => 'En cours',
			'ordering' => 0,
			'class'    => '#FF0000',
		], $overrides);
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testBuildEntityReturnsStatusEntity(): void
	{
		$dbObject = $this->createDbObject();

		$entity = StatusFactory::buildEntity($dbObject);

		$this->assertInstanceOf(StatusEntity::class, $entity);
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testBuildEntityMapsIdCorrectly(): void
	{
		$dbObject = $this->createDbObject(['id' => 42]);

		$entity = StatusFactory::buildEntity($dbObject);

		$this->assertEquals(42, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testBuildEntityMapsStepCorrectly(): void
	{
		$dbObject = $this->createDbObject(['step' => 3]);

		$entity = StatusFactory::buildEntity($dbObject);

		$this->assertEquals(3, $entity->getStep());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testBuildEntityMapsLabelFromValueField(): void
	{
		$dbObject = $this->createDbObject(['value' => 'Validé']);

		$entity = StatusFactory::buildEntity($dbObject);

		$this->assertEquals('Validé', $entity->getLabel());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testBuildEntityMapsOrderingCorrectly(): void
	{
		$dbObject = $this->createDbObject(['ordering' => 5]);

		$entity = StatusFactory::buildEntity($dbObject);

		$this->assertEquals(5, $entity->getOrdering());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testBuildEntityMapsColorFromClassField(): void
	{
		$dbObject = $this->createDbObject(['class' => '#00FF00']);

		$entity = StatusFactory::buildEntity($dbObject);

		$this->assertEquals('#00FF00', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::buildEntity
	 */
	public function testBuildEntityMapsAllPropertiesCorrectly(): void
	{
		$dbObject = $this->createDbObject([
			'id'       => 10,
			'step'     => 2,
			'value'    => 'Refusé',
			'ordering' => 3,
			'class'    => '#0000FF',
		]);

		$entity = StatusFactory::buildEntity($dbObject);

		$this->assertEquals(10, $entity->getId());
		$this->assertEquals(2, $entity->getStep());
		$this->assertEquals('Refusé', $entity->getLabel());
		$this->assertEquals(3, $entity->getOrdering());
		$this->assertEquals('#0000FF', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObject
	 */
	public function testFromDbObjectWithObject(): void
	{
		$dbObject = $this->createDbObject(['id' => 7]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(StatusEntity::class, $entity);
		$this->assertEquals(7, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObject
	 */
	public function testFromDbObjectWithArray(): void
	{
		$dbArray = [
			'id'       => 3,
			'step'     => 1,
			'value'    => 'Brouillon',
			'ordering' => 1,
			'class'    => '#CCCCCC',
		];

		$entity = $this->factory->fromDbObject($dbArray);

		$this->assertInstanceOf(StatusEntity::class, $entity);
		$this->assertEquals(3, $entity->getId());
		$this->assertEquals(1, $entity->getStep());
		$this->assertEquals('Brouillon', $entity->getLabel());
		$this->assertEquals(1, $entity->getOrdering());
		$this->assertEquals('#CCCCCC', $entity->getColor());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObject
	 */
	public function testFromDbObjectWithArrayConvertsToObject(): void
	{
		$dbArray = [
			'id'       => 5,
			'step'     => 2,
			'value'    => 'Test',
			'ordering' => 0,
			'class'    => '#000000',
		];

		$entity = $this->factory->fromDbObject($dbArray);

		$this->assertInstanceOf(StatusEntity::class, $entity);
		$this->assertEquals(5, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithEmptyArrayReturnsEmptyArray(): void
	{
		$entities = StatusFactory::fromDbObjects([]);

		$this->assertIsArray($entities);
		$this->assertEmpty($entities);
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObjects
	 */
	public function testFromDbObjectsReturnsEntities(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 1, 'value' => 'En cours']),
			$this->createDbObject(['id' => 2, 'value' => 'Validé']),
			$this->createDbObject(['id' => 3, 'value' => 'Refusé']),
		];

		$entities = StatusFactory::fromDbObjects($dbObjects);

		$this->assertCount(3, $entities);
		foreach ($entities as $entity) {
			$this->assertInstanceOf(StatusEntity::class, $entity);
		}
		$this->assertEquals(1, $entities[0]->getId());
		$this->assertEquals(2, $entities[1]->getId());
		$this->assertEquals(3, $entities[2]->getId());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObjects
	 */
	public function testFromDbObjectsPreservesOrderAndData(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 10, 'step' => 1, 'value' => 'Premier', 'ordering' => 1, 'class' => '#111111']),
			$this->createDbObject(['id' => 20, 'step' => 2, 'value' => 'Deuxième', 'ordering' => 2, 'class' => '#222222']),
		];

		$entities = StatusFactory::fromDbObjects($dbObjects);

		$this->assertEquals(10, $entities[0]->getId());
		$this->assertEquals(1, $entities[0]->getStep());
		$this->assertEquals('Premier', $entities[0]->getLabel());
		$this->assertEquals(1, $entities[0]->getOrdering());
		$this->assertEquals('#111111', $entities[0]->getColor());

		$this->assertEquals(20, $entities[1]->getId());
		$this->assertEquals(2, $entities[1]->getStep());
		$this->assertEquals('Deuxième', $entities[1]->getLabel());
		$this->assertEquals(2, $entities[1]->getOrdering());
		$this->assertEquals('#222222', $entities[1]->getColor());
	}

	/**
	 * @covers \Tchooz\Factories\ApplicationFile\StatusFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithSingleElement(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 99, 'value' => 'Unique']),
		];

		$entities = StatusFactory::fromDbObjects($dbObjects);

		$this->assertCount(1, $entities);
		$this->assertEquals(99, $entities[0]->getId());
		$this->assertEquals('Unique', $entities[0]->getLabel());
	}
}

