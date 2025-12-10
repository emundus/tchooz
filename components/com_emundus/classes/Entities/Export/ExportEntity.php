<?php
/**
 * @package     Tchooz\Entities\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Export;

use Joomla\CMS\User\User;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Export\ExportFormatEnum;

class ExportEntity
{
	private int $id;

	private \DateTime $createdAt;

	private User $createdBy;

	private string $filename;

	private ?\DateTime $expiredAt;

	private ?TaskEntity $task;

	private int $hits;

	private int $progress;

	private ExportFormatEnum $format;

	private bool $cancelled = false;

	private bool $failed = false;

	public function __construct(int $id, \DateTime $createdAt, User $createdBy, string $filename, ExportFormatEnum $format, ?\DateTime $expiredAt, ?TaskEntity $task, int $hits, int $progress = 0, bool $cancelled = false, bool $failed = false)
	{
		$this->id        = $id;
		$this->createdAt = $createdAt;
		$this->createdBy = $createdBy;
		$this->filename  = $filename;
		$this->format    = $format;
		$this->expiredAt = $expiredAt;
		$this->task      = $task;
		$this->hits      = $hits;
		$this->progress  = $progress;
		$this->cancelled = $cancelled;
		$this->failed    = $failed;
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

	public function setCreatedAt(\DateTime $created_at): void
	{
		$this->createdAt = $created_at;
	}

	public function getCreatedBy(): User
	{
		return $this->createdBy;
	}

	public function setCreatedBy(User $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getFilename(): string
	{
		return $this->filename;
	}

	public function setFilename(string $filename): void
	{
		$this->filename = $filename;
	}

	public function getFormat(): ExportFormatEnum
	{
		return $this->format;
	}

	public function setFormat(ExportFormatEnum $format): void
	{
		$this->format = $format;
	}

	public function getExpiredAt(): ?\DateTime
	{
		return $this->expiredAt;
	}

	public function setExpiredAt(\DateTime $expired_at): void
	{
		$this->expiredAt = $expired_at;
	}

	public function getTask(): ?TaskEntity
	{
		return $this->task;
	}

	public function setTask(?TaskEntity $task): void
	{
		$this->task = $task;
	}

	public function getHits(): int
	{
		return $this->hits;
	}

	public function setHits(int $hits): void
	{
		$this->hits = $hits;
	}

	public function getProgress(): int
	{
		return $this->progress;
	}

	public function setProgress(int $progress): void
	{
		$this->progress = $progress;
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
			'id'        => $this->id,
			'createdAt' => $this->createdAt,
			'createdBy' => $this->createdBy,
			'filename'  => $this->filename,
			'expiredAt' => $this->expiredAt,
			'task'      => $this->task,
			'hits'      => $this->hits,
			'progress'  => $this->progress,
			'format'    => $this->format,
			'cancelled' => $this->cancelled,
			'failed'    => $this->failed,
		];
	}
}