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
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Entities\Groups\GroupEntity;

/**
 * @covers \Tchooz\Entities\Actions\GroupAccessEntity
 */
class GroupAccessEntityTest extends UnitTestCase
{
	private CrudEntity $crud;
	private GroupEntity $group;
	private ActionEntity $action;

	protected function setUp(): void
	{
		parent::setUp();
		$this->crud = new CrudEntity(1, 1, 1, 1, 1);
		$this->group = new GroupEntity(1, 'Group', 'Description', true, [], false, false, []);
		$this->action = new ActionEntity(1, 'action', 'Action', $this->crud);
	}

	/**
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::__construct
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::getId
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::getGroup
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::getAction
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::getCrud
	 */
	public function testInstanciation(): void
	{
		$entity = new GroupAccessEntity(1, $this->group, $this->action, $this->crud);

		$this->assertSame(1, $entity->getId());
		$this->assertSame($this->group, $entity->getGroup());
		$this->assertSame($this->action, $entity->getAction());
		$this->assertSame($this->crud, $entity->getCrud());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::__construct
	 */
	public function testInstanciationWithNullGroupAndAction(): void
	{
		$entity = new GroupAccessEntity(2, null, null, $this->crud);

		$this->assertSame(2, $entity->getId());
		$this->assertNull($entity->getGroup());
		$this->assertNull($entity->getAction());
		$this->assertSame($this->crud, $entity->getCrud());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::setId
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::setGroup
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::setAction
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::setCrud
	 */
	public function testSettersReturnFluent(): void
	{
		$entity = new GroupAccessEntity(1, null, null, $this->crud);
		$newCrud = new CrudEntity(0, 0, 0, 0, 0);

		$result = $entity->setId(99);
		$this->assertSame($entity, $result);

		$result = $entity->setGroup($this->group);
		$this->assertSame($entity, $result);

		$result = $entity->setAction($this->action);
		$this->assertSame($entity, $result);

		$result = $entity->setCrud($newCrud);
		$this->assertSame($entity, $result);

		$this->assertSame(99, $entity->getId());
		$this->assertSame($this->group, $entity->getGroup());
		$this->assertSame($this->action, $entity->getAction());
		$this->assertSame($newCrud, $entity->getCrud());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$entity = new GroupAccessEntity(1, $this->group, $this->action, $this->crud);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertIsArray($serialized['group']);
		$this->assertIsArray($serialized['action']);
		$this->assertIsArray($serialized['crud']);
	}

	/**
	 * @covers \Tchooz\Entities\Actions\GroupAccessEntity::__serialize
	 */
	public function testSerializeWithNullGroupAndAction(): void
	{
		$entity = new GroupAccessEntity(1, null, null, $this->crud);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertNull($serialized['group']);
		$this->assertNull($serialized['action']);
		$this->assertIsArray($serialized['crud']);
	}
}

