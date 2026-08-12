<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

use Tchooz\Enums\Import\ImportConflictModeEnum;

/**
 * Default base class for entity importers.
 *
 * Provides a sensible getSupportedModes() implementation derived from the
 * marker interfaces the importer carries, so the support list never drifts
 * away from what the importer can actually do:
 *
 *   - SKIP       : always supported (no-op).
 *   - UPDATE     : added automatically when the importer implements
 *                  UpdatableEntityImporter — keeping the type guarantee
 *                  on update() while removing the duplicate declaration.
 *   - CREATE_NEW : NOT added by default. Override getSupportedModes() to
 *                  opt-in for entities where intentional duplicates are
 *                  acceptable (append-only/event-sourced data).
 *
 * Importers with a different policy (e.g. SKIP only because there is no
 * way to identify duplicates) can override the method.
 */
abstract class AbstractEntityImporter implements EntityImporterInterface
{
	/**
	 * @return array<ImportConflictModeEnum>
	 */
	public function getSupportedModes(): array
	{
		$modes = [ImportConflictModeEnum::SKIP];

		if ($this instanceof UpdatableEntityImporter)
		{
			$modes[] = ImportConflictModeEnum::UPDATE;
		}

		return $modes;
	}
}
