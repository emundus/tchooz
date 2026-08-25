<?php
/**
 * @package     Tchooz\Services\Import\Report
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Enums\Import;

enum RowStatusEnum: string
{
	case CREATED = 'created';
	case UPDATED = 'updated';
	case SKIPPED = 'skipped';
	case FAILED  = 'failed';

	/**
	 * Outcome of a row that passed validation in validate-only mode
	 * (dry-run preview): the row is well-formed but was neither persisted
	 * nor checked for conflicts. Always 0 on a real import run.
	 */
	case VALID = 'valid';

	/**
	 * Row that carried no value in any *recognized* column, so there was nothing
	 * to import. Covers a genuinely blank line as well as one whose only filled
	 * columns are absent from the ColumnMap. Counted rather than dropped, so a
	 * file that maps to nothing can say so instead of reporting an empty result.
	 */
	case IGNORED = 'ignored';
}
