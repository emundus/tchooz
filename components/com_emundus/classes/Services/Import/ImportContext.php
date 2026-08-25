<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

/**
 * Context passed alongside each row through the pipeline.
 */
final class ImportContext
{
	/** @var string[]  non-blocking advisories collected while processing the row */
	private array $warnings = [];

	public function __construct(
		public readonly string $sourceName,
		public readonly int    $rowNumber,
		public readonly bool   $dryRun = false,
		public readonly ?int   $userId = null
	) {}

	public function withRow(int $rowNumber): self
	{
		return new self($this->sourceName, $rowNumber, $this->dryRun, $this->userId);
	}

	public function addWarning(string $message): void
	{
		$this->warnings[] = $message;
	}

	/** @return string[] */
	public function getWarnings(): array
	{
		return $this->warnings;
	}
}
