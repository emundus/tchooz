<?php
/**
 * @package     Tchooz\Enums\List
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\List;

enum ListDisplayEnum: string
{
	case TABLE = 'table';
	case CARDS = 'blocs';

	case ALL = 'all';
}
