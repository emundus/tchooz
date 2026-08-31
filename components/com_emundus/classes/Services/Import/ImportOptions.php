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
	 * @param  callable|null  $onCheckpoint  Optional callback fired once per run to report
	 *                                       the slice outcome. Signature:
	 *                                       (int $lastProcessedRow, ImportReport $report, bool $completed): void.
	 *                                       Fired with $completed = false when the loop breaks
	 *                                       on the time budget (more rows remain), and with
	 *                                       $completed = true when the source has been fully
	 *                                       consumed. Lets the async wrapper learn the resume
	 *                                       cursor and whether the job is done.
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
	 * @param  bool      $validateOnly       When true, the pipeline stops after validation
	 *                                       (required fields + type rules + importer->validate())
	 *                                       and reports each passing row as VALID — it never
	 *                                       calls exists() nor persist(). No DB write, no per-row
	 *                                       lookup, so it stays cheap even on huge files. Powers
	 *                                       the synchronous dry-run preview. Appended last so
	 *                                       existing positional/named callers are unaffected.
	 * @param  callable|null  $onProgress    Optional callback fired every $progressEveryRows
	 *                                       processed rows, with the committed cursor so the
	 *                                       async wrapper can persist intermediate progress and
	 *                                       the UI bar advances *within* a slice (not only at its
	 *                                       end). Signature: (int $lastProcessedRow, ImportReport
	 *                                       $report): void. All rows up to $lastProcessedRow are
	 *                                       already committed, so it also tightens crash recovery.
	 * @param  int       $progressEveryRows  Cadence (in processed rows) of the $onProgress
	 *                                       callback. Defaults to 250 — frequent enough for a
	 *                                       smooth bar, rare enough to keep the extra writes
	 *                                       negligible. Ignored when $onProgress is null.
	 */
	public function __construct(
		public readonly bool $dryRun = false,
		public readonly bool $stopOnError = false,
		public readonly ?int $userId = null,
		public readonly int  $skipUntilRow = 0,
		public readonly ?int $timeBudgetSeconds = null,
		public readonly mixed $onCheckpoint = null,
		public readonly float $maxUnknownHeaderRatio = 0.5,
		public readonly ImportConflictModeEnum $conflictMode = ImportConflictModeEnum::SKIP,
		public readonly bool $validateOnly = false,
		public readonly mixed $onProgress = null,
		public readonly int $progressEveryRows = 250
	) {}
}
