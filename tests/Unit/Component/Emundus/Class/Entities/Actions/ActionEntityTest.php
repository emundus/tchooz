<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Actions;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Enums\Actions\ActionTypeEnum;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Actions
 *
 * @covers \Tchooz\Entities\Actions\ActionEntity
 */
class ActionEntityTest extends UnitTestCase
{
	private CrudEntity $crud;

	protected function setUp(): void
	{
		parent::setUp();
		$this->crud = new CrudEntity(1, 1, 1, 1, 1);
	}

	/**
	 * @covers \Tchooz\Entities\Actions\ActionEntity::__construct()
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new ActionEntity(1, 'test_action', 'Test Action', $this->crud);

		$this->assertSame(1, $entity->getId());
		$this->assertSame('test_action', $entity->getName());
		$this->assertSame('Test Action', $entity->getLabel());
		$this->assertSame($this->crud, $entity->getCrud());
		$this->assertSame(0, $entity->getOrdering());
		$this->assertTrue($entity->isStatus());
		$this->assertNull($entity->getDescription());
		$this->assertSame(ActionTypeEnum::FILE, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\ActionEntity::__construct()
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$entity = new ActionEntity(
			5,
			'full_action',
			'Full Action',
			$this->crud,
			10,
			false,
			'Une description',
			ActionTypeEnum::PLATFORM
		);

		$this->assertSame(5, $entity->getId());
		$this->assertSame('full_action', $entity->getName());
		$this->assertSame('Full Action', $entity->getLabel());
		$this->assertSame($this->crud, $entity->getCrud());
		$this->assertSame(10, $entity->getOrdering());
		$this->assertFalse($entity->isStatus());
		$this->assertSame('Une description', $entity->getDescription());
		$this->assertSame(ActionTypeEnum::PLATFORM, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\ActionEntity::__construct()
	 */
	public function testIdZeroFallback(): void
	{
		$entity = new ActionEntity(0, 'action', 'Action', $this->crud);

		$this->assertSame(0, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setId()
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setName()
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setLabel()
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setCrud()
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setOrdering()
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setStatus()
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setDescription()
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setType()
	 */
	public function testSetters(): void
	{
		$entity = new ActionEntity(1, 'action', 'Action', $this->crud);
		$newCrud = new CrudEntity(0, 0, 0, 0, 0);

		$entity->setId(99);
		$entity->setName('new_name');
		$entity->setLabel('New Label');
		$entity->setCrud($newCrud);
		$entity->setOrdering(5);
		$entity->setStatus(false);
		$entity->setDescription('desc');
		$entity->setType(ActionTypeEnum::USERS);

		$this->assertSame(99, $entity->getId());
		$this->assertSame('new_name', $entity->getName());
		$this->assertSame('New Label', $entity->getLabel());
		$this->assertSame($newCrud, $entity->getCrud());
		$this->assertSame(5, $entity->getOrdering());
		$this->assertFalse($entity->isStatus());
		$this->assertSame('desc', $entity->getDescription());
		$this->assertSame(ActionTypeEnum::USERS, $entity->getType());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\ActionEntity::setType()
	 */
	public function testSetTypeReturnsFluent(): void
	{
		$entity = new ActionEntity(1, 'action', 'Action', $this->crud);
		$result = $entity->setType(ActionTypeEnum::PLATFORM);

		$this->assertSame($entity, $result);
	}

	/**
	 * @covers \Tchooz\Entities\Actions\ActionEntity::__serialize()
	 */
	public function testSerialize(): void
	{
		$entity = new ActionEntity(
			1,
			'action',
			'Action Label',
			$this->crud,
			3,
			true,
			'description',
			ActionTypeEnum::FILE
		);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertSame('action', $serialized['name']);
		$this->assertSame('Action Label', $serialized['label']);
		$this->assertIsArray($serialized['crud']);
		$this->assertSame(3, $serialized['ordering']);
		$this->assertTrue($serialized['status']);
		$this->assertSame('description', $serialized['description']);
		$this->assertSame('file', $serialized['type']);
	}
}