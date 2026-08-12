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
use Tchooz\Entities\Event\SlotEntity;
use Tchooz\Entities\Poll\PollAnswerEntity;
use Tchooz\Entities\Poll\PollParticipantsEntity;
use Tchooz\Enums\Poll\AnswerTypeEnum;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Poll\PollAnswerEntity
 */
class PollAnswerEntityTest extends TestCase
{
	private function makeSlot(int $id = 11): SlotEntity
	{
		return new SlotEntity(
			$id,
			new \DateTime('2026-01-15 09:00:00'),
			new \DateTime('2026-01-15 10:00:00'),
			1
		);
	}

	private function makeParticipant(int $id = 21): PollParticipantsEntity
	{
		return new PollParticipantsEntity($id, null, 'p@example.com', 'First', 'Last', null);
	}

	private function makeAnswer(
		int $id = 1,
		AnswerTypeEnum $answer = AnswerTypeEnum::AVAILABLE,
		?SlotEntity $slot = null,
		string $comment = 'A comment',
		?PollParticipantsEntity $participant = null
	): PollAnswerEntity
	{
		return new PollAnswerEntity(
			$id,
			$answer,
			$slot ?? $this->makeSlot(),
			$comment,
			$participant ?? $this->makeParticipant()
		);
	}

	// -------------------------------------------------------------------------
	// Constructor / getters
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getId
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getAnswer
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getSlot
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getComment
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getParticipant
	 * @return void
	 */
	public function testConstructorInitializesAllProperties(): void
	{
		$slot        = $this->makeSlot(77);
		$participant = $this->makeParticipant(88);
		$answer      = $this->makeAnswer(42, AnswerTypeEnum::IF_NEEDED, $slot, 'Peut-être', $participant);

		$this->assertSame(42, $answer->getId(), 'Constructor should set the id');
		$this->assertSame(AnswerTypeEnum::IF_NEEDED, $answer->getAnswer(), 'Constructor should set the answer enum');
		$this->assertSame($slot, $answer->getSlot(), 'Constructor should set the slot');
		$this->assertSame('Peut-être', $answer->getComment(), 'Constructor should set the comment');
		$this->assertSame($participant, $answer->getParticipant(), 'Constructor should set the participant');
	}

	// -------------------------------------------------------------------------
	// Setters — value updated
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setId
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getId
	 * @return void
	 */
	public function testSetIdUpdatesId(): void
	{
		$answer = $this->makeAnswer(id: 1);
		$answer->setId(99);
		$this->assertSame(99, $answer->getId(), 'setId should update the id');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setAnswer
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getAnswer
	 * @return void
	 */
	public function testSetAnswerUpdatesAnswer(): void
	{
		$answer = $this->makeAnswer(answer: AnswerTypeEnum::AVAILABLE);
		$answer->setAnswer(AnswerTypeEnum::NOT_AVAILABLE);
		$this->assertSame(AnswerTypeEnum::NOT_AVAILABLE, $answer->getAnswer(), 'setAnswer should update the answer enum');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setSlot
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getSlot
	 * @return void
	 */
	public function testSetSlotUpdatesSlot(): void
	{
		$answer  = $this->makeAnswer();
		$newSlot = $this->makeSlot(555);
		$answer->setSlot($newSlot);
		$this->assertSame($newSlot, $answer->getSlot(), 'setSlot should update the slot');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setComment
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getComment
	 * @return void
	 */
	public function testSetCommentUpdatesComment(): void
	{
		$answer = $this->makeAnswer(comment: 'old');
		$answer->setComment('new');
		$this->assertSame('new', $answer->getComment(), 'setComment should update the comment');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setParticipant
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getParticipant
	 * @return void
	 */
	public function testSetParticipantUpdatesParticipant(): void
	{
		$answer         = $this->makeAnswer();
		$newParticipant = $this->makeParticipant(999);
		$answer->setParticipant($newParticipant);
		$this->assertSame($newParticipant, $answer->getParticipant(), 'setParticipant should update the participant');
	}

	// -------------------------------------------------------------------------
	// Fluent interface — every setter must return $this
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setId
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setAnswer
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setSlot
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setComment
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::setParticipant
	 * @return void
	 */
	public function testAllSettersReturnSelf(): void
	{
		$answer = $this->makeAnswer();

		$this->assertSame($answer, $answer->setId(2), 'setId should return $this');
		$this->assertSame($answer, $answer->setAnswer(AnswerTypeEnum::NOT_ANSWERED), 'setAnswer should return $this');
		$this->assertSame($answer, $answer->setSlot($this->makeSlot(2)), 'setSlot should return $this');
		$this->assertSame($answer, $answer->setComment('x'), 'setComment should return $this');
		$this->assertSame($answer, $answer->setParticipant($this->makeParticipant(3)), 'setParticipant should return $this');
	}

	// -------------------------------------------------------------------------
	// __serialize — shape stability
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::__serialize
	 * @return void
	 */
	public function testSerializeProducesExpectedShape(): void
	{
		$slot        = $this->makeSlot(123);
		$participant = $this->makeParticipant(456);
		$answer      = $this->makeAnswer(7, AnswerTypeEnum::AVAILABLE, $slot, 'OK', $participant);

		$serialized = $answer->__serialize();

		$this->assertSame(7, $serialized['id'], 'Serialized id should match');
		$this->assertSame(AnswerTypeEnum::AVAILABLE->value, $serialized['answer'], 'Serialized answer should be the enum value');
		$this->assertSame(123, $serialized['slot'], 'Serialized slot should be the slot id');
		$this->assertSame('OK', $serialized['comment'], 'Serialized comment should match');
		$this->assertSame(456, $serialized['participant'], 'Serialized participant should be the participant id');
	}

	// -------------------------------------------------------------------------
	// Edge cases
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getComment
	 * @return void
	 */
	public function testConstructorAcceptsEmptyComment(): void
	{
		$answer = $this->makeAnswer(comment: '');
		$this->assertSame('', $answer->getComment(), 'Constructor should accept an empty comment');
	}

	/**
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::__construct
	 * @covers \Tchooz\Entities\Poll\PollAnswerEntity::getId
	 * @return void
	 */
	public function testConstructorAcceptsIdZero(): void
	{
		$answer = $this->makeAnswer(id: 0);
		$this->assertSame(0, $answer->getId(), 'Constructor should accept id 0 (insert candidate)');
	}
}
