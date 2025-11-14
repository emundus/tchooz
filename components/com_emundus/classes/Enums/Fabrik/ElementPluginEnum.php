<?php
declare(strict_types=1);

/**
 * @package     Tchooz\Enums\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Fabrik;

enum ElementPluginEnum: string
{
	// Standard fields
	case ID = 'internalid';
	case FIELD = 'field';
	case TEXTAREA = 'textarea';
	case PASSWORD = 'password';

	// Date fields
	case DATE = 'date';
	case JDATE = 'jdate';
	case YEARS = 'years';
	case BIRTHDAY = 'birthday';

	// Choices fields
	case CHECKBOX = 'checkbox';
	case DROPDOWN = 'dropdown';
	case RADIO = 'radiobutton';
	case DATABASEJOIN = 'databasejoin';
	case RATING = 'rating';
	case YESNO = 'yesno';

	// File fields
	case FILEUPLOAD = 'fileupload';
	case EMUNDUS_FILEUPLOAD = 'emundus_fileupload';

	// Other fields
	case BOOKING = 'booking';
	case CALC = 'calc';
	case CURRENCY = 'currency';
	case PHONENUMBER = 'emundus_phonenumber';
	case IBAN = 'iban';
	case PANEL = 'panel';
	case DISPLAY = 'display';

	public static function tryFromString(?string $plugin): ?self
	{
		if ($plugin === null || $plugin === '')
		{
			return null;
		}

		// normalize (lowercase, trim)
		$normalized = strtolower(trim($plugin));

		return self::tryFrom($normalized);
	}

	public function getLabel(): string
	{
		return match ($this)
		{
			self::ID => 'COM_EMUNDUS_USERNAME',
			self::FIELD => 'COM_EMUNDUS_ONBOARD_TYPE_FIELD',
			self::TEXTAREA => 'COM_EMUNDUS_ONBOARD_TYPE_TEXTAREA',
			self::PASSWORD => 'COM_EMUNDUS_REGISTER_PASSWORD1_LABEL',
			self::DATE, self::JDATE, self::BIRTHDAY => 'COM_EMUNDUS_ONBOARD_TYPE_BIRTHDAY',
			self::YEARS => 'MOD_EMUNDUS_FILTERS_YEARS',
			self::CHECKBOX => 'COM_EMUNDUS_ONBOARD_TYPE_CHECKBOX',
			self::DROPDOWN => 'COM_EMUNDUS_ONBOARD_TYPE_DROPDOWN',
			self::RADIO => 'COM_EMUNDUS_ONBOARD_TYPE_RADIOBUTTON',
			self::DATABASEJOIN => 'COM_EMUNDUS_ONBOARD_TYPE_DATABASEJOIN',
			self::RATING => 'COM_EMUNDUS_FABRIK_ELEMENT_RATING',
			self::YESNO => 'COM_EMUNDUS_ONBOARD_TYPE_YESNO',
			self::FILEUPLOAD, self::EMUNDUS_FILEUPLOAD => 'COM_EMUNDUS_ONBOARD_TYPE_FILE',
			self::BOOKING => 'COM_EMUNDUS_ONBOARD_TYPE_BOOKING',
			self::CALC => 'COM_EMUNDUS_ONBOARD_BUILDER_CALC_VALUE',
			self::CURRENCY => 'COM_EMUNDUS_ONBOARD_TYPE_CURRENCY',
			self::PHONENUMBER => 'COM_EMUNDUS_ONBOARD_TYPE_PHONE_NUMBER',
			self::IBAN => 'COM_EMUNDUS_ONBOARD_TYPE_IBAN',
			self::PANEL => 'COM_EMUNDUS_ONBOARD_TYPE_PANEL',
		};
	}

	public function getDateFormatParameter(): string
	{
		return match ($this)
		{
			self::DATE => 'date_form_format',
			self::JDATE => 'jdate_form_format',
			self::BIRTHDAY => 'list_date_format',
			default => '',
		};
	}

	public function isDateField(): bool
	{
		return match ($this)
		{
			self::DATE,
			self::JDATE,
			self::BIRTHDAY => true,
			default => false,
		};
	}

	public function isChoicesField(): bool
	{
		return match ($this)
		{
			self::CHECKBOX,
			self::DROPDOWN,
			self::RADIO => true,
			default => false,
		};
	}
}
