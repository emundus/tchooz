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

enum ButtonWidthEnum: string
{
	use CoercibleEnumTrait;

	case FIT  = 'fit';
	case FULL = 'full';

	public function cssClass(): string
	{
		return match ($this)
		{
			self::FIT  => 'tw-w-fit',
			self::FULL => 'tw-w-full',
		};
	}
}
