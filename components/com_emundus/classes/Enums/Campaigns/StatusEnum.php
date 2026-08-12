<?php
/**
 * @package     Tchooz\Enums\Campaigns
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Campaigns;

use Joomla\CMS\Language\Text;

enum StatusEnum: string
{
	case UPCCOMING = 'upcoming';
	case OPEN = 'open';
	case CLOSED = 'closed';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::UPCCOMING => Text::_('COM_EMUNDUS_CAMPAIGN_STATUS_UPCCOMING'),
			self::OPEN => Text::_('COM_EMUNDUS_CAMPAIGN_STATUS_OPEN'),
			self::CLOSED => Text::_('COM_EMUNDUS_CAMPAIGN_STATUS_CLOSED'),
		};
	}

	public function getClass(): string
	{
		$class = 'tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm';

		return match ($this)
		{
			self::OPEN => $class.' tw-bg-green-500 tw-text-white',
			self::UPCCOMING => $class.' tw-bg-yellow-100 tw-text-yellow-800',
			self::CLOSED => $class.' tw-bg-red-100 tw-text-red-800',
		};
	}
}