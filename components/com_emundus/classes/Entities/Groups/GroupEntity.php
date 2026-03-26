<?php
/**
 * @package     Tchooz\Entities\Groups
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Groups;

use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Entities\Programs\ProgramEntity;

class GroupEntity
{
	private int $id;

	private string $label;

	private string $description;

	private bool $published;

	/**
	 * @var array<ProgramEntity>
	 */
	private array $programs;

	// TODO: Move to a ColorClassEnum
	private string $class;

	private bool $anonymize;

	private bool $filterStatus;

	/**
	 * @var array<StatusEntity>
	 */
	private array $statuses;

	private array $visibleGroups;

	private array $visibleAttachments;

	/**
	 * @param   int     $id
	 * @param   string  $label
	 * @param   string  $description
	 * @param   bool    $published
	 * @param   array   $programs
	 * @param   bool    $anonymize
	 * @param   bool    $filterStatus
	 * @param   array   $statuses
	 * @param   array   $visibleGroups
	 * @param   array   $visibleAttachments
	 * @param   string  $class
	 */
	public function __construct(
		int $id,
		string $label,
		string $description,
		bool $published,
		array $programs,
		bool $anonymize,
		bool $filterStatus,
		array $statuses,
		array $visibleGroups = [],
		array $visibleAttachments = [],
		string $class = 'label-blue-2'
	)
	{
		$this->id                 = $id;
		$this->label              = $label;
		$this->description        = $description;
		$this->published          = $published;
		$this->programs           = $programs;
		$this->anonymize          = $anonymize;
		$this->filterStatus       = $filterStatus;
		$this->statuses           = $statuses;
		$this->visibleGroups      = $visibleGroups;
		$this->visibleAttachments = $visibleAttachments;
		$this->class              = $class;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): GroupEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): GroupEntity
	{
		$this->label = $label;

		return $this;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): GroupEntity
	{
		$this->description = $description;

		return $this;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): GroupEntity
	{
		$this->published = $published;

		return $this;
	}

	public function getPrograms(): array
	{
		return $this->programs;
	}

	public function setPrograms(array $programs): GroupEntity
	{
		$this->programs = $programs;

		return $this;
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function setClass(string $class): GroupEntity
	{
		$this->class = $class;

		return $this;
	}

	public function isAnonymize(): bool
	{
		return $this->anonymize;
	}

	public function setAnonymize(bool $anonymize): GroupEntity
	{
		$this->anonymize = $anonymize;

		return $this;
	}

	public function isFilterStatus(): bool
	{
		return $this->filterStatus;
	}

	public function setFilterStatus(bool $filterStatus): GroupEntity
	{
		$this->filterStatus = $filterStatus;

		return $this;
	}

	public function getStatuses(): array
	{
		return $this->statuses;
	}

	public function setStatuses(array $statuses): GroupEntity
	{
		$this->statuses = $statuses;

		return $this;
	}

	public function getVisibleGroups(): array
	{
		return $this->visibleGroups;
	}

	public function setVisibleGroups(array $visibleGroups): GroupEntity
	{
		$this->visibleGroups = $visibleGroups;

		return $this;
	}

	public function getVisibleAttachments(): array
	{
		return $this->visibleAttachments;
	}

	public function setVisibleAttachments(array $visibleAttachments): GroupEntity
	{
		$this->visibleAttachments = $visibleAttachments;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id'                  => $this->id,
			'label'               => $this->label,
			'description'         => $this->description,
			'published'           => $this->published,
			'programs'            => array_map(fn(ProgramEntity $program) => $program->__serialize(), $this->programs),
			'class'               => $this->class,
			'anonymize'           => $this->anonymize,
			'filter_status'       => $this->filterStatus,
			'statuses'            => array_map(fn(StatusEntity $status) => $status->__serialize(), $this->statuses),
			'status'              => implode(',', array_map(fn(StatusEntity $status) => $status->getStep(), $this->statuses)),
			'visible_groups'      => $this->visibleGroups,
			'visible_attachments' => $this->visibleAttachments
		];
	}
}