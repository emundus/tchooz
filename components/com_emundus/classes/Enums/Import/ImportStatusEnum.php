<?php
/**
 * @package     Tchooz\Enums\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Enums\Import;

use Tchooz\Entities\Import\ImportEntity;

/**
 * The state of an import job
 */
enum ImportStatusEnum: string
{
	case PROCESSING = 'processing';
	case COMPLETED  = 'completed';
	case FAILED     = 'failed';
	case CANCELLED  = 'cancelled';

	/**
	 * Derives the status of an import. Precedence (failed > completed >
	 * cancelled > processing): a failed import is FAILED even at 100%, and a
	 * cancelled-but-finished import counts as COMPLETED.
	 */
	public static function fromImport(ImportEntity $import): self
	{
		return match (true)
		{
			$import->isFailed()           => self::FAILED,
			$import->getProgress() >= 100 => self::COMPLETED,
			$import->isCancelled()        => self::CANCELLED,
			default                       => self::PROCESSING,
		};
	}
}
