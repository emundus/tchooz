<?php
/**
 * @package     Tchooz\Services\Export\OptionsSchema
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export\OptionsSchema;

use Tchooz\Entities\Fields\Field;

/**
 * Declarative schema of the runtime "Options" toggles shown to the user just
 * before running an export. One concrete schema per export format. The schema
 * is the single source of truth for: the form rendered on the front, the
 * defaults applied when the user does not touch a field, and the cast rules
 * applied to the raw POST payload.
 */
abstract class AbstractOptionsSchema
{
	/**
	 * Field entities (with FieldGroup) used to build the front parameter form.
	 *
	 * @return  array<Field>
	 */
	abstract public function getFields(): array;

	/**
	 * Default value for each field, keyed by field name.
	 *
	 * @return  array<string, mixed>
	 */
	abstract public function getDefaults(): array;

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
			default   => $value,
		};
	}
}
