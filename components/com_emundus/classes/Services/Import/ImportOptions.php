<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Services\Import\Report\ImportReport;

/**
 * Pipeline-level configuration.
 *
 * The last three properties are dormant hooks reserved for an upcoming
 * async-friendly mode (see ExcelService for the resumable pattern they will
 * follow). They are no-ops as long as their defaults are kept and have zero
 * cost on synchronous callers.
 */
final class ImportOptions
{
	/**
	 * @param  bool      $dryRun             Roll back persistence even on success.
	 * @param  bool      $stopOnError        Stop the loop on the first failed row.
	 * @param  int|null  $userId             Optional caller id propagated via ImportContext.
	 * @param  int       $skipUntilRow       Rows whose number is <= this value are skipped.
	 *                                       Used by the async wrapper to resume a job from
	 *                                       the row right after the last persisted one.
	 * @param  int|null  $timeBudgetSeconds  When set, the pipeline checks elapsed time
	 *                                       between rows and breaks once the budget is
	 *                                       exhausted (so a slice fits inside one cron run).
	 * @param  callable|null  $onCheckpoint  Optional callback fired right before the
	 *                                       pipeline breaks on time budget. Signature:
	 *                                       (int $lastProcessedRow, ImportReport $report): void.
	 *                                       Lets the async wrapper persist the slice state.
	 * @param  float    $maxUnknownHeaderRatio  Threshold on the fraction of source headers
	 *                                       not recognized by the ColumnMap. When the ratio
	 *                                       is **at or above** this value, the pipeline
	 *                                       aborts with a global error ("wrong file
	 *                                       uploaded"). Defaults to 0.5 — half the file being
	 *                                       foreign is enough to reject. Set to 1.0 to
	 *                                       disable the check entirely (only the
	 *                                       "zero matched columns" case stays fatal).
	 * @param  ImportConflictModeEnum $conflictMode  Policy for rows whose lookup hits an
	 *                                       existing record:
	 *                                         - SKIP       : ignore (default, safe).
	 *                                         - UPDATE     : overwrite the existing row
	 *                                                        (requires an UpdatableEntityImporter).
	 *                                         - CREATE_NEW : skip the existence check and
	 *                                                        create a duplicate.
	 */
	public function __construct(
		public readonly bool $dryRun = false,
		public readonly bool $stopOnError = false,
		public readonly ?int $userId = null,
		public readonly int  $skipUntilRow = 0,
		public readonly ?int $timeBudgetSeconds = null,
		public readonly mixed $onCheckpoint = null,
		public readonly float $maxUnknownHeaderRatio = 0.5,
		public readonly ImportConflictModeEnum $conflictMode = ImportConflictModeEnum::SKIP
	) {}
}
