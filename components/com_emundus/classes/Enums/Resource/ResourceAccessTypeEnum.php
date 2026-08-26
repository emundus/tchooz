<?php
/**
 * @package     Tchooz\Enums\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Resource;

enum ResourceAccessTypeEnum: string
{
	case ROLE  = 'role';
	case GROUP = 'group';
	case USER  = 'user';
}
