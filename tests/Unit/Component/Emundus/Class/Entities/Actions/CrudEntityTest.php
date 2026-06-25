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
use Tchooz\Entities\Actions\CrudEntity;

/**
 * @covers \Tchooz\Entities\Actions\CrudEntity
 */
class CrudEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Actions\CrudEntity::__construct
	 * @covers \Tchooz\Entities\Actions\CrudEntity::getMulti
	 * @covers \Tchooz\Entities\Actions\CrudEntity::getCreate
	 * @covers \Tchooz\Entities\Actions\CrudEntity::getRead
	 * @covers \Tchooz\Entities\Actions\CrudEntity::getUpdate
	 * @covers \Tchooz\Entities\Actions\CrudEntity::getDelete
	 */
	public function testInstanciation(): void
	{
		$entity = new CrudEntity(1, 2, 3, 4, 5);

		$this->assertSame(1, $entity->getMulti());
		$this->assertSame(2, $entity->getCreate());
		$this->assertSame(3, $entity->getRead());
		$this->assertSame(4, $entity->getUpdate());
		$this->assertSame(5, $entity->getDelete());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\CrudEntity::setMulti
	 * @covers \Tchooz\Entities\Actions\CrudEntity::setCreate
	 * @covers \Tchooz\Entities\Actions\CrudEntity::setRead
	 * @covers \Tchooz\Entities\Actions\CrudEntity::setUpdate
	 * @covers \Tchooz\Entities\Actions\CrudEntity::setDelete
	 */
	public function testSetters(): void
	{
		$entity = new CrudEntity(0, 0, 0, 0, 0);

		$entity->setMulti(10);
		$entity->setCreate(20);
		$entity->setRead(30);
		$entity->setUpdate(40);
		$entity->setDelete(50);

		$this->assertSame(10, $entity->getMulti());
		$this->assertSame(20, $entity->getCreate());
		$this->assertSame(30, $entity->getRead());
		$this->assertSame(40, $entity->getUpdate());
		$this->assertSame(50, $entity->getDelete());
	}

	/**
	 * @covers \Tchooz\Entities\Actions\CrudEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$entity = new CrudEntity(1, 2, 3, 4, 5);

		$serialized = $entity->__serialize();

		$this->assertSame([
			'multi'  => 1,
			'create' => 2,
			'read'   => 3,
			'update' => 4,
			'delete' => 5,
		], $serialized);
	}
}