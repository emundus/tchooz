<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Fabrik;

use Joomla\CMS\User\User;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Fabrik\FabrikGroupEntity;
use Tchooz\Entities\Fabrik\FabrikGroupParams;

/**
 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity
 */
class FabrikGroupEntityTest extends UnitTestCase
{
	private \DateTime $created;
	private \DateTime $modified;
	private User $user;

	protected function setUp(): void
	{
		parent::setUp();
		$this->created = new \DateTime('2025-01-15 10:00:00');
		$this->modified = new \DateTime('2025-02-01 12:00:00');
		$this->user = $this->createMock(User::class);
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::__construct
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getId
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getName
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getCreated
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getCreatedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getModified
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getModifiedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::isJoin
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getPrivate
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::isPublished
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getParams
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getElements
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new FabrikGroupEntity(
			1, 'group_name', 'Group Label',
			$this->created, $this->user, $this->modified
		);

		$this->assertSame(1, $entity->getId());
		$this->assertSame('group_name', $entity->getName());
		$this->assertSame('Group Label', $entity->getLabel());
		$this->assertSame($this->created, $entity->getCreated());
		$this->assertSame($this->user, $entity->getCreatedBy());
		$this->assertSame($this->modified, $entity->getModified());
		$this->assertNull($entity->getModifiedBy());
		$this->assertFalse($entity->isJoin());
		$this->assertSame(0, $entity->getPrivate());
		$this->assertSame('', $entity->getParamsRaw());
		$this->assertTrue($entity->isPublished());
		$this->assertNull($entity->getParams());
		$this->assertSame([], $entity->getElements());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$modUser = $this->createMock(User::class);
		$entity = new FabrikGroupEntity(
			2, 'grp', 'Label',
			$this->created, $this->user, $this->modified,
			$modUser, true, 1, '{"key":"val"}'
		);

		$this->assertSame(2, $entity->getId());
		$this->assertSame($modUser, $entity->getModifiedBy());
		$this->assertTrue($entity->isJoin());
		$this->assertSame(1, $entity->getPrivate());
		$this->assertSame('{"key":"val"}', $entity->getParamsRaw());
	}

	/**
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setId
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setName
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setCss
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::getCss
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setLabel
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setPublished
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setCreated
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setCreatedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setModified
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setModifiedBy
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setIsJoin
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setPrivate
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setParams
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setParamsRaw
	 * @covers \Tchooz\Entities\Fabrik\FabrikGroupEntity::setElements
	 */
	public function testSetters(): void
	{
		$entity = new FabrikGroupEntity(
			1, 'name', 'label',
			$this->created, $this->user, $this->modified
		);
		$newUser = $this->createMock(User::class);
		$newDate = new \DateTime('2025-12-01');
		$params = new FabrikGroupParams();

		$entity->setId(99);
		$entity->setName('new_name');
		$entity->setCss('custom-css');
		$entity->setLabel('New Label');
		$entity->setPublished(false);
		$entity->setCreated($newDate);
		$entity->setCreatedBy($newUser);
		$entity->setModified($newDate);
		$entity->setModifiedBy($newUser);
		$entity->setIsJoin(true);
		$entity->setPrivate(1);
		$entity->setParams($params);
		$entity->setParamsRaw('{"a":"b"}');
		$entity->setElements(['elem1']);

		$this->assertSame(99, $entity->getId());
		$this->assertSame('new_name', $entity->getName());
		$this->assertSame('custom-css', $entity->getCss());
		$this->assertSame('New Label', $entity->getLabel());
		$this->assertFalse($entity->isPublished());
		$this->assertSame($newDate, $entity->getCreated());
		$this->assertSame($newUser, $entity->getCreatedBy());
		$this->assertSame($newDate, $entity->getModified());
		$this->assertSame($newUser, $entity->getModifiedBy());
		$this->assertTrue($entity->isJoin());
		$this->assertSame(1, $entity->getPrivate());
		$this->assertSame($params, $entity->getParams());
		$this->assertSame('{"a":"b"}', $entity->getParamsRaw());
		$this->assertSame(['elem1'], $entity->getElements());
	}
}

