<?php
/**
 * @package     Tchooz\Services\Import\Report
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Report;

use Tchooz\Enums\Import\RowStatusEnum;
use Tchooz\Services\Import\ImportContext;

/**
 * Aggregated outcome of an import run.
 *
 * Counters are indexed by RowStatusEnum::value, so adding a new status case
 * does not require any change here — the new status flows through add(),
 * count(), merge() and toArray() automatically.
 *
 * Mergeable so that a controller can run several pipelines (one per sheet,
 * one per entity type) and produce a single report.
 */
final class ImportReport
{
	/**
	 * Cap on individually-stored failed rows: keeps the *stored* report bounded
	 * regardless of the failure rate (a fully-failed 100k-row file must not blow
	 * up the JSON column). Beyond it, failed_truncated flags the cut.
	 */
	public const MAX_FAILED_ROWS = 5000;

	/** @var RowResult[] */
	private array $rows = [];

	/** @var array<string, int>  status->value => count */
	private array $counts = [];

	/** @var string[]  raw headers found in the source that did not match any canonical field */
	private array $unknownHeaders = [];

	/** @var string[]  pipeline-level errors not tied to a specific row (wrong file, etc.) */
	private array $globalErrors = [];

	/**
	 * @param RowError[]           $reasons
	 * @param array<string, mixed> $data     canonical row values, kept for FAILED rows so an export can list them
	 * @param string[]             $warnings non-blocking advisories
	 */
	public function add(ImportContext $context, RowStatusEnum $status, array $reasons = [], array $data = [], array $warnings = []): void
	{
		// The context collects warnings while the row is decoded and validated
		// (referential mismatch, unusable optional value...); they belong to the
		// row's outcome, so they are merged with any passed explicitly.
		$warnings = array_merge($context->getWarnings(), $warnings);

		$this->rows[] = new RowResult($context->sourceName, $context->rowNumber, $status, $reasons, $data, $warnings);
		$this->counts[$status->value] = ($this->counts[$status->value] ?? 0) + 1;
	}

	public function merge(self $other): void
	{
		foreach ($other->rows as $row)
		{
			$this->rows[] = $row;
		}

		foreach ($other->counts as $status => $count)
		{
			$this->counts[$status] = ($this->counts[$status] ?? 0) + $count;
		}

		foreach ($other->unknownHeaders as $header)
		{
			if (!in_array($header, $this->unknownHeaders, true))
			{
				$this->unknownHeaders[] = $header;
			}
		}

		foreach ($other->globalErrors as $error)
		{
			$this->globalErrors[] = $error;
		}
	}

	/**
	 * Records the raw source headers that didn't map to any canonical field.
	 * Surfaced as a warning so the user knows what was ignored without blocking
	 * the import (e.g. extra "Notes" column in an otherwise valid file).
	 *
	 * @param string[] $headers
	 */
	public function setUnknownHeaders(array $headers): void
	{
		$this->unknownHeaders = array_values(array_unique($headers));
	}

	/** @return string[] */
	public function getUnknownHeaders(): array
	{
		return $this->unknownHeaders;
	}

	/**
	 * Records a pipeline-level error that is not tied to a specific row, e.g.
	 * "the uploaded file does not match the selected entity".
	 */
	public function addGlobalError(string $message): void
	{
		$this->globalErrors[] = $message;
	}

	/** @return string[] */
	public function getGlobalErrors(): array
	{
		return $this->globalErrors;
	}

	public function hasGlobalErrors(): bool
	{
		return $this->globalErrors !== [];
	}

	public function count(RowStatusEnum $status): int
	{
		return $this->counts[$status->value] ?? 0;
	}

	/** @return RowResult[] */
	public function getRows(): array
	{
		return $this->rows;
	}

	/** @return RowResult[] */
	public function getRowsByStatus(RowStatusEnum $status): array
	{
		return array_values(array_filter(
			$this->rows,
			static fn (RowResult $r) => $r->status === $status
		));
	}

	public function toArray(): array
	{
		$summary = ['total' => count($this->rows)];
		foreach (RowStatusEnum::cases() as $case)
		{
			$summary[$case->value] = $this->counts[$case->value] ?? 0;
		}

		$summary['unknown_headers'] = $this->unknownHeaders;
		$summary['global_errors']   = $this->globalErrors;
		$summary['warnings']        = count(array_filter($this->rows, static fn (RowResult $r) => $r->warnings !== []));

		return [
			'summary' => $summary,
			'rows'    => array_map(static fn (RowResult $r) => $r->toArray(), $this->rows),
		];
	}

	/**
	 * Bounded variant of toArray() meant for persistence (the `report` column).
	 *
	 * Keeps the full summary counters and, for FAILED rows, a structured per-row
	 * entry ({row, errors}) so the report can be translated on read and exported
	 * line by line. The per-row list is hard-capped; truncation is flagged so the
	 * UI can say "+N more".
	 *
	 * CREATED / UPDATED / SKIPPED / VALID rows are represented by their counters
	 * only — never stored individually.
	 */
	public function toStorableArray(): array
	{
		$summary = ['total' => count($this->rows)];
		foreach (RowStatusEnum::cases() as $case)
		{
			$summary[$case->value] = $this->counts[$case->value] ?? 0;
		}
		$summary['unknown_headers'] = $this->unknownHeaders;
		$summary['global_errors']   = $this->globalErrors;

		$failedRows    = [];
		$rowsTruncated = false;

		foreach ($this->getRowsByStatus(RowStatusEnum::FAILED) as $row)
		{
			if (count($failedRows) < self::MAX_FAILED_ROWS)
			{
				$failedRows[] = [
					'row'    => $row->rowNumber,
					'errors' => array_map(static fn (RowError $error) => $error->toArray(), $row->reasons),
				];
			}
			else
			{
				$rowsTruncated = true;
			}
		}

		return [
			'summary'          => $summary,
			'failed_rows'      => $failedRows,
			'failed_truncated' => $rowsTruncated,
			'failed_total'     => $summary[RowStatusEnum::FAILED->value],
		];
	}

	/**
	 * Combines two storable reports (output of toStorableArray()) into one.
	 *
	 * Used by the async wrapper to accumulate per-slice reports onto the running
	 * cumulative: counters are summed, per-row failures are concatenated (re-capped),
	 * unknown headers are unioned and global errors concatenated.
	 *
	 * @param array $cumulative The report stored so far (empty array on first slice).
	 * @param array $slice      The storable report of the slice just processed.
	 */
	public static function mergeStorable(array $cumulative, array $slice): array
	{
		if (empty($cumulative))
		{
			return $slice;
		}
		if (empty($slice))
		{
			return $cumulative;
		}

		$summary = $cumulative['summary'] ?? [];
		foreach (($slice['summary'] ?? []) as $key => $value)
		{
			if ($key === 'unknown_headers')
			{
				$summary['unknown_headers'] = array_values(array_unique(
					array_merge($summary['unknown_headers'] ?? [], $value)
				));
			}
			elseif ($key === 'global_errors')
			{
				$summary['global_errors'] = array_merge($summary['global_errors'] ?? [], $value);
			}
			else
			{
				$summary[$key] = ($summary[$key] ?? 0) + $value;
			}
		}

		$failedRows    = $cumulative['failed_rows'] ?? [];
		$rowsTruncated = false;
		foreach (($slice['failed_rows'] ?? []) as $failedRow)
		{
			if (count($failedRows) < self::MAX_FAILED_ROWS)
			{
				$failedRows[] = $failedRow;
			}
			else
			{
				$rowsTruncated = true;
			}
		}

		$truncated = $rowsTruncated
			|| ($cumulative['failed_truncated'] ?? false)
			|| ($slice['failed_truncated'] ?? false);

		return [
			'summary'          => $summary,
			'failed_rows'      => $failedRows,
			'failed_truncated' => $truncated,
			'failed_total'     => ($cumulative['failed_total'] ?? 0) + ($slice['failed_total'] ?? 0),
		];
	}
}
