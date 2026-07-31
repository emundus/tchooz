<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Groups
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Groups;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Programs\ProgramEntity;

/**
 * @covers \Tchooz\Entities\Groups\GroupEntity
 */
class GroupEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Groups\GroupEntity::__construct
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getId
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getLabel
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getDescription
	 * @covers \Tchooz\Entities\Groups\GroupEntity::isPublished
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getPrograms
	 * @covers \Tchooz\Entities\Groups\GroupEntity::isAnonymize
	 * @covers \Tchooz\Entities\Groups\GroupEntity::isFilterStatus
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getStatuses
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getVisibleGroups
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getVisibleAttachments
	 * @covers \Tchooz\Entities\Groups\GroupEntity::getClass
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new GroupEntity(1, 'Group', 'Description', true, [], false, false, []);

		$this->assertSame(1, $entity->getId());
		$this->assertSame('Group', $entity->getLabel());
		$this->assertSame('Description', $entity->getDescription());
		$this->assertTrue($entity->isPublished());
		$this->assertSame([], $entity->getPrograms());
		$this->assertFalse($entity->isAnonymize());
		$this->assertFalse($entity->isFilterStatus());
		$this->assertSame([], $entity->getStatuses());
		$this->assertSame([], $entity->getVisibleGroups());
		$this->assertSame([], $entity->getVisibleAttachments());
		$this->assertSame('label-blue-2', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Entities\Groups\GroupEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$entity = new GroupEntity(
			2, 'Group 2', 'Desc 2', false, [],
			true, true, [],
			[1, 2], [3, 4], 'label-red-1'
		);

		$this->assertSame(2, $entity->getId());
		$this->assertFalse($entity->isPublished());
		$this->assertTrue($entity->isAnonymize());
		$this->assertTrue($entity->isFilterStatus());
		$this->assertSame([1, 2], $entity->getVisibleGroups());
		$this->assertSame([3, 4], $entity->getVisibleAttachments());
		$this->assertSame('label-red-1', $entity->getClass());
	}

	/**
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setId
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setLabel
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setDescription
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setPublished
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setPrograms
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setClass
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setAnonymize
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setFilterStatus
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setStatuses
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setVisibleGroups
	 * @covers \Tchooz\Entities\Groups\GroupEntity::setVisibleAttachments
	 */
	public function testSettersReturnFluent(): void
	{
		$entity = new GroupEntity(1, 'Group', 'Desc', true, [], false, false, []);

		$result = $entity->setId(99);
		$this->assertSame($entity, $result);

		$result = $entity->setLabel('New Label');
		$this->assertSame($entity, $result);

		$result = $entity->setDescription('New Desc');
		$this->assertSame($entity, $result);

		$result = $entity->setPublished(false);
		$this->assertSame($entity, $result);

		$result = $entity->setPrograms([]);
		$this->assertSame($entity, $result);

		$result = $entity->setClass('label-green-1');
		$this->assertSame($entity, $result);

		$result = $entity->setAnonymize(true);
		$this->assertSame($entity, $result);

		$result = $entity->setFilterStatus(true);
		$this->assertSame($entity, $result);

		$result = $entity->setStatuses([]);
		$this->assertSame($entity, $result);

		$result = $entity->setVisibleGroups([5, 6]);
		$this->assertSame($entity, $result);

		$result = $entity->setVisibleAttachments([7, 8]);
		$this->assertSame($entity, $result);

		$this->assertSame(99, $entity->getId());
		$this->assertSame('New Label', $entity->getLabel());
		$this->assertSame('New Desc', $entity->getDescription());
		$this->assertFalse($entity->isPublished());
		$this->assertSame('label-green-1', $entity->getClass());
		$this->assertTrue($entity->isAnonymize());
		$this->assertTrue($entity->isFilterStatus());
		$this->assertSame([5, 6], $entity->getVisibleGroups());
		$this->assertSame([7, 8], $entity->getVisibleAttachments());
	}

	/**
	 * @covers \Tchooz\Entities\Groups\GroupEntity::__serialize
	 */
	public function testSerializeWithEmptyRelations(): void
	{
		$entity = new GroupEntity(1, 'Group', 'Desc', true, [], false, false, []);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertSame('Group', $serialized['label']);
		$this->assertSame('Desc', $serialized['description']);
		$this->assertTrue($serialized['published']);
		$this->assertSame([], $serialized['programs']);
		$this->assertSame('label-blue-2', $serialized['class']);
		$this->assertFalse($serialized['anonymize']);
		$this->assertFalse($serialized['filter_status']);
		$this->assertSame([], $serialized['statuses']);
		$this->assertSame('', $serialized['status']);
		$this->assertSame([], $serialized['visible_groups']);
		$this->assertSame([], $serialized['visible_attachments']);
	}
}

