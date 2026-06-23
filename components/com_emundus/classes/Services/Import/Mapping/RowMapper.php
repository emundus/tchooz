<?php
/**
 * @package     Tchooz\Services\Import\Mapping
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Mapping;

/**
 * Translates a raw row (keyed by raw headers as found in the source) into a
 * canonical row (keyed by canonical names declared in the ColumnMap).
 *
 * Headers unknown to the map are dropped silently. Missing canonical fields
 * are filled with null so downstream code can rely on `array_key_exists`.
 */
final class RowMapper
{
	public function __construct(private readonly ColumnMap $columnMap) {}

	/**
	 * @param array<string, mixed> $rawRow
	 *
	 * @return array<string, mixed>  Canonical row.
	 */
	public function map(array $rawRow): array
	{
		$canonical = array_fill_keys($this->columnMap->canonicalFields(), null);

		foreach ($rawRow as $rawHeader => $value)
		{
			if (!is_string($rawHeader) || $rawHeader === '')
			{
				continue;
			}

			$resolved = $this->columnMap->resolve($rawHeader);

			if ($resolved === null)
			{
				continue;
			}

			// First non-empty value wins, so duplicate headers in the source
			// don't silently overwrite an already-mapped value with null.
			if (self::isEmpty($canonical[$resolved] ?? null))
			{
				$canonical[$resolved] = $value;
			}
		}

		return $canonical;
	}

	/**
	 * True when every canonical value is empty — the row carries no data.
	 *
	 * @param array<string, mixed> $row
	 */
	public static function isRowEmpty(array $row): bool
	{
		foreach ($row as $value)
		{
			if (!self::isEmpty($value))
			{
				return false;
			}
		}

		return true;
	}

	private static function isEmpty(mixed $value): bool
	{
		if ($value === null)
		{
			return true;
		}

		if (is_string($value))
		{
			return trim($value) === '';
		}

		return false;
	}
}
