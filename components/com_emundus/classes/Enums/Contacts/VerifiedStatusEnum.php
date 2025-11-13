<?php
/**
 * @package     Tchooz\Enums\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Contacts;

use Joomla\CMS\Language\Text;

enum VerifiedStatusEnum: string
{
	case VERIFIED = 'verified';
	case TO_BE_VERIFIED = 'to_be_verified';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::VERIFIED => Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_STATUS_VERIFIED'),
			self::TO_BE_VERIFIED => Text::_('COM_EMUNDUS_ONBOARD_ADD_CONTACT_STATUS_TO_BE_VERIFIED'),
		};
	}

	public function getColorClass(): string
	{
		return match ($this)
		{
			self::VERIFIED => 'em-bg-main-500',
			self::TO_BE_VERIFIED => 'tw-bg-orange-500',
		};
	}
}
