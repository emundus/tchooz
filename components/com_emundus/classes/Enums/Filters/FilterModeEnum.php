<?php
/**
 * @package     Tchooz\Enums\Filters
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Filters;

enum FilterModeEnum: string
{
	case SEARCH = 'search';

	case EXPORT = 'export';

	case LIST = 'list';
}
