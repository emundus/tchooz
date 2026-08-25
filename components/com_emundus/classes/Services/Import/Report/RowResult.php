<?php
/**
 * @package     Tchooz\Services\Import\Report
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Report;

use Tchooz\Enums\Import\RowStatusEnum;

/**
 * Outcome for a single row.
 *
 * - $reasons holds all structured errors for FAILED rows (one entry per error).
 * - $reasons is empty for CREATED and SKIPPED.
 * - $data is the row's canonical values, kept only for FAILED rows so an export
 *   can list them for correction; empty otherwise.
 * - $warnings holds non-blocking advisories (e.g. a referential label that
 *   disagrees with the resolved value); they never change the status.
 */
final class RowResult
{
	/**
	 * @param RowError[]            $reasons
	 * @param array<string, mixed>  $data
	 * @param string[] $warnings
	 */
	public function __construct(
	public readonly string         $sourceName,
		public readonly int            $rowNumber,
		public readonly RowStatusEnum  $status,
		public readonly array          $reasons = [],
		public readonly array          $data = [],
		public readonly array          $warnings = []
	) {}

	public function toArray(): array
	{
		return [
			'source'  => $this->sourceName,
			'row'     => $this->rowNumber,
			'status'  => $this->status->value,
			'errors'  => array_map(static fn (RowError $reason) => $reason->toArray(), $this->reasons),
			'warnings' => $this->warnings,
		];
	}
}
