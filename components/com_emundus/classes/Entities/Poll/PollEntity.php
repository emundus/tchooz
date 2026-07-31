<?php

namespace Tchooz\Entities\Poll;

use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;
use Tchooz\Entities\Event\SlotEntity;
use Tchooz\Entities\Location\LocationEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;

#[Table(name: '#__emundus_setup_polls')]
class PollEntity
{
	private int $id;

	#[Column(type: Types::STRING, length: 255)]
	private string $name;

	#[Column(type: Types::TEXT)]
	private string $description;

	#[Column(type: Types::STRING, length: 10)]
	private  ColorEnum $color;

	#[Column(type: Types::STRING)]
	private StatusEnum $status;

	#[Column(type: Types::DATETIME_MUTABLE)]
	private ?\DateTime $startDate;

	#[Column(type: Types::DATE_MUTABLE)]
	private ?\DateTime $endDate;

	#[Column(type: Types::BOOLEAN, options: ['default' => 0])]
	private bool $canEditAnswers;

	#[Column(name: 'created_by', type: Types::INTEGER)]
	private ?int $createdBy;

	/**
	 * @var array<PollParticipantsEntity>
	 */
	private array $participants = [];

	/**
	 * @var array<SlotEntity>
	 */
	private array $slots = [];

	/**
	 * Ids of the programmes the poll is shared with.
	 *
	 * @var array<int>
	 */
	private array $programs = [];

	/**
	 * @param   int             $id
	 * @param   string          $name
	 * @param   string          $description
	 * @param   ColorEnum       $color
	 * @param   StatusEnum      $status
	 * @param   \DateTime|null  $startDate
	 * @param   \DateTime|null  $endDate
	 * @param   array           $participants
	 * @param   SlotEntity[]    $slots
	 * @param   bool            $canEditAnswers
	 * @param   int|null        $createdBy
	 */
	public function __construct(int $id, string $name, string $description, ColorEnum $color, StatusEnum $status = StatusEnum::UPCCOMING, ?\DateTime $startDate = null, ?\DateTime $endDate = null, array $participants = [], array $slots = [], bool $canEditAnswers = false, ?int $createdBy = null, array $programs = [])
	{
		$this->id             = $id;
		$this->name           = $name;
		$this->description    = $description;
		$this->color          = $color;
		$this->status         = $status;
		$this->startDate      = $startDate;
		$this->endDate        = $endDate;
		$this->participants   = $participants;
		$this->slots          = $slots;
		$this->canEditAnswers = $canEditAnswers;
		$this->createdBy      = $createdBy;
		$this->programs       = array_values(array_unique(array_map('intval', $programs)));
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): PollEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): PollEntity
	{
		$this->name = $name;

		return $this;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): PollEntity
	{
		$this->description = $description;

		return $this;
	}

	public function getColor(): ColorEnum
	{
		return $this->color;
	}

	public function setColor(ColorEnum $color): PollEntity
	{
		$this->color = $color;

		return $this;
	}

	public function getStatus(): StatusEnum
	{
		return $this->status;
	}

	public function setStatus(StatusEnum $status): PollEntity
	{
		$this->status = $status;

		return $this;
	}

	public function getStartDate(): ?\DateTime
	{
		return $this->startDate;
	}

	public function setStartDate(?\DateTime $startDate): PollEntity
	{
		$this->startDate = $startDate;

		return $this;
	}

	public function getEndDate(): ?\DateTime
	{
		return $this->endDate;
	}

	public function setEndDate(?\DateTime $endDate): PollEntity
	{
		$this->endDate = $endDate;

		return $this;
	}

	public function getParticipants(): array
	{
		return $this->participants;
	}

	public function setParticipants(array $participants): PollEntity
	{
		$this->participants = $participants;

		return $this;
	}

	public function getSlots(): array
	{
		return $this->slots;
	}

	public function setSlots(array $slots): PollEntity
	{
		$this->slots = $slots;

		return $this;
	}

	public function canEditAnswers(): bool
	{
		return $this->canEditAnswers;
	}

	public function setCanEditAnswers(bool $canEditAnswers): PollEntity
	{
		$this->canEditAnswers = $canEditAnswers;

		return $this;
	}

	/**
	 * @return array<int>
	 */
	public function getPrograms(): array
	{
		return $this->programs;
	}

	/**
	 * @param   array<int>  $programs
	 */
	public function setPrograms(array $programs): PollEntity
	{
		$this->programs = array_values(array_unique(array_map('intval', $programs)));

		return $this;
	}

	public function getCreatedBy(): ?int
	{
		return $this->createdBy;
	}

	public function setCreatedBy(?int $createdBy): PollEntity
	{
		$this->createdBy = $createdBy;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'description' => $this->description,
			'color' => $this->color->value,
			'status' => $this->status->value,
			'start_date' => $this->startDate?->format('Y-m-d H:i:s'),
			'end_date' => $this->endDate?->format('Y-m-d H:i:s'),
			'can_edit_answers' => $this->canEditAnswers ? 1 : 0,
			'created_by' => $this->createdBy,
			'slots' => array_map(fn($slot) => $slot->__serialize(), $this->slots),
			'participants' => array_map(fn($participant) => $participant->__serialize(), $this->participants),
			'programs' => $this->programs,
		];
	}
}