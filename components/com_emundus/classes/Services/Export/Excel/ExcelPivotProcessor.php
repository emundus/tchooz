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
use Tchooz\Services\Export\Export;

/**
 * Expand each row into N rows based on a pivot target, then group rows from the
 * same application file together.
 *
 * The pivot is picked in two steps by the user: a scope (element / group /
 * evaluation — the latter shown as "Formulaire") and a target id resolved within
 * that scope. Element and group share the same "explode a Fabrik repeat/multi-value
 * column into rows" mechanics — only the entry point differs. Evaluation splits
 * the columns of the evaluation form into one row per submission.
 */
class ExcelPivotProcessor
{
	private FabrikRepository $fabrikRepository;

	/**
	 * Separator the repeated values of a column were aggregated with, as decided by the caller
	 * (ExcelService passes the one it asked EmundusHelperFabrik for). It has no default on purpose:
	 * splitting on anything else cuts inside the values themselves — a currency is stored formatted,
	 * so "600,00 € (EUR)" split on ',' yielded a phantom row holding "00 € (EUR)", read as 0 €.
	 */
	private string $valueSeparator;

	private string $multipleSeparator;

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
	 * @param   array           $files               JSON `files` map, keyed by fnum
	 * @param   array           $headers             JSON `headers` map (used to filter which siblings to expand for repeat groups)
	 * @param   PivotScopeEnum  $scope               Pivot semantic picked by the user
	 * @param   int             $targetId            Id of the target within that scope (form id / group id / element id / evaluation form id)
	 * @param   string          $valueSeparator      Separator the caller aggregated the repeated values with
	 * @param   string          $multipleSeparator   Separator the caller aggregated the rows of a multiple table with
	 */
	public function process(array $files, array $headers, PivotScopeEnum $scope, int $targetId, string $valueSeparator, string $multipleSeparator): array
	{
		$this->valueSeparator      = $valueSeparator;
		$this->multipleSeparator = $multipleSeparator;

		if (empty($files) || $targetId <= 0) {
			return $files;
		}

		$rowsByFnum = array_map(fn(array $file) => [$file], $files);

		$rowsByFnum = match ($scope) {
			PivotScopeEnum::GROUP      => $this->expandByGroup($rowsByFnum, $headers, $targetId),
			PivotScopeEnum::ELEMENT    => $this->expandByElement($rowsByFnum, $headers, $targetId),
			PivotScopeEnum::EVALUATION => $this->expandByEvaluation($rowsByFnum, $headers, $targetId),
		};

		return $this->flatten($rowsByFnum);
	}

	/**
	 * Group scope: split every repeat-group iteration into its own row. All
	 * sibling elements of the group that are present in `$headers` are exploded
	 * together, so the resulting rows stay coherent.
	 *
	 * @param   array<string, list<array>>  $rowsByFnum
	 *
	 * @return array<string, list<array>>
	 */
	private function expandByGroup(array $rowsByFnum, array $headers, int $groupId): array
	{
		$this->fabrikRepository->setElementFilters([]);
		$groupElements = $this->fabrikRepository->getElementsByGroupId($groupId);
		if (empty($groupElements)) {
			return $rowsByFnum;
		}

		$columnsToExpand = [];
		foreach ($groupElements as $groupElement) {
			assert($groupElement instanceof FabrikElementEntity);
			if (array_key_exists($groupElement->getId(), $headers)) {
				$columnsToExpand[] = $groupElement->getId();
			}
		}

		if (empty($columnsToExpand)) {
			return $rowsByFnum;
		}

		return $this->expandRepetitions($rowsByFnum, $headers, $columnsToExpand, $groupElements[0]->getDbTableName());
	}

	/**
	 * Element scope: keeps the historical behavior — explode the picked element's
	 * aggregated value AND every sibling of its group that we're exporting.
	 *
	 * @param   array<string, list<array>>  $rowsByFnum
	 *
	 * @return array<string, list<array>>
	 */
	private function expandByElement(array $rowsByFnum, array $headers, int $elementId): array
	{
		$this->fabrikRepository->setElementFilters([]);
		$elementEntity = $this->fabrikRepository->getElementById($elementId);
		if (empty($elementEntity)) {
			return $rowsByFnum;
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

		return $this->expandRepetitions($rowsByFnum, $headers, $columnsToExpand, $elementEntity->getDbTableName());
	}

	/**
	 * The repetitions of a multiple table (several rows per file, e.g. evaluations) belong to one of
	 * its rows each: the file is split per table row first, so that every repetition keeps the
	 * values of its own row (its evaluator, the other answers) instead of the list of all of them.
	 *
	 * @param   array<string, list<array>>  $rowsByFnum
	 * @param   array<int>                  $columnIds
	 *
	 * @return array<string, list<array>>
	 */
	private function expandRepetitions(array $rowsByFnum, array $headers, array $columnIds, string $tableName): array
	{
		if (Export::isMultipleTable($tableName)) {
			$rowsByFnum = $this->splitRows($rowsByFnum, $this->collectMultipleColumns($tableName, $headers), $this->multipleSeparator);
		}

		return $this->splitRows($rowsByFnum, $columnIds, $this->valueSeparator);
	}

	/**
	 * Evaluation scope: one row per submission of the picked evaluation form.
	 *
	 * Evaluation columns are merged upstream (Export::getData() and the synthetic
	 * `evaluator_<table>` column of ExcelService) with the multiple separator,
	 * ordered by `evaluator ASC`. Pivot row i receives ONLY the i-th item of each
	 * evaluation column, while identity columns stay repeated.
	 *
	 * @param   array<string, list<array>>  $rowsByFnum
	 *
	 * @return array<string, list<array>>
	 */
	private function expandByEvaluation(array $rowsByFnum, array $headers, int $formId): array
	{
		$tableName = $this->resolveEvaluationTable($formId);
		if (empty($tableName)) {
			Log::add(
				'Pivot scope=evaluation could not resolve db table for form ' . $formId,
				Log::WARNING,
				'com_emundus.service.export'
			);

			return $rowsByFnum;
		}

		return $this->splitRows($rowsByFnum, $this->collectMultipleColumns($tableName, $headers), $this->multipleSeparator);
	}

	/**
	 * Identify, among the exported columns, those that hold one value per row of
	 * the multiple table `$tableName`. Two kinds exist:
	 *  - the synthetic evaluator column of evaluation tables, keyed `evaluator_<tableName>`;
	 *  - numeric element ids whose Fabrik element is backed by `$tableName`.
	 *
	 * @return array<int|string>  Column keys
	 */
	private function collectMultipleColumns(string $tableName, array $headers): array
	{
		$columns = [];

		$evaluatorKey = 'evaluator_' . $tableName;
		if (array_key_exists($evaluatorKey, $headers)) {
			$columns[] = $evaluatorKey;
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
				$columns[] = (int) $headerKey;
			}
		}

		return $columns;
	}

	/**
	 * Split every row into as many rows as its longest aggregate among `$columnKeys` has parts:
	 * row i keeps the i-th part of each of these columns (empty when a column has fewer parts),
	 * and every other column stays repeated.
	 *
	 * @param   array<string, list<array>>  $rowsByFnum
	 * @param   array<int|string>           $columnKeys
	 *
	 * @return array<string, list<array>>
	 */
	private function splitRows(array $rowsByFnum, array $columnKeys, string $separator): array
	{
		foreach ($rowsByFnum as $fnum => $rows) {
			$splitRows = [];

			foreach ($rows as $row) {
				$parts = [];
				$count = 1;
				foreach ($columnKeys as $columnKey) {
					if (!isset($row[$columnKey]) || $row[$columnKey] === '') {
						continue;
					}

					$parts[$columnKey] = explode($separator, (string) $row[$columnKey]);
					$count             = max($count, count($parts[$columnKey]));
				}

				for ($i = 0; $i < $count; $i++) {
					$splitRow = $row;
					foreach ($parts as $columnKey => $columnParts) {
						$splitRow[$columnKey] = trim($columnParts[$i] ?? '');
					}
					$splitRows[] = $splitRow;
				}
			}

			$rowsByFnum[$fnum] = $splitRows;
		}

		return $rowsByFnum;
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
	 * Key the rows back the way the JSON `files` map expects them: the first row of a file keeps
	 * the fnum, the next ones get `fnum_1`, `fnum_2`, …, each file's rows staying contiguous.
	 *
	 * @param   array<string, list<array>>  $rowsByFnum
	 */
	private function flatten(array $rowsByFnum): array
	{
		$files = [];

		foreach ($rowsByFnum as $fnum => $rows) {
			foreach (array_values($rows) as $index => $row) {
				$files[$index === 0 ? $fnum : ($fnum . '_' . $index)] = $row;
			}
		}

		return $files;
	}
}
