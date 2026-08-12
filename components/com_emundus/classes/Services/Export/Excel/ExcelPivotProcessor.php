<?php
/**
 * @package     Tchooz\Services\Export\Excel
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\Excel;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Export\PivotScopeEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;

/**
 * Expand each row into N rows based on a pivot target, then group rows from the
 * same application file together.
 *
 * The pivot is picked in two steps by the user: a scope (element / group /
 * evaluation — the latter shown as "Formulaire") and a target id resolved within
 * that scope. Element and group share the same "explode a Fabrik repeat/multi-value
 * column into rows" mechanics — only the entry point differs. Evaluation is
 * fundamentally different: it queries the evaluation table for that fnum and
 * duplicates the row per submission.
 */
class ExcelPivotProcessor
{
	private FabrikRepository $fabrikRepository;

	/**
	 * The service reuses a single FabrikRepository across the whole export lifecycle;
	 * its `$elementFilters` array accumulates across `getElementById()` / `getData()` /
	 * pivot lookups and its later merges silently narrow subsequent queries (e.g. keeping
	 * a stale `id => X` filter is what prevented `getElementsByGroupId()` from returning
	 * more than the pivot element itself). We instantiate our own instance so filter
	 * state stays local to pivot processing.
	 */
	public function __construct(?FabrikRepository $fabrikRepository = null)
	{
		if ($fabrikRepository === null) {
			$fabrikRepository = new FabrikRepository();
			$fabrikRepository->setFactory(new FabrikFactory($fabrikRepository));
		}
		$this->fabrikRepository = $fabrikRepository;
	}

	/**
	 * @param   array           $files    JSON `files` map, keyed by fnum
	 * @param   array           $headers  JSON `headers` map (used to filter which siblings to expand for repeat groups)
	 * @param   PivotScopeEnum  $scope    Pivot semantic picked by the user
	 * @param   int             $targetId Id of the target within that scope (form id / group id / element id / evaluation form id)
	 */
	public function process(array $files, array $headers, PivotScopeEnum $scope, int $targetId): array
	{
		if (empty($files) || $targetId <= 0) {
			return $files;
		}

		// Capture the incoming dossier order *before* expansion. Expansion appends
		// extra rows (`fnum_1`, `fnum_2`, …) at the tail of the array, so the raw
		// key order no longer reflects the original order — we restore it from here.
		$baseFnumOrder = array_keys($files);

		$expanded = match ($scope) {
			PivotScopeEnum::GROUP      => $this->expandByGroup($files, $headers, $targetId),
			PivotScopeEnum::ELEMENT    => $this->expandByElement($files, $headers, $targetId),
			PivotScopeEnum::EVALUATION => $this->expandByEvaluation($files, $headers, $targetId),
		};

		return $this->groupByFnum($expanded, $baseFnumOrder);
	}

	/**
	 * Group scope: split every repeat-group iteration into its own row. All
	 * sibling elements of the group that are present in `$headers` are exploded
	 * together, so the resulting rows stay coherent.
	 */
	private function expandByGroup(array $files, array $headers, int $groupId): array
	{
		$this->fabrikRepository->setElementFilters([]);
		$groupElements = $this->fabrikRepository->getElementsByGroupId($groupId);
		if (empty($groupElements)) {
			return $files;
		}

		$columnsToExpand = [];
		foreach ($groupElements as $groupElement) {
			assert($groupElement instanceof FabrikElementEntity);
			if (array_key_exists($groupElement->getId(), $headers)) {
				$columnsToExpand[] = $groupElement->getId();
			}
		}

		if (empty($columnsToExpand)) {
			return $files;
		}

		foreach ($files as $fnum => $file) {
			foreach ($columnsToExpand as $columnId) {
				$this->splitColumn($files, $fnum, $file, $columnId);
			}
		}

		return $files;
	}

	/**
	 * Element scope: keeps the historical behavior — explode the picked element's
	 * comma-separated value AND every sibling of its group that we're exporting.
	 */
	private function expandByElement(array $files, array $headers, int $elementId): array
	{
		$this->fabrikRepository->setElementFilters([]);
		$elementEntity = $this->fabrikRepository->getElementById($elementId);
		if (empty($elementEntity)) {
			return $files;
		}
		assert($elementEntity instanceof FabrikElementEntity);

		$columnsToExpand = [$elementId];

		$groupParams = $elementEntity->getGroupParams();
		if (!empty($groupParams) && (int) ($groupParams->repeat_group_button ?? 0) === 1) {
			// Second consecutive repository call — reset filters so `id => $elementId`
			// (set by getElementById above) doesn't narrow the group query to just the pivot.
			$this->fabrikRepository->setElementFilters([]);
			$groupElements = $this->fabrikRepository->getElementsByGroupId($elementEntity->getGroupId());
			foreach ($groupElements as $groupElement) {
				assert($groupElement instanceof FabrikElementEntity);
				if (
					$groupElement->getId() !== $elementId
					&& array_key_exists($groupElement->getId(), $headers)
				) {
					$columnsToExpand[] = $groupElement->getId();
				}
			}
		}

		foreach ($files as $fnum => $file) {
			foreach ($columnsToExpand as $columnId) {
				$this->splitColumn($files, $fnum, $file, $columnId);
			}
		}

		return $files;
	}

	/**
	 * Evaluation scope: one row per submission of the picked evaluation form.
	 * Reads the form's underlying evaluation table to count submissions per fnum
	 * and duplicates the base row accordingly.
	 *
	 * Evaluation columns are merged upstream in Export::getData() as
	 * comma-separated lists ordered by `evaluator ASC` (evaluation elements use
	 * ',' as separator, the synthetic `evaluator_<table>` column uses ', '). A
	 * naive duplication would repeat the whole "eval1, eval2" list on every
	 * pivot row. We therefore de-aggregate: pivot row i receives ONLY the i-th
	 * item of each evaluation column, while identity columns stay repeated.
	 */
	private function expandByEvaluation(array $files, array $headers, int $formId): array
	{
		$tableName = $this->resolveEvaluationTable($formId);
		if (empty($tableName)) {
			Log::add(
				'Pivot scope=evaluation could not resolve db table for form ' . $formId,
				Log::WARNING,
				'com_emundus.service.export'
			);

			return $files;
		}

		$evaluationColumns = $this->collectEvaluationColumns($tableName, $headers);

		$db = Factory::getContainer()->get('DatabaseDriver');

		foreach ($files as $fnum => $file) {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName($tableName))
				->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

			$db->setQuery($query);
			$count = (int) $db->loadResult();

			if ($count < 2) {
				continue;
			}

			// Duplicate the base row into $count rows, then de-aggregate the
			// evaluation columns so each row carries a single evaluation's value.
			for ($i = 1; $i < $count; $i++) {
				$files[$fnum . '_' . $i] = $file;
			}

			for ($i = 0; $i < $count; $i++) {
				$rowKey        = $i === 0 ? $fnum : ($fnum . '_' . $i);
				$files[$rowKey] = $this->deAggregateEvaluationRow($files[$rowKey], $evaluationColumns, $i);
			}
		}

		return $files;
	}

	/**
	 * Identify, among the exported columns, those that hold per-evaluation values
	 * for the picked evaluation table. Two kinds exist:
	 *  - the synthetic evaluator column, keyed `evaluator_<tableName>`;
	 *  - numeric element ids whose Fabrik element is backed by `$tableName`.
	 *
	 * @return array<int|string, string>  Map column key => concatenation separator
	 */
	private function collectEvaluationColumns(string $tableName, array $headers): array
	{
		$columns = [];

		$evaluatorKey = 'evaluator_' . $tableName;
		if (array_key_exists($evaluatorKey, $headers)) {
			// Evaluator names are joined with ', ' upstream (ExcelService).
			$columns[$evaluatorKey] = ', ';
		}

		foreach (array_keys($headers) as $headerKey) {
			if (!is_int($headerKey) && !ctype_digit((string) $headerKey)) {
				continue;
			}

			$this->fabrikRepository->setElementFilters([]);
			$element = $this->fabrikRepository->getElementById((int) $headerKey);
			if (empty($element)) {
				continue;
			}
			assert($element instanceof FabrikElementEntity);

			if ($element->getDbTableName() === $tableName) {
				// Evaluation element values are joined with ',' upstream (Export::getData).
				$columns[(int) $headerKey] = ',';
			}
		}

		return $columns;
	}

	/**
	 * Return a copy of the row keeping only the $index-th value of each
	 * evaluation column. Identity (non-evaluation) columns are left untouched so
	 * they stay repeated across the dossier's pivot rows. Out-of-range indexes
	 * yield an empty cell rather than an error.
	 *
	 * @param   array                        $row               Row to de-aggregate
	 * @param   array<int|string, string>    $evaluationColumns Column key => separator
	 * @param   int                          $index             Zero-based evaluation index for this row
	 */
	private function deAggregateEvaluationRow(array $row, array $evaluationColumns, int $index): array
	{
		$deAggregated = $row;

		foreach ($evaluationColumns as $columnKey => $separator) {
			if (!array_key_exists($columnKey, $deAggregated) || $deAggregated[$columnKey] === '') {
				continue;
			}

			$parts                     = explode($separator, (string) $deAggregated[$columnKey]);
			$deAggregated[$columnKey]  = array_key_exists($index, $parts) ? trim($parts[$index]) : '';
		}

		return $deAggregated;
	}

	/**
	 * Split a single column's comma-separated value across successive rows.
	 * Existing base row keeps index 0; extra values get suffixed keys.
	 */
	private function splitColumn(array &$files, string $fnum, array $file, int $columnId): void
	{
		if (empty($file[$columnId])) {
			return;
		}

		$parts = explode(',', $file[$columnId]);

		foreach ($parts as $key => $value) {
			$index = $key === 0 ? $fnum : ($fnum . '_' . $key);

			if (empty($files[$index])) {
				$files[$index] = $files[$fnum];
			}

			$files[$index][$columnId] = trim($value);
		}
	}

	/**
	 * Look up the Fabrik list table backing a given evaluation form.
	 */
	private function resolveEvaluationTable(int $formId): ?string
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('l.db_table_name'))
			->from($db->quoteName('#__fabrik_lists', 'l'))
			->where($db->quoteName('l.form_id') . ' = ' . (int) $formId);

		$db->setQuery($query);
		$table = $db->loadResult();

		return !empty($table) ? $table : null;
	}

	/**
	 * Re-order the expanded map so every dossier's rows (base row + its
	 * `fnum_1`, `fnum_2`, … pivot rows) are contiguous, while preserving the
	 * original dossier order captured before expansion.
	 *
	 * Expansion appends extra rows at the tail of the array, so the raw key order
	 * interleaves dossiers (`A, B, A_1, B_1`). We rebuild the array dossier by
	 * dossier: the base row first, then each of its suffixed rows in pivot-index
	 * order. Base fnums are matched exactly (never parsed from keys) because
	 * eMundus fnums themselves contain underscores.
	 *
	 * @param   array          $files          Expanded rows keyed by fnum / fnum_N
	 * @param   array<string>  $baseFnumOrder  Base fnums in their original order
	 */
	private function groupByFnum(array $files, array $baseFnumOrder): array
	{
		$grouped = [];

		foreach ($baseFnumOrder as $baseFnum) {
			// Base row first (index 0 keeps the raw fnum as its key).
			if (array_key_exists($baseFnum, $files)) {
				$grouped[$baseFnum] = $files[$baseFnum];
			}

			// Then the pivot rows for this dossier, in ascending index order.
			$prefix = $baseFnum . '_';
			$pivotRows = [];
			foreach ($files as $key => $file) {
				if (!str_starts_with((string) $key, $prefix)) {
					continue;
				}

				$suffix = substr((string) $key, strlen($prefix));
				if (ctype_digit($suffix)) {
					$pivotRows[(int) $suffix] = [$key, $file];
				}
			}

			ksort($pivotRows);
			foreach ($pivotRows as [$key, $file]) {
				$grouped[$key] = $file;
			}
		}

		// Safety net: append any row that didn't match a known base fnum so the
		// row count can never shrink (keeps the "correct number of lines" guarantee).
		foreach ($files as $key => $file) {
			if (!array_key_exists($key, $grouped)) {
				$grouped[$key] = $file;
			}
		}

		return $grouped;
	}
}
