<?php
/**
 * @package     Tchooz\Enums\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Enums\Import;

use Joomla\CMS\Language\Text;

/**
 * Data type expected for a canonical import field.
 *
 * Lives at the documentation level (consumed by the frontend describe()
 * payload). The pipeline itself does NOT enforce these types yet — each
 * EntityImporter's validate() keeps full control over runtime checks.
 *
 * Conventions for the optional `format` hint on AliasColumnMapBuilder:
 *   STRING   → free text, or a referential format string (e.g. "iso-3166-1-alpha-2",
 *              "iso-4217", "E.164"). Frontend maps these to localized examples.
 *   DATE     → human-readable date pattern (e.g. "YYYY-MM-DD", "DD/MM/YYYY").
 *   INTEGER  → no format (digits only).
 *   NUMBER   → no format (decimal point convention is locale-driven).
 *   BOOLEAN  → no format (the canonical truthy/falsy set is implementation-defined).
 *   EMAIL    → no format (always RFC 5322).
 *   URL      → no format (always absolute URI).
 *   ENUM     → `values` carries the allowed set; format is unused.
 */
enum FieldTypeEnum: string
{
	case STRING  = 'string';
	case INTEGER = 'integer';
	case NUMBER  = 'number';
	case BOOLEAN = 'boolean';
	case DATE    = 'date';
	case EMAIL   = 'email';
	case URL     = 'url';
	case ENUM    = 'enum';

	/**
	 * Localized, human-readable label. Used in the XLSX model documentation
	 * sheet and exposed alongside the raw value in describe() payloads so the
	 * frontend can display a friendly type name without maintaining its own
	 * translation table.
	 */
	public function getLabel(): string
	{
		return match ($this)
		{
			self::STRING  => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_STRING'),
			self::INTEGER => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_INTEGER'),
			self::NUMBER  => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_NUMBER'),
			self::BOOLEAN => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_BOOLEAN'),
			self::DATE    => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_DATE'),
			self::EMAIL   => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_EMAIL'),
			self::URL     => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_URL'),
			self::ENUM    => Text::_('COM_EMUNDUS_IMPORT_FIELD_TYPE_ENUM'),
		};
	}
}
