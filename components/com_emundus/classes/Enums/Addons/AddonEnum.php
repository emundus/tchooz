<?php
/**
 * @package     Tchooz\Enums\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Addons;

use Joomla\CMS\Language\Text;

enum AddonEnum: string
{
	case ANONYMOUS = 'anonymous';
	case AUTOMATION = 'automation';
	case CHOICES = 'choices';
	case CRC = 'crc';
	case CUSTOM_REFERENCE_FORMAT = 'custom_reference_format';
	case IMPORT = 'import';
	case MESSENGER = 'messenger';
	case NUMERIC_SIGN = 'numeric_sign';
	case PAYMENT = 'payment';
	case SMS = 'sms';
	case BOOKING = 'booking';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::ANONYMOUS => Text::_('COM_EMUNDUS_ADDON_ANONYMOUS'),
			self::AUTOMATION => Text::_('COM_EMUNDUS_ADDON_AUTOMATION'),
			self::CHOICES => Text::_('COM_EMUNDUS_ADDON_CHOICES'),
			self::CRC => Text::_('COM_EMUNDUS_ADDON_CRC'),
			self::CUSTOM_REFERENCE_FORMAT => Text::_('COM_EMUNDUS_ADDON_CUSTOM_REFERENCE_FORMAT'),
			self::IMPORT => Text::_('COM_EMUNDUS_ADDON_IMPORT'),
			self::MESSENGER => Text::_('COM_EMUNDUS_ADDON_MESSENGER'),
			self::NUMERIC_SIGN => Text::_('COM_EMUNDUS_ADDON_NUMERIC_SIGN'),
			self::PAYMENT => Text::_('COM_EMUNDUS_ADDON_PAYMENT'),
			self::SMS => Text::_('COM_EMUNDUS_ADDON_SMS'),
			self::BOOKING => Text::_('COM_EMUNDUS_ADDON_BOOKING'),
		};
	}

	public function getDescription(): string
	{
		return match ($this)
		{
			self::ANONYMOUS => Text::_('COM_EMUNDUS_ADDON_ANONYMOUS_DESC'),
			self::AUTOMATION => Text::_('COM_EMUNDUS_ADDON_AUTOMATION_DESC'),
			self::CHOICES => Text::_('COM_EMUNDUS_ADDON_CHOICES_DESC'),
			self::CRC => Text::_('COM_EMUNDUS_ADDON_CRC_DESC'),
			self::CUSTOM_REFERENCE_FORMAT => Text::_('COM_EMUNDUS_ADDON_CUSTOM_REFERENCE_FORMAT_DESC'),
			self::IMPORT => Text::_('COM_EMUNDUS_ADDON_IMPORT_DESC'),
			self::MESSENGER => Text::_('COM_EMUNDUS_ADDON_MESSENGER_DESC'),
			self::NUMERIC_SIGN => Text::_('COM_EMUNDUS_ADDON_NUMERIC_SIGN_DESC'),
			self::PAYMENT => Text::_('COM_EMUNDUS_ADDON_PAYMENT_DESC'),
			self::SMS => Text::_('COM_EMUNDUS_ADDON_SMS_DESC'),
			self::BOOKING => Text::_('COM_EMUNDUS_ADDON_BOOKING_DESC'),
		};
	}

	// Material symbols icons: https://fonts.google.com/icons
	public function getIcon(): string
	{
		return match ($this)
		{
			self::ANONYMOUS => 'visibility_off',
			self::AUTOMATION => 'automation',
			self::CHOICES => 'checklist',
			self::CRC => 'contacts',
			self::CUSTOM_REFERENCE_FORMAT => 'format_quote',
			self::IMPORT => 'file_upload',
			self::MESSENGER => 'message',
			self::NUMERIC_SIGN => 'signature',
			self::PAYMENT => 'payment',
			self::SMS => 'sms',
			self::BOOKING => 'event',
		};
	}
}
