<?php
/**
 * @package     Tchooz\Entities\Import
 * @subpackage
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Entities\Import;

use Joomla\CMS\User\User;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Import\ImportConflictModeEnum;

/**
 * In-memory representation of one asynchronous import job.
 *
 * It carries the operationalstate of a long-running job (progress, cancelled, failed, linked task) plus the
 * import-specific facts the resumable pipeline needs (entity type, conflict mode,
 * source file, resume cursor, cumulative report).
 */
class ImportEntity
{
	private int $id;

	private \DateTime $createdAt;

	private User $createdBy;

	private string $type;

	private string $filename;

	private string $originalFilename;

	private string $format;

	private ImportConflictModeEnum $conflictMode;

	private ?TaskEntity $task;

	private float $progress;

	private int $totalRows;

	private int $lastProcessedRow;

	private array $report;

	private bool $cancelled;

	private bool $failed;

	public function __construct(
		int                    $id,
		\DateTime              $createdAt,
		User                   $createdBy,
		string                 $type,
		string                 $filename,
		string                 $originalFilename,
		string                 $format,
		ImportConflictModeEnum $conflictMode,
		?TaskEntity            $task = null,
		float                  $progress = 0,
		int                    $totalRows = 0,
		int                    $lastProcessedRow = 0,
		array                  $report = [],
		bool                   $cancelled = false,
		bool                   $failed = false
	)
	{
		$this->id               = $id;
		$this->createdAt        = $createdAt;
		$this->createdBy        = $createdBy;
		$this->type             = $type;
		$this->filename         = $filename;
		$this->originalFilename = $originalFilename;
		$this->format           = $format;
		$this->conflictMode     = $conflictMode;
		$this->task             = $task;
		$this->progress         = $progress;
		$this->totalRows        = $totalRows;
		$this->lastProcessedRow = $lastProcessedRow;
		$this->report           = $report;
		$this->cancelled        = $cancelled;
		$this->failed           = $failed;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getCreatedAt(): \DateTime
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTime $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	public function getCreatedBy(): User
	{
		return $this->createdBy;
	}

	public function setCreatedBy(User $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): void
	{
		$this->type = $type;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function setFilename(string $filename): void
	{
		$this->filename = $filename;
	}

	public function getOriginalFilename(): string
	{
		return $this->originalFilename;
	}

	public function setOriginalFilename(string $originalFilename): void
	{
		$this->originalFilename = $originalFilename;
	}

	public function getFormat(): string
	{
		return $this->format;
	}

	public function setFormat(string $format): void
	{
		$this->format = $format;
	}

	public function getConflictMode(): ImportConflictModeEnum
	{
		return $this->conflictMode;
	}

	public function setConflictMode(ImportConflictModeEnum $conflictMode): void
	{
		$this->conflictMode = $conflictMode;
	}

	public function getTask(): ?TaskEntity
	{
		return $this->task;
	}

	public function setTask(?TaskEntity $task): void
	{
		$this->task = $task;
	}

	public function getProgress(): float
	{
		return $this->progress;
	}

	public function setProgress(float $progress): void
	{
		$this->progress = $progress;
	}

	public function getTotalRows(): int
	{
		return $this->totalRows;
	}

	public function setTotalRows(int $totalRows): void
	{
		$this->totalRows = $totalRows;
	}

	public function getLastProcessedRow(): int
	{
		return $this->lastProcessedRow;
	}

	public function setLastProcessedRow(int $lastProcessedRow): void
	{
		$this->lastProcessedRow = $lastProcessedRow;
	}

	public function getReport(): array
	{
		return $this->report;
	}

	public function setReport(array $report): void
	{
		$this->report = $report;
	}

	public function isCancelled(): bool
	{
		return $this->cancelled;
	}

	public function setCancelled(bool $cancelled): void
	{
		$this->cancelled = $cancelled;
	}

	public function isFailed(): bool
	{
		return $this->failed;
	}

	public function setFailed(bool $failed): void
	{
		$this->failed = $failed;
	}

	public function __serialize(): array
	{
		return [
			'id'               => $this->id,
			'createdAt'        => $this->createdAt,
			'createdBy'        => $this->createdBy,
			'type'             => $this->type,
			'filename'         => $this->filename,
			'originalFilename' => $this->originalFilename,
			'format'           => $this->format,
			'conflictMode'     => $this->conflictMode->value,
			'task'             => $this->task,
			'progress'         => $this->progress,
			'totalRows'        => $this->totalRows,
			'lastProcessedRow' => $this->lastProcessedRow,
			'report'           => $this->report,
			'cancelled'        => $this->cancelled,
			'failed'           => $this->failed,
		];
	}
}