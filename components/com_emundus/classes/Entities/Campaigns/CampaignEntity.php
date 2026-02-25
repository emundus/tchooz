<?php
/**
 * @package     Tchooz\Entities\Campaigns
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Campaigns;

use DateTime;
use Joomla\CMS\Factory;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Enums\Campaigns\StatusEnum;

class CampaignEntity
{
	private int $id;

	private int $createdBy;

	private string $label;

	private ?string $description;

	private ?string $short_description;

	private DateTime $start_date;

	private DateTime $end_date;

	private int $profile_id;

	private ?ProgramEntity $program;

	private string $year;

	private bool $published;

	private bool $pinned;

	private ?string $alias;

	private bool $visible;

	private ?CampaignEntity $parent;

	private StatusEnum $status;

	private string $timezone;

	private int $files_count = 0;

	// TODO: Refactor when Fabrik was moved into Entities
	private ?array $moreProperties;

	public function __construct(string $label, DateTime $start_date, DateTime $end_date, ?ProgramEntity $program, string $year, ?string $description = '', ?string $short_description = '', int $profile_id = 0, bool $published = true, bool $pinned = false, ?string $alias = '', bool $visible = true, ?CampaignEntity $parent = null, int $id = 0, array $moreProperties = [], int $files_count = 0, int $createdBy = 0)
	{
		$this->id                = $id;
		$this->label             = $label;
		$this->description       = $description;
		$this->short_description = $short_description;
		$this->start_date        = $start_date;
		$this->end_date          = $end_date;
		$this->profile_id        = $profile_id;
		$this->program           = $program;
		$this->year              = $year;
		$this->published         = $published;
		$this->pinned            = $pinned;
		$this->alias             = $alias;
		$this->visible           = $visible;
		$this->parent            = $parent;
		$this->moreProperties    = $moreProperties;
		$this->files_count       = $files_count;
		$this->createdBy         = $createdBy;

		// Determine status based on dates
		$this->timezone = Factory::getApplication()->get('offset', 'Europe/Paris');
		$timezone       = $this->timezone ? new \DateTimeZone($this->timezone) : new \DateTimeZone(date_default_timezone_get());

		$currentDate = new DateTime('now', $timezone);
		if ($currentDate < $this->start_date)
		{
			$this->status = StatusEnum::UPCCOMING;
		}
		elseif ($currentDate >= $this->start_date && $currentDate <= $this->end_date)
		{
			$this->status = StatusEnum::OPEN;
		}
		else
		{
			$this->status = StatusEnum::CLOSED;
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getShortDescription(): string
	{
		return $this->short_description;
	}

	public function setShortDescription(string $short_description): void
	{
		$this->short_description = $short_description;
	}

	public function getStartDate(): DateTime
	{
		return $this->start_date;
	}

	public function setStartDate(DateTime $start_date): void
	{
		$this->start_date = $start_date;
	}

	public function getEndDate(): DateTime
	{
		return $this->end_date;
	}

	public function setEndDate(DateTime $end_date): void
	{
		$this->end_date = $end_date;
	}

	public function getProfileId(): int
	{
		return $this->profile_id;
	}

	public function setProfileId(int $profile_id): void
	{
		$this->profile_id = $profile_id;
	}

	public function getProgram(): ?ProgramEntity
	{
		return $this->program;
	}

	public function setProgram(?ProgramEntity $program): void
	{
		$this->program = $program;
	}

	public function getYear(): string
	{
		return $this->year;
	}

	public function setYear(string $year): void
	{
		$this->year = $year;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function isPinned(): bool
	{
		return $this->pinned;
	}

	public function setPinned(bool $pinned): void
	{
		$this->pinned = $pinned;
	}

	public function getAlias(): ?string
	{
		return $this->alias;
	}

	public function setAlias(?string $alias): void
	{
		$this->alias = $alias;
	}

	public function isVisible(): bool
	{
		return $this->visible;
	}

	public function setVisible(bool $visible): void
	{
		$this->visible = $visible;
	}

	public function getParent(): ?CampaignEntity
	{
		return $this->parent;
	}

	public function setParent(?CampaignEntity $parent): void
	{
		$this->parent = $parent;
	}

	public function getStatus(): StatusEnum
	{
		return $this->status;
	}

	public function setStatus(StatusEnum $status): void
	{
		$this->status = $status;
	}

	public function getTimezone(): string
	{
		return $this->timezone;
	}

	public function setTimezone(string $timezone): void
	{
		$this->timezone = $timezone;
	}

	public function getMoreProperties(): ?array
	{
		return $this->moreProperties;
	}

	public function setMoreProperties(?array $moreProperties): void
	{
		$this->moreProperties = $moreProperties;
	}

	public function getFilesCount(): int
	{
		return $this->files_count;
	}

	public function setFilesCount(int $files_count): void
	{
		$this->files_count = $files_count;
	}

	public function __serialize(): array
	{
		$serialize = get_object_vars($this);
		if (!empty($this->program))
		{
			$serialize['program'] = $this->program->__serialize();
		}
		$serialize['status'] = $this->status->value;

		return $serialize;
	}
}