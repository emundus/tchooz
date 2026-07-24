<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Poll
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Poll;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Poll\PollEntity
 */
class PollEntityTest extends TestCase
{
	private function makePoll(
		int        $id             = 1,
		string     $name           = 'Jury de printemps',
		string     $description    = 'Trouver un créneau commun',
		ColorEnum  $color          = ColorEnum::BLUE,
		StatusEnum $status         = StatusEnum::OPEN,
		?\DateTime $startDate      = null,
		?\DateTime $endDate        = null,
		array      $participants   = [],
		array      $slots          = [],
		bool       $canEditAnswers = false
	): PollEntity
	{
		return new PollEntity($id, $name, $description, $color, $status, $startDate, $endDate, $participants, $slots, $canEditAnswers);
	}

	// -------------------------------------------------------------------------
	// Constructor / getters
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollEntity::getId
	 * @covers \Tchooz\Entities\Poll\PollEntity::getName
	 * @covers \Tchooz\Entities\Poll\PollEntity::getDescription
	 * @covers \Tchooz\Entities\Poll\PollEntity::getColor
	 * @covers \Tchooz\Entities\Poll\PollEntity::getStatus
	 * @covers \Tchooz\Entities\Poll\PollEntity::getStartDate
	 * @covers \Tchooz\Entities\Poll\PollEntity::getEndDate
	 * @covers \Tchooz\Entities\Poll\PollEntity::getParticipants
	 * @covers \Tchooz\Entities\Poll\PollEntity::getSlots
	 * @covers \Tchooz\Entities\Poll\PollEntity::canEditAnswers
	 * @return void
	 */
	public function testConstructorInitializesAllProperties(): void
	{
		$start = new \DateTime('2026-01-01 09:00:00');
		$end   = new \DateTime('2026-01-31 18:00:00');

		$poll = $this->makePoll(
			42,
			'Comité de sélection',
			'Choisir une date',
			ColorEnum::BLUE,
			StatusEnum::CLOSED,
			$start,
			$end,
			[],
			[],
			true
		);

		$this->assertSame(42, $poll->getId(), 'Constructor should set the id');
		$this->assertSame('Comité de sélection', $poll->getName(), 'Constructor should set the name');
		$this->assertSame('Choisir une date', $poll->getDescription(), 'Constructor should set the description');
		$this->assertSame(ColorEnum::BLUE, $poll->getColor(), 'Constructor should set the color');
		$this->assertSame(StatusEnum::CLOSED, $poll->getStatus(), 'Constructor should set the status');
		$this->assertSame($start, $poll->getStartDate(), 'Constructor should set the start date');
		$this->assertSame($end, $poll->getEndDate(), 'Constructor should set the end date');
		$this->assertSame([], $poll->getParticipants(), 'Constructor should set participants');
		$this->assertSame([], $poll->getSlots(), 'Constructor should set slots');
		$this->assertTrue($poll->canEditAnswers(), 'Constructor should set canEditAnswers');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollEntity::getStatus
	 * @covers \Tchooz\Entities\Poll\PollEntity::canEditAnswers
	 * @return void
	 */
	public function testConstructorAppliesDefaultStatusAndCanEditAnswers(): void
	{
		$poll = new PollEntity(0, 'New poll', '', ColorEnum::BLUE);

		$this->assertSame(StatusEnum::UPCCOMING, $poll->getStatus(), 'Status should default to UPCCOMING');
		$this->assertFalse($poll->canEditAnswers(), 'canEditAnswers should default to false');
		$this->assertNull($poll->getStartDate(), 'Start date should default to null');
		$this->assertNull($poll->getEndDate(), 'End date should default to null');
	}

	// -------------------------------------------------------------------------
	// Setters — value updated
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setId
	 * @covers \Tchooz\Entities\Poll\PollEntity::getId
	 * @return void
	 */
	public function testSetIdUpdatesId(): void
	{
		$poll = $this->makePoll(id: 1);
		$poll->setId(99);
		$this->assertSame(99, $poll->getId(), 'setId should update the id');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setName
	 * @covers \Tchooz\Entities\Poll\PollEntity::getName
	 * @return void
	 */
	public function testSetNameUpdatesName(): void
	{
		$poll = $this->makePoll(name: 'Old');
		$poll->setName('New');
		$this->assertSame('New', $poll->getName(), 'setName should update the name');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setDescription
	 * @covers \Tchooz\Entities\Poll\PollEntity::getDescription
	 * @return void
	 */
	public function testSetDescriptionUpdatesDescription(): void
	{
		$poll = $this->makePoll(description: 'Old');
		$poll->setDescription('New');
		$this->assertSame('New', $poll->getDescription(), 'setDescription should update the description');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setColor
	 * @covers \Tchooz\Entities\Poll\PollEntity::getColor
	 * @return void
	 */
	public function testSetColorUpdatesColor(): void
	{
		$poll = $this->makePoll(color: ColorEnum::BLUE);
		$poll->setColor(ColorEnum::DARK_BLUE);
		$this->assertSame(ColorEnum::DARK_BLUE, $poll->getColor(), 'setColor should update the color');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setStatus
	 * @covers \Tchooz\Entities\Poll\PollEntity::getStatus
	 * @return void
	 */
	public function testSetStatusUpdatesStatus(): void
	{
		$poll = $this->makePoll(status: StatusEnum::UPCCOMING);
		$poll->setStatus(StatusEnum::OPEN);
		$this->assertSame(StatusEnum::OPEN, $poll->getStatus(), 'setStatus should update the status');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setStartDate
	 * @covers \Tchooz\Entities\Poll\PollEntity::getStartDate
	 * @return void
	 */
	public function testSetStartDateUpdatesStartDate(): void
	{
		$poll = $this->makePoll();
		$date = new \DateTime('2026-03-15 08:00:00');
		$poll->setStartDate($date);
		$this->assertSame($date, $poll->getStartDate(), 'setStartDate should update the start date');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setEndDate
	 * @covers \Tchooz\Entities\Poll\PollEntity::getEndDate
	 * @return void
	 */
	public function testSetEndDateUpdatesEndDate(): void
	{
		$poll = $this->makePoll();
		$date = new \DateTime('2026-04-15 08:00:00');
		$poll->setEndDate($date);
		$this->assertSame($date, $poll->getEndDate(), 'setEndDate should update the end date');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setParticipants
	 * @covers \Tchooz\Entities\Poll\PollEntity::getParticipants
	 * @return void
	 */
	public function testSetParticipantsUpdatesParticipants(): void
	{
		$poll         = $this->makePoll();
		$participants = ['p1', 'p2'];
		$poll->setParticipants($participants);
		$this->assertSame($participants, $poll->getParticipants(), 'setParticipants should update the participants');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setSlots
	 * @covers \Tchooz\Entities\Poll\PollEntity::getSlots
	 * @return void
	 */
	public function testSetSlotsUpdatesSlots(): void
	{
		$poll  = $this->makePoll();
		$slots = ['s1'];
		$poll->setSlots($slots);
		$this->assertSame($slots, $poll->getSlots(), 'setSlots should update the slots');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setCanEditAnswers
	 * @covers \Tchooz\Entities\Poll\PollEntity::canEditAnswers
	 * @return void
	 */
	public function testSetCanEditAnswersUpdatesValue(): void
	{
		$poll = $this->makePoll(canEditAnswers: false);
		$poll->setCanEditAnswers(true);
		$this->assertTrue($poll->canEditAnswers(), 'setCanEditAnswers should update the value');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::getCreatedBy
	 * @covers \Tchooz\Entities\Poll\PollEntity::setCreatedBy
	 * @return void
	 */
	public function testCreatedByDefaultsToNullAndCanBeSet(): void
	{
		$poll = $this->makePoll();
		$this->assertNull($poll->getCreatedBy(), 'createdBy should default to null');

		$poll->setCreatedBy(42);
		$this->assertSame(42, $poll->getCreatedBy(), 'setCreatedBy should update the creator id');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setCreatedBy
	 * @return void
	 */
	public function testSetCreatedByReturnsSelf(): void
	{
		$poll = $this->makePoll();
		$this->assertSame($poll, $poll->setCreatedBy(7), 'setCreatedBy should return $this');
	}

	// -------------------------------------------------------------------------
	// Fluent interface — every setter must return $this
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setId
	 * @covers \Tchooz\Entities\Poll\PollEntity::setName
	 * @covers \Tchooz\Entities\Poll\PollEntity::setDescription
	 * @covers \Tchooz\Entities\Poll\PollEntity::setColor
	 * @covers \Tchooz\Entities\Poll\PollEntity::setStatus
	 * @covers \Tchooz\Entities\Poll\PollEntity::setStartDate
	 * @covers \Tchooz\Entities\Poll\PollEntity::setEndDate
	 * @covers \Tchooz\Entities\Poll\PollEntity::setParticipants
	 * @covers \Tchooz\Entities\Poll\PollEntity::setSlots
	 * @covers \Tchooz\Entities\Poll\PollEntity::setCanEditAnswers
	 * @return void
	 */
	public function testAllSettersReturnSelf(): void
	{
		$poll = $this->makePoll();

		$this->assertSame($poll, $poll->setId(2), 'setId should return $this');
		$this->assertSame($poll, $poll->setName('x'), 'setName should return $this');
		$this->assertSame($poll, $poll->setDescription('x'), 'setDescription should return $this');
		$this->assertSame($poll, $poll->setColor(ColorEnum::BLUE), 'setColor should return $this');
		$this->assertSame($poll, $poll->setStatus(StatusEnum::OPEN), 'setStatus should return $this');
		$this->assertSame($poll, $poll->setStartDate(null), 'setStartDate should return $this');
		$this->assertSame($poll, $poll->setEndDate(null), 'setEndDate should return $this');
		$this->assertSame($poll, $poll->setParticipants([]), 'setParticipants should return $this');
		$this->assertSame($poll, $poll->setSlots([]), 'setSlots should return $this');
		$this->assertSame($poll, $poll->setCanEditAnswers(true), 'setCanEditAnswers should return $this');
	}

	// -------------------------------------------------------------------------
	// __serialize — shape stability
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__serialize
	 * @return void
	 */
	public function testSerializeProducesExpectedShape(): void
	{
		$start = new \DateTime('2026-01-01 09:00:00');
		$end   = new \DateTime('2026-01-31 18:00:00');
		$poll  = $this->makePoll(7, 'Sondage', 'Desc', ColorEnum::BLUE, StatusEnum::OPEN, $start, $end, [], [], true);

		$serialized = $poll->__serialize();

		$this->assertSame(7, $serialized['id'], 'Serialized id should match');
		$this->assertSame('Sondage', $serialized['name'], 'Serialized name should match');
		$this->assertSame('Desc', $serialized['description'], 'Serialized description should match');
		$this->assertSame(ColorEnum::BLUE->value, $serialized['color'], 'Serialized color should be the enum value');
		$this->assertSame(StatusEnum::OPEN->value, $serialized['status'], 'Serialized status should be the enum value');
		$this->assertSame('2026-01-01 09:00:00', $serialized['start_date'], 'Serialized start_date should be Y-m-d H:i:s');
		$this->assertSame('2026-01-31 18:00:00', $serialized['end_date'], 'Serialized end_date should be Y-m-d H:i:s');
		$this->assertSame(1, $serialized['can_edit_answers'], 'Serialized can_edit_answers should be 1 when true');
		$this->assertArrayHasKey('created_by', $serialized, 'Serialized output should expose created_by');
		$this->assertSame([], $serialized['slots'], 'Serialized slots should be an empty array');
		$this->assertSame([], $serialized['participants'], 'Serialized participants should be an empty array');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__serialize
	 * @return void
	 */
	public function testSerializeNullDatesAndFalseCanEditAnswers(): void
	{
		$poll = new PollEntity(0, 'P', '', ColorEnum::BLUE);

		$serialized = $poll->__serialize();

		$this->assertNull($serialized['start_date'], 'Null start date should serialize to null');
		$this->assertNull($serialized['end_date'], 'Null end date should serialize to null');
		$this->assertSame(0, $serialized['can_edit_answers'], 'Serialized can_edit_answers should be 0 when false');
	}

	// -------------------------------------------------------------------------
	// Edge cases
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollEntity::getId
	 * @return void
	 */
	public function testConstructorAcceptsIdZero(): void
	{
		$poll = $this->makePoll(id: 0);
		$this->assertSame(0, $poll->getId(), 'Constructor should accept id 0');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollEntity::getName
	 * @covers \Tchooz\Entities\Poll\PollEntity::getDescription
	 * @return void
	 */
	public function testConstructorAcceptsEmptyStrings(): void
	{
		$poll = $this->makePoll(name: '', description: '');
		$this->assertSame('', $poll->getName(), 'Constructor should accept an empty name');
		$this->assertSame('', $poll->getDescription(), 'Constructor should accept an empty description');
	}

	// -------------------------------------------------------------------------
	// Programmes
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::getPrograms
	 * @return void
	 */
	public function testProgramsDefaultsToEmptyArray(): void
	{
		$poll = new PollEntity(0, 'P', '', ColorEnum::BLUE);
		$this->assertSame([], $poll->getPrograms(), 'Programs should default to an empty array');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollEntity::getPrograms
	 * @return void
	 */
	public function testConstructorNormalizesPrograms(): void
	{
		$poll = new PollEntity(1, 'P', '', ColorEnum::BLUE, StatusEnum::OPEN, null, null, [], [], false, null, ['3', 7, '3']);
		$this->assertSame([3, 7], $poll->getPrograms(), 'Constructor should cast to int and drop duplicates');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::setPrograms
	 * @return void
	 */
	public function testSetProgramsNormalizesAndReturnsSelf(): void
	{
		$poll   = new PollEntity(0, 'P', '', ColorEnum::BLUE);
		$result = $poll->setPrograms(['5', 5, '9']);

		$this->assertSame($poll, $result, 'setPrograms should return self');
		$this->assertSame([5, 9], $poll->getPrograms(), 'setPrograms should cast to int and drop duplicates');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollEntity::__serialize
	 * @return void
	 */
	public function testSerializeIncludesPrograms(): void
	{
		$poll = new PollEntity(1, 'P', '', ColorEnum::BLUE);
		$poll->setPrograms([4, 8]);

		$serialized = $poll->__serialize();

		$this->assertSame([4, 8], $serialized['programs'], 'Serialized output should expose programs');
	}
}
