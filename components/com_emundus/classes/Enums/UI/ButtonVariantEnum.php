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

enum ButtonVariantEnum: string
{
	use CoercibleEnumTrait;

	case PRIMARY = 'primary';
	case SECONDARY = 'secondary';
	case LINK = 'link';
	case CANCEL = 'cancel';
	case DISABLED = 'disabled';
	case DASHED = 'dashed';
	case RED = 'red';

	public function cssClass(): string
	{
		return match ($this)
		{
			self::PRIMARY => 'tw-btn-primary',
			self::SECONDARY => 'tw-btn-secondary',
			self::DASHED => 'tw-btn-dashed',
			self::LINK => 'tw-underline tw-border-0',
			self::CANCEL => 'tw-btn-cancel',
			self::RED => 'tw-btn-red',
			self::DISABLED => 'em-disabled-button',
		};
	}
}
