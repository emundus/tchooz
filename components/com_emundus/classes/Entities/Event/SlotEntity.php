<?php

namespace Tchooz\Entities\Event;

use Tchooz\Entities\Location\RoomEntity;
use Tchooz\Entities\Poll\PollAnswerEntity;

class SlotEntity
{
	private int $id;

	private ?SlotEntity $parent;

	private \DateTime $start;

	private \DateTime $end;

	private ?RoomEntity $room;

	private ?string $locationText = null;

	private int $capacity;

	private string $moreInformations;

	private string $link;

	private ?string $teamsId;

	/**
	 * @var array<PollAnswerEntity>
	 */
	private array $answers;

	/**
	 * @param   int          $id
	 * @param   ?SlotEntity  $parent
	 * @param   \DateTime    $start
	 * @param   \DateTime    $end
	 * @param   ?RoomEntity  $room
	 * @param   int          $capacity
	 * @param   string       $moreInformations
	 * @param   string       $link
	 * @param   string|null  $teamsId
	 */
	public function __construct(int $id, \DateTime $start, \DateTime $end, int $capacity, ?SlotEntity $parent = null, ?RoomEntity $room = null, string $moreInformations = '', string $link = '', ?string $teamsId = null, array $answers = [])
	{
		$this->id               = $id;
		$this->parent           = $parent;
		$this->start            = $start;
		$this->end              = $end;
		$this->room             = $room;
		$this->capacity         = $capacity;
		$this->moreInformations = $moreInformations;
		$this->link             = $link;
		$this->teamsId          = $teamsId;
		$this->answers          = $answers;
	}


	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): SlotEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getParent(): ?SlotEntity
	{
		return $this->parent;
	}

	public function setParent(?SlotEntity $parent): SlotEntity
	{
		$this->parent = $parent;

		return $this;
	}

	public function getStart(): \DateTime
	{
		return $this->start;
	}

	public function setStart(\DateTime $start): SlotEntity
	{
		$this->start = $start;

		return $this;
	}

	public function getEnd(): \DateTime
	{
		return $this->end;
	}

	public function setEnd(\DateTime $end): SlotEntity
	{
		$this->end = $end;

		return $this;
	}

	public function getRoom(): ?RoomEntity
	{
		return $this->room;
	}

	public function setRoom(?RoomEntity $room): SlotEntity
	{
		$this->room = $room;

		return $this;
	}

	public function getLocationText(): ?string
	{
		return $this->locationText;
	}

	public function setLocationText(?string $locationText): SlotEntity
	{
		$this->locationText = $locationText;

		return $this;
	}

	public function getCapacity(): int
	{
		return $this->capacity;
	}

	public function setCapacity(int $capacity): SlotEntity
	{
		$this->capacity = $capacity;

		return $this;
	}

	public function getMoreInformations(): string
	{
		return $this->moreInformations;
	}

	public function setMoreInformations(string $moreInformations): SlotEntity
	{
		$this->moreInformations = $moreInformations;

		return $this;
	}

	public function getLink(): string
	{
		return $this->link;
	}

	public function setLink(string $link): SlotEntity
	{
		$this->link = $link;

		return $this;
	}

	public function getTeamsId(): ?string
	{
		return $this->teamsId;
	}

	public function setTeamsId(?string $teamsId): SlotEntity
	{
		$this->teamsId = $teamsId;

		return $this;
	}

	public function getAnswers(): array
	{
		return $this->answers;
	}

	public function setAnswers(array $answers): SlotEntity
	{
		$this->answers = $answers;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id'       => $this->id,
			'parent'   => $this->parent?->__serialize(),
			'start'    => $this->start->format('Y-m-d H:i:s'),
			'end'      => $this->end->format('Y-m-d H:i:s'),
			'room'          => $this->room?->getId(),
			'location_text' => $this->locationText,
			'capacity' => $this->capacity,
			'answers'  => array_map(fn($answer) => $answer->__serialize(), $this->answers)
		];
	}
}