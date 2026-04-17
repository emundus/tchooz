<?php
/**
 * @package     Unit\Component\Emundus\Class\Factories\Label
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Factories\Label;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Factories\Label\LabelFactory;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Label
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Label\LabelFactory
 */
class LabelFactoryTest extends UnitTestCase
{
	private LabelFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		$this->factory = new LabelFactory();
	}

	private function createDbObject(array $overrides = []): object
	{
		return (object) array_merge([
			'id'       => 1,
			'label'    => 'Test Label',
			'class'    => 'label-blue-1',
			'ordering' => 0,
		], $overrides);
	}

	// =====================
	// fromDbObject tests
	// =====================

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectWithObjectReturnsLabelEntity(): void
	{
		$dbObject = $this->createDbObject();

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(LabelEntity::class, $entity);
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectWithArrayReturnsLabelEntity(): void
	{
		$dbArray = [
			'id'       => 5,
			'label'    => 'Array Label',
			'class'    => 'label-red-1',
			'ordering' => 2,
		];

		$entity = $this->factory->fromDbObject($dbArray);

		$this->assertInstanceOf(LabelEntity::class, $entity);
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectWithArrayMapsAllFields(): void
	{
		$dbArray = [
			'id'       => 5,
			'label'    => 'Array Label',
			'class'    => 'label-red-1',
			'ordering' => 2,
		];

		$entity = $this->factory->fromDbObject($dbArray);

		$this->assertEquals(5, $entity->getId());
		$this->assertEquals('Array Label', $entity->getLabel());
		$this->assertEquals('label-red-1', $entity->getClass());
		$this->assertEquals(2, $entity->getOrdering());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectMapsIdCorrectly(): void
	{
		$dbObject = $this->createDbObject(['id' => 42]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals(42, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectMapsLabelCorrectly(): void
	{
		$dbObject = $this->createDbObject(['label' => 'Mon label']);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals('Mon label', $entity->getLabel());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectMapsClassCorrectly(): void
	{
		$dbObject = $this->createDbObject(['class' => 'label-green-2']);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals('label-green-2', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectMapsOrderingCorrectly(): void
	{
		$dbObject = $this->createDbObject(['ordering' => 5]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals(5, $entity->getOrdering());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectLabelDefaultsToEmptyStringWhenMissing(): void
	{
		$dbObject = $this->createDbObject();
		$dbObject->label = null;

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals('', $entity->getLabel());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectClassDefaultsToEmptyStringWhenMissing(): void
	{
		$dbObject = $this->createDbObject();
		unset($dbObject->class);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals('', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectOrderingDefaultsToZeroWhenMissing(): void
	{
		$dbObject = $this->createDbObject();
		unset($dbObject->ordering);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals(0, $entity->getOrdering());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObject
	 */
	public function testFromDbObjectMapsAllPropertiesCorrectly(): void
	{
		$dbObject = $this->createDbObject([
			'id'       => 99,
			'label'    => 'Full Label',
			'class'    => 'label-purple-3',
			'ordering' => 10,
		]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertEquals(99, $entity->getId());
		$this->assertEquals('Full Label', $entity->getLabel());
		$this->assertEquals('label-purple-3', $entity->getClass());
		$this->assertEquals(10, $entity->getOrdering());
	}

	// =====================
	// fromDbObjects tests
	// =====================

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithEmptyArrayReturnsEmptyArray(): void
	{
		$entities = $this->factory->fromDbObjects([]);

		$this->assertIsArray($entities);
		$this->assertEmpty($entities);
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObjects
	 */
	public function testFromDbObjectsReturnsArrayOfLabelEntities(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 1, 'label' => 'Label 1']),
			$this->createDbObject(['id' => 2, 'label' => 'Label 2']),
			$this->createDbObject(['id' => 3, 'label' => 'Label 3']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects);

		$this->assertCount(3, $entities);
		foreach ($entities as $entity) {
			$this->assertInstanceOf(LabelEntity::class, $entity);
		}
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObjects
	 */
	public function testFromDbObjectsPreservesOrderAndData(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 10, 'label' => 'Premier',  'class' => 'label-blue-1',  'ordering' => 1]),
			$this->createDbObject(['id' => 20, 'label' => 'Deuxième', 'class' => 'label-red-2',   'ordering' => 2]),
		];

		$entities = $this->factory->fromDbObjects($dbObjects);

		$this->assertEquals(10, $entities[0]->getId());
		$this->assertEquals('Premier', $entities[0]->getLabel());
		$this->assertEquals('label-blue-1', $entities[0]->getClass());
		$this->assertEquals(1, $entities[0]->getOrdering());

		$this->assertEquals(20, $entities[1]->getId());
		$this->assertEquals('Deuxième', $entities[1]->getLabel());
		$this->assertEquals('label-red-2', $entities[1]->getClass());
		$this->assertEquals(2, $entities[1]->getOrdering());
	}

	/**
	 * @covers \Tchooz\Factories\Label\LabelFactory::fromDbObjects
	 */
	public function testFromDbObjectsWithSingleElementReturnsOneEntity(): void
	{
		$dbObjects = [
			$this->createDbObject(['id' => 99, 'label' => 'Unique']),
		];

		$entities = $this->factory->fromDbObjects($dbObjects);

		$this->assertCount(1, $entities);
		$this->assertEquals(99, $entities[0]->getId());
		$this->assertEquals('Unique', $entities[0]->getLabel());
	}
}
