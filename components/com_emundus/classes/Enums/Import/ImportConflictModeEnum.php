<?php
/**
 * @package     Tchooz\Enums\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Enums\Import;

/**
 * Policy applied by the pipeline when a row's lookup hits an existing record.
 *
 *   - SKIP       : ignore the incoming row, report it as SKIPPED. Safe default.
 *   - UPDATE     : merge the incoming row onto the existing record (SET semantics:
 *                  every declared scalar field is overwritten; relations are left
 *                  untouched unless the importer chooses otherwise).
 *   - CREATE_NEW : bypass the existence check and create a duplicate. Useful for
 *                  append-only/event-sourced imports (transactions, logs, etc.).
 */
enum ImportConflictModeEnum: string
{
	case SKIP       = 'skip';
	case UPDATE     = 'update';
	case CREATE_NEW = 'create_new';
}
