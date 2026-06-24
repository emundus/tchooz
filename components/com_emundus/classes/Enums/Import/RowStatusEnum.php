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
}
