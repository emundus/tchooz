<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums;

enum JoinTypeEnum: string
{
	case INNER = 'INNER';
	case LEFT = 'LEFT';
	case RIGHT = 'RIGHT';

	public function getMethod(): string
	{
		return match($this) {
			self::INNER => 'innerJoin',
			self::LEFT => 'leftJoin',
			self::RIGHT => 'rightJoin',
		};
	}
}
