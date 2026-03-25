<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums;

use Joomla\CMS\Language\Text;

enum StatusEnum: int
{
	case PUBLISHED = 1;
	case UNPUBLISHED = 0;
	case ARCHIVED = 2;
	case TRASHED = -2;

	public function getLabel(): string
	{
		return match ($this)
		{
			self::PUBLISHED => Text::_('COM_EMUNDUS_STATUS_PUBLISHED'),
			self::UNPUBLISHED => Text::_('COM_EMUNDUS_STATUS_UNPUBLISHED'),
			self::ARCHIVED => Text::_('COM_EMUNDUS_STATUS_ARCHIVED'),
			self::TRASHED => Text::_('COM_EMUNDUS_STATUS_TRASHED'),
		};
	}

	public function getClass(): string
	{
		$class = 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm';

		return match ($this)
		{
			self::PUBLISHED => $class.' em-bg-main-500 tw-text-white',
			self::UNPUBLISHED => $class.' tw-bg-neutral-300 tw-text-neutral-700',
			self::ARCHIVED => $class.' tw-bg-yellow-100 tw-text-yellow-800',
			self::TRASHED => $class.' tw-bg-red-100 tw-text-red-800',
		};
	}
}
