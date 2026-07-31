<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Services\Import\Mapping\ColumnMap;

/**
 * Contract implemented once per entity type that can be imported.
 *
 * The pipeline calls these methods in this order for each non-empty row:
 *   1. validate()  — returns business errors (empty array if valid)
 *   2. exists()    — true to skip the row as an existing duplicate
 *   3. persist()   — creates and flushes the entity (and dependencies)
 *
 * persist() runs inside a transaction opened by the pipeline. Any throwable
 * triggers a rollback and the row is reported as failed. Do not catch and
 * swallow errors inside persist().
 */
interface EntityImporterInterface
{
	/**
	 * Builds an instance with sensible default dependencies.
	 *
	 * Used by EntityImporterRegistry::registerAll() to auto-discover importers
	 * dropped in Services/Import/Entity/ without forcing the controller to
	 * know each importer's constructor. Implementations should wire their own
	 * default repositories here; callers needing custom dependencies keep
	 * using the regular constructor.
	 */
	public static function create(): self;

	/**
	 * Logical entity type, used in reports and registries (e.g. "contact").
	 */
	public function getType(): string;

	/**
	 * Mapping between raw file headers and canonical field names.
	 */
	public function getColumnMap(): ColumnMap;

	/**
	 * Business validation beyond required fields.
	 *
	 * @param   array<string, mixed>  $row  Canonicalized row.
	 *
	 * @return string[]  Empty array means the row is valid.
	 */
	public function validate(array $row, ImportContext $context): array;

	/**
	 * True when an equivalent record already exists in the database.
	 *
	 * @param   array<string, mixed>  $row  Canonicalized row.
	 */
	public function exists(array $row, ImportContext $context): bool;

	/**
	 * Persists the entity and all its dependencies.
	 *
	 * Must throw on any failure — do not log-and-continue. The pipeline
	 * wraps this call in a transaction and rolls back on exception.
	 *
	 * @param   array<string, mixed>  $row  Canonicalized row.
	 */
	public function persist(array $row, ImportContext $context): void;

	/**
	 * @return array<ImportConflictModeEnum> Supported conflict resolution modes for this importer.
	 */
	public function getSupportedModes(): array;
}
