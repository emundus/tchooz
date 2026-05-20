<?php
/**
 * @package     Tchooz\Enums\UI
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\UI;

use Tchooz\Enums\Traits\CoercibleEnumTrait;

enum ButtonIconPositionEnum: string
{
	use CoercibleEnumTrait;

	case LEFT  = 'left';
	case RIGHT = 'right';
}
