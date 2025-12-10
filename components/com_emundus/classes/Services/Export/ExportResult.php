<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

class ExportResult
{
	private bool $status;

	private int $progress;

	private string $filePath;

	public function __construct(bool $status, int $progress = 0, string $filePath = '')
	{
		$this->status = $status;
		$this->progress = $progress;
		$this->filePath = $filePath;
	}

	public function isStatus(): bool
	{
		return $this->status;
	}

	public function setStatus(bool $status): void
	{
		$this->status = $status;
	}

	public function getProgress(): int
	{
		return $this->progress;
	}

	public function setProgress(int $progress): void
	{
		$this->progress = $progress;
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}

	public function setFilePath(string $filePath): void
	{
		$this->filePath = $filePath;
	}
}