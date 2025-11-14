<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums;

enum ValueFormatEnum: string
{
	case RAW = 'raw';
	case FORMATTED = 'formatted';
	case BOTH = 'both';
}
