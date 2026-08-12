<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Campaigns
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Campaigns;

use DateTime;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Enums\Campaigns\StatusEnum;

/**
 * @covers \Tchooz\Entities\Campaigns\CampaignEntity
 */
class CampaignEntityTest extends UnitTestCase
{
	private ProgramEntity $program;

	protected function setUp(): void
	{
		parent::setUp();
		$this->program = new ProgramEntity('PRG001', 'Programme Test', 1);
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__construct
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getId
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getLabel
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getDescription
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getShortDescription
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getStartDate
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getEndDate
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getProfileId
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getProgram
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getYear
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::isPublished
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::isPinned
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getAlias
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::isVisible
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getParent
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getMoreProperties
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getFilesCount
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getCreatedBy
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$start = new DateTime('+1 month');
		$end = new DateTime('+2 months');

		$entity = new CampaignEntity('Campaign', $start, $end, $this->program, '2025');

		$this->assertSame(0, $entity->getId());
		$this->assertSame('Campaign', $entity->getLabel());
		$this->assertSame('', $entity->getDescription());
		$this->assertSame('', $entity->getShortDescription());
		$this->assertSame($start, $entity->getStartDate());
		$this->assertSame($end, $entity->getEndDate());
		$this->assertSame(0, $entity->getProfileId());
		$this->assertSame($this->program, $entity->getProgram());
		$this->assertSame('2025', $entity->getYear());
		$this->assertTrue($entity->isPublished());
		$this->assertFalse($entity->isPinned());
		$this->assertSame('', $entity->getAlias());
		$this->assertTrue($entity->isVisible());
		$this->assertNull($entity->getParent());
		$this->assertSame([], $entity->getMoreProperties());
		$this->assertSame(0, $entity->getFilesCount());
		$this->assertSame(0, $entity->getCreatedBy());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$start = new DateTime('-1 month');
		$end = new DateTime('+1 month');
		$parent = new CampaignEntity('Parent', new DateTime('+1 month'), new DateTime('+2 months'), null, '2025');

		$entity = new CampaignEntity(
			'Full Campaign', $start, $end, $this->program, '2026',
			'Description', 'Short desc', 5,
			false, true, 'alias-test', false,
			$parent, 42, ['extra' => 'data'], 10, 99
		);

		$this->assertSame(42, $entity->getId());
		$this->assertSame('Full Campaign', $entity->getLabel());
		$this->assertSame('Description', $entity->getDescription());
		$this->assertSame('Short desc', $entity->getShortDescription());
		$this->assertSame(5, $entity->getProfileId());
		$this->assertSame('2026', $entity->getYear());
		$this->assertFalse($entity->isPublished());
		$this->assertTrue($entity->isPinned());
		$this->assertSame('alias-test', $entity->getAlias());
		$this->assertFalse($entity->isVisible());
		$this->assertSame($parent, $entity->getParent());
		$this->assertSame(['extra' => 'data'], $entity->getMoreProperties());
		$this->assertSame(10, $entity->getFilesCount());
		$this->assertSame(99, $entity->getCreatedBy());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__construct
	 */
	public function testInstanciationWithNullProgram(): void
	{
		$entity = new CampaignEntity('Test', new DateTime('+1 day'), new DateTime('+2 days'), null, '2025');

		$this->assertNull($entity->getProgram());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__construct
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getStatus
	 */
	public function testStatusIsUpcomingWhenStartDateInFuture(): void
	{
		$entity = new CampaignEntity(
			'Future', new DateTime('+1 month'), new DateTime('+2 months'), null, '2025'
		);

		$this->assertSame(StatusEnum::UPCCOMING, $entity->getStatus());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__construct
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getStatus
	 */
	public function testStatusIsOpenWhenCurrentDateBetweenStartAndEnd(): void
	{
		$entity = new CampaignEntity(
			'Current', new DateTime('-1 month'), new DateTime('+1 month'), null, '2025'
		);

		$this->assertSame(StatusEnum::OPEN, $entity->getStatus());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__construct
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getStatus
	 */
	public function testStatusIsClosedWhenEndDateInPast(): void
	{
		$entity = new CampaignEntity(
			'Past', new DateTime('-2 months'), new DateTime('-1 month'), null, '2025'
		);

		$this->assertSame(StatusEnum::CLOSED, $entity->getStatus());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__construct
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::getTimezone
	 */
	public function testTimezoneIsSetFromApplication(): void
	{
		$entity = new CampaignEntity(
			'Test', new DateTime('+1 day'), new DateTime('+2 days'), null, '2025'
		);

		$this->assertNotEmpty($entity->getTimezone());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setId
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setCreatedBy
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setLabel
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setDescription
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setShortDescription
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setStartDate
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setEndDate
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setProfileId
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setProgram
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setYear
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setPublished
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setPinned
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setAlias
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setVisible
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setParent
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setStatus
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setTimezone
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setMoreProperties
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setFilesCount
	 */
	public function testSetters(): void
	{
		$entity = new CampaignEntity(
			'Campaign', new DateTime('+1 day'), new DateTime('+2 days'), null, '2025'
		);

		$newStart = new DateTime('2026-01-01');
		$newEnd = new DateTime('2026-12-31');
		$newProgram = new ProgramEntity('PRG002', 'New Programme', 2);
		$parent = new CampaignEntity('Parent', new DateTime('+1 day'), new DateTime('+2 days'), null, '2025');

		$entity->setId(50);
		$entity->setCreatedBy(100);
		$entity->setLabel('New Label');
		$entity->setDescription('New Desc');
		$entity->setShortDescription('New Short');
		$entity->setStartDate($newStart);
		$entity->setEndDate($newEnd);
		$entity->setProfileId(7);
		$entity->setProgram($newProgram);
		$entity->setYear('2026');
		$entity->setPublished(false);
		$entity->setPinned(true);
		$entity->setAlias('new-alias');
		$entity->setVisible(false);
		$entity->setParent($parent);
		$entity->setStatus(StatusEnum::CLOSED);
		$entity->setTimezone('America/New_York');
		$entity->setMoreProperties(['k' => 'v']);
		$entity->setFilesCount(25);

		$this->assertSame(50, $entity->getId());
		$this->assertSame(100, $entity->getCreatedBy());
		$this->assertSame('New Label', $entity->getLabel());
		$this->assertSame('New Desc', $entity->getDescription());
		$this->assertSame('New Short', $entity->getShortDescription());
		$this->assertSame($newStart, $entity->getStartDate());
		$this->assertSame($newEnd, $entity->getEndDate());
		$this->assertSame(7, $entity->getProfileId());
		$this->assertSame($newProgram, $entity->getProgram());
		$this->assertSame('2026', $entity->getYear());
		$this->assertFalse($entity->isPublished());
		$this->assertTrue($entity->isPinned());
		$this->assertSame('new-alias', $entity->getAlias());
		$this->assertFalse($entity->isVisible());
		$this->assertSame($parent, $entity->getParent());
		$this->assertSame(StatusEnum::CLOSED, $entity->getStatus());
		$this->assertSame('America/New_York', $entity->getTimezone());
		$this->assertSame(['k' => 'v'], $entity->getMoreProperties());
		$this->assertSame(25, $entity->getFilesCount());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::setParent
	 */
	public function testSetParentAcceptsNull(): void
	{
		$parent = new CampaignEntity('Parent', new DateTime('+1 day'), new DateTime('+2 days'), null, '2025');
		$entity = new CampaignEntity(
			'Child', new DateTime('+1 day'), new DateTime('+2 days'), null, '2025',
			parent: $parent
		);

		$this->assertSame($parent, $entity->getParent());

		$entity->setParent(null);
		$this->assertNull($entity->getParent());
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__serialize
	 */
	public function testSerializeWithoutProgram(): void
	{
		$entity = new CampaignEntity(
			'Campaign', new DateTime('-1 month'), new DateTime('+1 month'), null, '2025'
		);

		$serialized = $entity->__serialize();

		$this->assertSame('Campaign', $serialized['label']);
		$this->assertNull($serialized['program']);
		$this->assertSame('open', $serialized['status']);
		$this->assertSame('2025', $serialized['year']);
		$this->assertArrayHasKey('id', $serialized);
		$this->assertArrayHasKey('start_date', $serialized);
		$this->assertArrayHasKey('end_date', $serialized);
		$this->assertArrayHasKey('timezone', $serialized);
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__serialize
	 */
	public function testSerializeWithProgram(): void
	{
		$entity = new CampaignEntity(
			'Campaign', new DateTime('+1 month'), new DateTime('+2 months'),
			$this->program, '2025'
		);

		$serialized = $entity->__serialize();

		$this->assertIsArray($serialized['program']);
		$this->assertSame('PRG001', $serialized['program']['code']);
		$this->assertSame('Programme Test', $serialized['program']['label']);
	}

	/**
	 * @covers \Tchooz\Entities\Campaigns\CampaignEntity::__serialize
	 */
	public function testSerializeStatusIsStringValue(): void
	{
		$entity = new CampaignEntity(
			'Future', new DateTime('+1 month'), new DateTime('+2 months'), null, '2025'
		);

		$serialized = $entity->__serialize();

		$this->assertSame('upcoming', $serialized['status']);
	}
}