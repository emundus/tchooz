<?php
/**
 * @package     Tchooz\Services\Export\OptionsSchema
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\OptionsSchema;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Export\ExportTabEnum;

/**
 * Declarative schema of the runtime "Options" toggles shown to the user just
 * before running an export. One concrete schema per export format. The schema
 * is the single source of truth for: the form rendered on the front, the
 * defaults applied when the user does not touch a field, and the cast rules
 * applied to the raw POST payload.
 *
 * Format-agnostic fields (the `language` chooser is the canonical one) live in
 * this base class so every concrete schema inherits them. Children override
 * {@see getFormatFields()} / {@see getFormatDefaults()} to declare what is
 * specific to their format and call back to {@see getFields()} / {@see getDefaults()}.
 */
abstract class AbstractOptionsSchema
{
	public const LANGUAGE = 'language';

	/**
	 * Format-specific fields. Override per concrete schema; the base
	 * {@see getFields()} prepends the format-agnostic entries.
	 *
	 * @return  array<Field>
	 */
	abstract protected function getFormatFields(): array;

	/**
	 * Format-specific defaults. Override per concrete schema; the base
	 * {@see getDefaults()} merges format-agnostic defaults in.
	 *
	 * @return  array<string, mixed>
	 */
	abstract protected function getFormatDefaults(): array;

	/**
	 * Field entities (with FieldGroup) used to build the front parameter form.
	 *
	 * @return  array<Field>
	 */
	public function getFields(): array
	{
		return array_merge($this->getFormatFields(), $this->getCommonFields());
	}

	/**
	 * Order between format vs common defaults is irrelevant (associative keys),
	 * but we keep the same convention as getFields() for consistency.
	 *
	 * @return  array<string, mixed>
	 */
	public function getDefaults(): array
	{
		return array_merge($this->getFormatDefaults(), $this->getCommonDefaults());
	}

	/**
	 * @return  array<Field>
	 */
	protected function getCommonFields(): array
	{
		return [
			new ChoiceField(
				name: self::LANGUAGE,
				label: 'COM_EMUNDUS_EXPORT_LANGUAGE_LABEL',
				choices: $this->buildLanguageChoices(),
				required: false,
				multiple: false,
				group: ExportTabEnum::OPTIONS->toFieldGroup(),
				addSelectOption: false,
			),
		];
	}

	/**
	 * Default value for each field, keyed by field name.
	 *
	 * @return  array<string, mixed>
	 */
	protected function getCommonDefaults(): array
	{
		return [
			self::LANGUAGE => Factory::getApplication()->getLanguage()->getTag(),
		];
	}

	/**
	 * @return  array<ChoiceFieldValue>
	 */
	private function buildLanguageChoices(): array
	{
		$languages = LanguageHelper::getLanguages();
		$choices   = [];

		if (count($languages) > 1)
		{
			$choices[] = new ChoiceFieldValue(null, Text::_('PLEASE_SELECT'));
		}

		foreach ($languages as $language)
		{
			$choices[] = new ChoiceFieldValue($language->lang_code, $language->title_native);
		}

		return $choices;
	}

	/**
	 * Schema as serializable arrays for the JSON response. Each entry merges
	 * the field's own schema with its default so the front can preselect the
	 * toggle without an extra round trip.
	 *
	 * @return  array<int, array<string, mixed>>
	 */
	public function toSchema(): array
	{
		$defaults = $this->getDefaults();

		return array_map(
			fn(Field $field) => array_merge(
				$field->toSchema(),
				['default' => $defaults[$field->getName()] ?? null]
			),
			$this->getFields()
		);
	}

	/**
	 * Filter a raw input (POST/JSON) down to the schema's known keys and cast
	 * each value through the matching field type. Unknown keys are dropped,
	 * missing keys fall back to their default.
	 *
	 * @param   array<string, mixed>  $raw
	 *
	 * @return  array<string, mixed>
	 */
	public function cast(array $raw): array
	{
		$defaults = $this->getDefaults();
		$out      = [];

		foreach ($this->getFields() as $field)
		{
			$name = $field->getName();

			if (array_key_exists($name, $raw))
			{
				$out[$name] = $this->castValue($field, $raw[$name]);
			}
			else
			{
				$out[$name] = $defaults[$name] ?? null;
			}
		}

		return $out;
	}

	protected function castValue(Field $field, mixed $value): mixed
	{
		return match ($field::getType())
		{
			'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
			'numeric' => is_numeric($value) ? (int) $value : null,
			'string'  => is_scalar($value) ? (string) $value : '',
			'choice'  => $this->castChoice($value),
			default   => $value,
		};
	}

	private function castChoice(mixed $value): int|string|null
	{
		if (!is_scalar($value) || $value === '') {
			return null;
		}

		return is_numeric($value) ? (int) $value : (string) $value;
	}
}
