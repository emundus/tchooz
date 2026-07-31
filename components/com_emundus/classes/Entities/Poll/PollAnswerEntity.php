<?php

namespace Tchooz\Entities\Poll;
use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;
use Tchooz\Entities\Event\SlotEntity;
use Tchooz\Enums\Poll\AnswerTypeEnum;

#[Table(name: '#__emundus_poll_answers')]
class PollAnswerEntity
{
	private int $id;

	#[Column(type: Types::STRING, length: 50)]
	private AnswerTypeEnum $answer;

	#[Column(type: Types::INTEGER)]
	private SlotEntity $slot;

	#[Column(type: Types::TEXT)]
	private string $comment;

	#[Column(type: Types::INTEGER)]
	private PollParticipantsEntity $participant;

	/**
	 * @param   int             $id
	 * @param   AnswerTypeEnum  $answer
	 * @param   SlotEntity      $slot
	 * @param   string          $comment
	 * @param   PollParticipantsEntity $participant
	 */
	public function __construct(int $id, AnswerTypeEnum $answer, SlotEntity $slot, string $comment, PollParticipantsEntity $participant)
	{
		$this->id      = $id;
		$this->answer  = $answer;
		$this->slot    = $slot;
		$this->comment = $comment;
		$this->participant = $participant;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): PollAnswerEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getAnswer(): AnswerTypeEnum
	{
		return $this->answer;
	}

	public function setAnswer(AnswerTypeEnum $answer): PollAnswerEntity
	{
		$this->answer = $answer;

		return $this;
	}

	public function getSlot(): SlotEntity
	{
		return $this->slot;
	}

	public function setSlot(SlotEntity $slot): PollAnswerEntity
	{
		$this->slot = $slot;

		return $this;
	}

	public function getComment(): string
	{
		return $this->comment;
	}

	public function setComment(string $comment): PollAnswerEntity
	{
		$this->comment = $comment;

		return $this;
	}

	public function getParticipant(): PollParticipantsEntity
	{
		return $this->participant;
	}

	public function setParticipant(PollParticipantsEntity $participant): PollAnswerEntity
	{
		$this->participant = $participant;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id' => $this->id,
			'answer' => $this->answer->value,
			'slot' => $this->slot->getId(),
			'comment' => $this->comment,
			'participant' => $this->participant->getId(),
		];
	}
}