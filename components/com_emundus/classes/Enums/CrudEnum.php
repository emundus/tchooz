<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums;

enum CrudEnum: string
{
	case CREATE = 'c';
	case READ   = 'r';
	case UPDATE = 'u';
	case DELETE = 'd';
}
