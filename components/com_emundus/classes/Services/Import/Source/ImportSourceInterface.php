<?php
/**
 * @package     Tchooz\Services\Import\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Source;

/**
 * Iterates rows of any tabular source (xlsx, csv, json, array, ...) as
 * associative arrays keyed by the raw header strings as they appear in the
 * source. The pipeline applies the ColumnMap on top to translate keys to
 * canonical names — sources stay dumb.
 *
 * Yields keys are the row numbers as they appear in the source (1-based,
 * with the header on row 1, so data starts at row 2). Sources without a
 * notion of physical line (json/array) yield sequential numbers starting
 * at 2 to keep behaviour uniform.
 */
interface ImportSourceInterface extends \IteratorAggregate
{
	/**
	 * Human-readable identifier of the source, used in reports.
	 * Examples: a sheet name, a file basename.
	 */
	public function getName(): string;

	/**
	 * Raw headers exactly as found in the source.
	 *
	 * @return string[]
	 */
	public function getRawHeaders(): array;

	/**
	 * @return \Iterator<int, array<string, mixed>>  rowNumber => raw row keyed by raw headers
	 */
	public function getIterator(): \Iterator;
}
