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
 * - $reasons holds all error messages for FAILED rows (one entry per error).
 * - $reasons is empty for CREATED and SKIPPED.
 * - $warnings holds non-blocking advisories (e.g. a referential label that
 *   disagrees with the resolved value); they never change the status.
 */
final class RowResult
{
	/**
	 * @param string[] $reasons
	 * @param string[] $warnings
	 */
	public function __construct(
		public readonly string         $sourceName,
		public readonly int            $rowNumber,
		public readonly RowStatusEnum  $status,
		public readonly array          $reasons = [],
		public readonly array          $warnings = []
	) {}

	public function toArray(): array
	{
		return [
			'source'   => $this->sourceName,
			'row'      => $this->rowNumber,
			'status'   => $this->status->value,
			'reasons'  => $this->reasons,
			'warnings' => $this->warnings,
		];
	}
}
