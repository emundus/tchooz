<?php
/**
 * @package     Tchooz\Services\Import\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Source;

/**
 * In-memory source, primarily for tests and programmatic invocations.
 *
 * Accepts either:
 *   - rows already keyed by header (no $headers argument needed)
 *   - a list of headers + a list of indexed rows (CSV-like layout)
 */
final class ArraySource implements ImportSourceInterface
{
	/** @var string[] */
	private array $headers;

	/** @var array<int, array<string, mixed>> */
	private array $rows;

	private string $name;

	/**
	 * @param array<int, array<string, mixed>>|array<int, array<int, mixed>>  $rows
	 * @param string[]|null                                                   $headers  Required when $rows is indexed.
	 */
	public function __construct(array $rows, ?array $headers = null, string $name = 'array')
	{
		$this->name = $name;

		if ($rows === [])
		{
			$this->headers = $headers ?? [];
			$this->rows    = [];
			return;
		}

		$first = reset($rows);

		// Associative rows are kept as-is. $headers, when provided, only controls
		// the order returned by getRawHeaders(); it does not re-shape the rows.
		if (is_array($first) && self::isAssoc($first))
		{
			$this->headers = $headers ?? array_keys($first);
			$this->rows    = array_values($rows);
			return;
		}

		// Indexed rows (CSV-like): headers are mandatory to combine values into
		// an associative shape downstream consumers can read.
		if ($headers === null)
		{
			throw new \InvalidArgumentException('ArraySource: $headers is required when rows are indexed arrays.');
		}

		$this->headers = $headers;
		$this->rows    = array_map(
			fn (array $row) => self::combine($headers, $row),
			array_values($rows)
		);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getRawHeaders(): array
	{
		return $this->headers;
	}

	public function getIterator(): \Iterator
	{
		foreach ($this->rows as $index => $row)
		{
			// Row 1 holds the headers in tabular sources, so data starts at 2.
			yield ($index + 2) => $row;
		}
	}

	/**
	 * @param array<int|string, mixed> $row
	 */
	private static function isAssoc(array $row): bool
	{
		if ($row === [])
		{
			return false;
		}

		foreach (array_keys($row) as $key)
		{
			if (!is_string($key))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string[]               $headers
	 * @param array<int, mixed>      $row
	 *
	 * @return array<string, mixed>
	 */
	private static function combine(array $headers, array $row): array
	{
		$assoc = [];

		foreach ($headers as $index => $header)
		{
			if (!is_string($header) || $header === '')
			{
				continue;
			}

			$assoc[$header] = $row[$index] ?? null;
		}

		return $assoc;
	}
}
