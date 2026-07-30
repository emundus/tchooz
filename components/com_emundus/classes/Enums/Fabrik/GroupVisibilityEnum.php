<?php
/**
 * @package     Tchooz\Enums\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Fabrik;

/**
 * Values of the fabrik group parameter repeat_group_show_first.
 */
enum GroupVisibilityEnum: string
{
	case VISIBLE = '1';
	case HIDDEN = '-1';
	case HIDDEN_IN_FORM = '-2';
	case HIDDEN_IN_DETAILS = '-3';
	case DETAILS_VIEW_ONLY = '2';
	case FORM_VIEW_ONLY = '3';
	case USABLE_ELEMENTS_ONLY = '4';
	case ALWAYS_READ_ONLY = '5';
	case LOADED_HIDDEN = '6';
	case NEVER = '0';

	/**
	 * Builds the visibility from a raw parameter value, which may be stored as a JSON string or a JSON integer,
	 * may carry surrounding whitespace, or may be missing.
	 *
	 * @param   int|string|null  $value
	 *
	 * @return GroupVisibilityEnum
	 */
	public static function fromParams(int|string|null $value): self
	{
		if ($value === null)
		{
			return self::VISIBLE;
		}

		return self::tryFrom(trim((string) $value)) ?? self::VISIBLE;
	}

	/**
	 * Builds the SQL condition matching the groups having this visibility, tolerating the parameter being stored
	 * as a JSON string or a JSON integer, carrying surrounding noise, or being missing.
	 *
	 * @param   string  $paramsColumn
	 *
	 * @return string
	 */
	public function toSqlCondition(string $paramsColumn): string
	{
		$extract   = 'JSON_EXTRACT(' . $paramsColumn . ', "$.repeat_group_show_first")';
		$condition = 'REGEXP_REPLACE(JSON_UNQUOTE(' . $extract . '), "[^0-9-]", "") = "' . $this->value . '"';

		if ($this === self::VISIBLE)
		{
			return '(' . $extract . ' IS NULL OR ' . $condition . ')';
		}

		return $condition;
	}

	/**
	 * Whether the group is shown in every view.
	 *
	 * @return bool
	 */
	public function isVisible(): bool
	{
		return $this === self::VISIBLE;
	}

	/**
	 * Whether the group is rendered in the form view.
	 *
	 * @return bool
	 */
	public function isVisibleInForm(): bool
	{
		return in_array($this, [self::VISIBLE, self::FORM_VIEW_ONLY], true);
	}

	/**
	 * Whether the group is withheld from the applicant.
	 *
	 * @return bool
	 */
	public function isHidden(): bool
	{
		return in_array($this, [self::NEVER, self::HIDDEN, self::HIDDEN_IN_FORM], true);
	}
}