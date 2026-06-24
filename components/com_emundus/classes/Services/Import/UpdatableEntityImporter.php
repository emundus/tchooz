<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

/**
 * Importer capable of overwriting an existing record with the incoming row.
 *
 * Pipeline routes to update() instead of persist() when the conflict mode is
 * UPDATE and the importer implements this marker interface. SET semantics
 * apply: every declared scalar field is overwritten on the existing record.
 * Decisions about related entities (addresses, organizations, ...) are left
 * to the implementation.
 */
interface UpdatableEntityImporter extends EntityImporterInterface
{
	/**
	 * Merges the incoming row onto the existing record matched by the same
	 * criteria as exists(). MUST throw on failure — the pipeline wraps this
	 * call in a transaction and rolls back on exception.
	 *
	 * @param array<string, mixed> $row Canonicalized row.
	 */
	public function update(array $row, ImportContext $context): void;
}
