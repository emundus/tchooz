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
	/** @var RowResult[] */
	private array $rows = [];

	/** @var array<string, int>  status->value => count */
	private array $counts = [];

	/** @var string[]  raw headers found in the source that did not match any canonical field */
	private array $unknownHeaders = [];

	/** @var string[]  pipeline-level errors not tied to a specific row (wrong file, etc.) */
	private array $globalErrors = [];

	/**
	 * @param string[] $reasons
	 */
	public function add(ImportContext $context, RowStatusEnum $status, array $reasons = []): void
	{
		$this->rows[] = new RowResult($context->sourceName, $context->rowNumber, $status, $reasons);
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

		return [
			'summary' => $summary,
			'rows'    => array_map(static fn (RowResult $r) => $r->toArray(), $this->rows),
		];
	}
}
