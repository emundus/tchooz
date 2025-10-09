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

enum ElementPlugin: string
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
