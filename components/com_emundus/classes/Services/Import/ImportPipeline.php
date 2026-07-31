<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Enums\Import\RowStatusEnum;
use Tchooz\Services\Import\Mapping\ColumnMap;
use Tchooz\Services\Import\Mapping\RowMapper;
use Tchooz\Services\Import\Report\ImportReport;
use Tchooz\Services\Import\Source\ImportSourceInterface;
use Tchooz\Services\Import\Validation\TypeValidator;

/**
 * Source-agnostic, entity-agnostic import orchestrator.
 *
 * For every non-empty row of the source it:
 *   1. translates raw headers to canonical names via the importer's ColumnMap;
 *   2. checks required canonical fields;
 *   3. delegates business validation to the importer;
 *   4. asks the importer whether the row already exists (skipped if so);
 *   5. opens a database transaction, calls $importer->persist(), then either
 *      commits, or rolls back on any throwable / when running in dry-run mode.
 *
 * On rollback, the row is reported as failed and the loop moves on (unless
 * stopOnError is set in the options).
 */
final class ImportPipeline
{
	private DatabaseInterface $db;
	private TypeValidator     $typeValidator;

	public function __construct(?DatabaseInterface $db = null, ?TypeValidator $typeValidator = null)
	{
		$this->db            = $db ?? Factory::getContainer()->get(DatabaseInterface::class);
		$this->typeValidator = $typeValidator ?? new TypeValidator();
	}

	public function run(
		ImportSourceInterface    $source,
		EntityImporterInterface  $importer,
		?ImportOptions           $options = null
	): ImportReport
	{
		$options   = $options ?? new ImportOptions();
		$report    = new ImportReport();
		$columnMap = $importer->getColumnMap();
		$mapper    = new RowMapper($columnMap);
		$required  = $columnMap->requiredFields();

		// Fail fast: each importer declares which conflict modes it can honour
		// (via AbstractEntityImporter::getSupportedModes() by default). Asking
		// for an unsupported mode is a programming error — surface it before
		// the loop rather than crashing mid-stream.
		if (!in_array($options->conflictMode, $importer->getSupportedModes(), true))
		{
			$report->addGlobalError(Text::sprintf(
				'COM_EMUNDUS_IMPORT_MODE_NOT_SUPPORTED',
				$options->conflictMode->value,
				$importer->getType()
			));
			return $report;
		}

		// Pre-flight: detect wrong-entity uploads up front so we never silently
		// drop foreign columns. If the check trips, the report carries a global
		// error and no row is processed.
		if (!$this->preflightHeaders($source, $columnMap, $options, $report))
		{
			return $report;
		}

		$deadline = $options->timeBudgetSeconds !== null
			? microtime(true) + $options->timeBudgetSeconds
			: null;

		$lastProcessedRow = $options->skipUntilRow;

		foreach ($source as $rowNumber => $rawRow)
		{
			$rowNumber = (int) $rowNumber;

			// Async resume: rows already persisted on a previous slice are skipped.
			if ($rowNumber <= $options->skipUntilRow)
			{
				continue;
			}

			// Time budget: leave the loop cleanly so the wrapper can persist
			// the cumulative report and re-enqueue itself.
			if ($deadline !== null && microtime(true) >= $deadline)
			{
				if (is_callable($options->onCheckpoint))
				{
					($options->onCheckpoint)($lastProcessedRow, $report);
				}
				break;
			}

			$context = new ImportContext($source->getName(), $rowNumber, $options->dryRun, $options->userId);
			$row     = $mapper->map($rawRow);

			if (RowMapper::isRowEmpty($row))
			{
				$lastProcessedRow = $rowNumber;
				continue;
			}

			$missing = $this->collectMissing($row, $required);
			if (!empty($missing))
			{
				$report->add($context, RowStatusEnum::FAILED, [
					Text::sprintf('COM_EMUNDUS_IMPORT_MISSING_REQUIRED_FIELDS', implode(', ', $missing)),
				]);
				if ($options->stopOnError) break;
				continue;
			}

			// Generic type-driven validation: errors short-circuit before the
			// importer's custom validate() so business rules only see rows that
			// already match their declared types/formats.
			$typeErrors = $this->validateTypes($row, $columnMap);
			if (!empty($typeErrors))
			{
				$report->add($context, RowStatusEnum::FAILED, $typeErrors);
				if ($options->stopOnError) break;
				continue;
			}

			$validationErrors = $importer->validate($row, $context);
			if (!empty($validationErrors))
			{
				$report->add($context, RowStatusEnum::FAILED, $validationErrors);
				if ($options->stopOnError) break;
				continue;
			}

			// Conflict resolution: SKIP / UPDATE rely on exists(), CREATE_NEW skips the
			// lookup entirely and always inserts a fresh record.
			if ($options->conflictMode !== ImportConflictModeEnum::CREATE_NEW)
			{
				try
				{
					$alreadyExists = $importer->exists($row, $context);
				}
				catch (\Throwable $e)
				{
					$report->add($context, RowStatusEnum::FAILED, [$e->getMessage()]);
					$this->logFailure($context, $e);
					if ($options->stopOnError) break;
					continue;
				}

				if ($alreadyExists)
				{
					if ($options->conflictMode === ImportConflictModeEnum::SKIP)
					{
						$report->add($context, RowStatusEnum::SKIPPED);
						continue;
					}

					// UPDATE branch.
					$updated = $this->updateRow($importer, $row, $context, $options, $report);
					if (!$updated && $options->stopOnError)
					{
						break;
					}
					continue;
				}
			}

			$persisted = $this->persistRow($importer, $row, $context, $options, $report);

			if (!$persisted && $options->stopOnError)
			{
				break;
			}
		}

		return $report;
	}

	/**
	 * @param array<string, mixed> $row
	 * @param string[]             $required
	 *
	 * @return string[]  canonical names of missing fields
	 */
	private function collectMissing(array $row, array $required): array
	{
		$missing = [];

		foreach ($required as $field)
		{
			$value = $row[$field] ?? null;
			if ($value === null || (is_string($value) && trim($value) === ''))
			{
				$missing[] = $field;
			}
		}

		return $missing;
	}

	/**
	 * Inspects the source headers against the ColumnMap and decides whether to
	 * continue with the import. Returns true when processing should proceed,
	 * false when the report carries a fatal global error.
	 *
	 *   - All headers unknown      → fatal (file is empty or completely foreign).
	 *   - Unknown ratio > threshold → fatal (likely wrong entity selected).
	 *   - Some unknown, below ratio → recorded as a warning, processing continues.
	 *   - All known                 → no-op.
	 */
	private function preflightHeaders(
		ImportSourceInterface $source,
		ColumnMap             $columnMap,
		ImportOptions         $options,
		ImportReport          $report
	): bool
	{
		$unknown = [];
		$known   = 0;

		foreach ($source->getRawHeaders() as $header)
		{
			$header = trim((string) $header);
			if ($header === '')
			{
				continue;
			}

			if ($columnMap->resolve($header) === null)
			{
				$unknown[] = $header;
			}
			else
			{
				$known++;
			}
		}

		$total = $known + count($unknown);

		// Nothing to compare against — the source has no usable header row.
		// Let downstream code decide (it will most likely return an empty report).
		if ($total === 0)
		{
			return true;
		}

		// Always surface the unknown headers in the report — useful both for
		// the soft-warning path (continue) and the fatal paths (rejected file),
		// so the frontend can list which columns were not recognized.
		if ($unknown !== [])
		{
			$report->setUnknownHeaders($unknown);
		}

		// All headers unrecognized: the file does not belong to this entity at all.
		if ($known === 0)
		{
			$report->addGlobalError(Text::sprintf(
				'COM_EMUNDUS_IMPORT_NO_MATCHED_HEADERS',
				count($unknown),
				implode(', ', $unknown)
			));

			return false;
		}

		if ($unknown !== [])
		{
			$ratio = count($unknown) / $total;

			if ($ratio >= $options->maxUnknownHeaderRatio)
			{
				$report->addGlobalError(Text::sprintf(
					'COM_EMUNDUS_IMPORT_TOO_MANY_UNKNOWN_HEADERS',
					count($unknown),
					$total,
					implode(', ', $unknown)
				));

				return false;
			}
		}

		return true;
	}

	/**
	 * Runs the generic TypeValidator on every declared canonical field.
	 *
	 * @param array<string, mixed> $row
	 *
	 * @return string[] aggregated errors across all fields
	 */
	private function validateTypes(array $row, ColumnMap $columnMap): array
	{
		$errors = [];

		foreach ($columnMap->canonicalFields() as $canonical)
		{
			$descriptor = $columnMap->getDescriptor($canonical);
			if ($descriptor === null)
			{
				continue;
			}

			$fieldErrors = $this->typeValidator->validate($row[$canonical] ?? null, $descriptor);
			if (!empty($fieldErrors))
			{
				array_push($errors, ...$fieldErrors);
			}
		}

		return $errors;
	}

	/**
	 * Returns true when the row was created (or dry-run rolled back successfully),
	 * false when persistence threw and was rolled back.
	 *
	 * @param array<string, mixed> $row
	 */
	private function persistRow(
		EntityImporterInterface $importer,
		array                   $row,
		ImportContext           $context,
		ImportOptions           $options,
		ImportReport            $report
	): bool
	{
		$transactionStarted = false;

		try
		{
			$this->db->transactionStart();
			$transactionStarted = true;

			$importer->persist($row, $context);

			if ($options->dryRun)
			{
				$this->db->transactionRollback();
			}
			else
			{
				$this->db->transactionCommit();
			}

			$report->add($context, RowStatusEnum::CREATED);

			return true;
		}
		catch (\Throwable $e)
		{
			if ($transactionStarted)
			{
				try
				{
					$this->db->transactionRollback();
				}
				catch (\Throwable $rollbackError)
				{
					Log::add(
						sprintf('Import rollback failed: %s', $rollbackError->getMessage()),
						Log::ERROR,
						'com_emundus.import'
					);
				}
			}

			$report->add($context, RowStatusEnum::FAILED, [$e->getMessage()]);
			$this->logFailure($context, $e);

			return false;
		}
	}

	/**
	 * Same lifecycle as persistRow(), but routes to the importer's update()
	 * method (UpdatableEntityImporter) and reports the row as UPDATED. The
	 * caller has already verified the importer is updatable.
	 *
	 * @param array<string, mixed> $row
	 */
	private function updateRow(
		EntityImporterInterface $importer,
		array                   $row,
		ImportContext           $context,
		ImportOptions           $options,
		ImportReport            $report
	): bool
	{
		assert($importer instanceof UpdatableEntityImporter);

		$transactionStarted = false;

		try
		{
			$this->db->transactionStart();
			$transactionStarted = true;

			$importer->update($row, $context);

			if ($options->dryRun)
			{
				$this->db->transactionRollback();
			}
			else
			{
				$this->db->transactionCommit();
			}

			$report->add($context, RowStatusEnum::UPDATED);

			return true;
		}
		catch (\Throwable $e)
		{
			if ($transactionStarted)
			{
				try
				{
					$this->db->transactionRollback();
				}
				catch (\Throwable $rollbackError)
				{
					Log::add(
						sprintf('Import rollback failed: %s', $rollbackError->getMessage()),
						Log::ERROR,
						'com_emundus.import'
					);
				}
			}

			$report->add($context, RowStatusEnum::FAILED, [$e->getMessage()]);
			$this->logFailure($context, $e);

			return false;
		}
	}

	private function logFailure(ImportContext $context, \Throwable $e): void
	{
		Log::add(
			sprintf(
				'Import error in "%s" at row %d: %s',
				$context->sourceName,
				$context->rowNumber,
				$e->getMessage()
			),
			Log::WARNING,
			'com_emundus.import'
		);
	}
}
