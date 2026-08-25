<?php
/**
 * @package     Tchooz\Traits
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Traits;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Services\Field\FieldResearch;
use Tchooz\Services\Import\EntityImporterInterface;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Mapping\ColumnMap;
use Tchooz\Services\Import\UpdatableEntityImporter;
use Tchooz\Services\Import\Validation\TypeValidator;
use Tchooz\Transformers\PhoneNumberTransformer;

/**
 * Shared machinery for automation actions that create/update a CRC entity
 * (contact, organization, ...) from application-file form data.
 *
 * The importer's ColumnMap is the single source of truth: it drives which
 * fields the action exposes as parameters (label + required flag come from the
 * FieldDescriptor) AND how each mapped form value is type-validated before it
 * reaches the importer. Persistence and duplicate detection are delegated to
 * the importer's exists()/persist()/update() primitives.
 *
 * The host action must expose `getParameterValue()` (ActionEntity).
 */
trait GeneratesEntityFromImportTrait
{
	/**
	 * Builds one ChoiceField per canonical field of the column map, reading the
	 * label and required flag from the field's descriptor. The suffix keeps the
	 * parameter name stable (`<canonical>_field`) so a form element can be mapped
	 * to each importable field.
	 *
	 * @return ChoiceField[]
	 */
	protected function buildFieldParametersFromColumnMap(ColumnMap $columnMap): array
	{
		$research = new FieldResearch('form', 'getFabrikElementOptions');

		$parameters = [];
		foreach ($columnMap->canonicalFields() as $canonical)
		{
			$descriptor   = $columnMap->getDescriptor($canonical);
			if($descriptor->type === FieldTypeEnum::BOOLEAN) {
				$parameters[] = (new BooleanField(
					$canonical . '_field',
					$descriptor?->label ?? $canonical,
				));
			}
			else {
				$parameters[] = (new ChoiceField(
					$canonical . '_field',
					$descriptor?->label ?? $canonical,
					[],
					$descriptor?->required ?? false,
					false
				))->setResearch($research);
			}
		}

		return $parameters;
	}

	/**
	 * Resolves the stored form-element reference of every mapped field into a
	 * single-choice option list, so the configured value is displayed with its
	 * human-readable label.
	 */
	protected function resolveFieldParameterOptions(ColumnMap $columnMap): void
	{
		foreach ($columnMap->canonicalFields() as $canonical)
		{
			if ($columnMap->getDescriptor($canonical)?->type === FieldTypeEnum::BOOLEAN)
			{
				// Toggle: its parameter holds a value, not an element reference.
				continue;
			}

			$elementValue = $this->getParameterValue($canonical . '_field');

			if (empty($elementValue))
			{
				continue;
			}

			[$formId, $elementId] = explode('.', $elementValue);
			$elements = \EmundusHelperEvents::getFormElements((int) $formId, (int) $elementId, true, [], []);

			if (empty($elements))
			{
				continue;
			}

			$element = $elements[0];
			$options = [new ChoiceFieldValue($element->form_id . '.' . $element->id, Text::_($element->label) . ' (' . Text::_($element->form_label) . ')')];

			foreach ($this->parameters as $parameter)
			{
				if ($parameter->getName() === $canonical . '_field')
				{
					$parameter->setChoices($options);
				}
			}
		}
	}

	/**
	 * Resolves and type-validates the mapped form values for a single target
	 * into a canonical row keyed as the importer expects.
	 *
	 * Returns null when a required field is unmapped, empty in this file, or
	 * fails type validation (the row cannot produce a usable entity). Optional
	 * fields that are empty or type-invalid are dropped, never guessed. Values
	 * that cannot be reduced to a scalar are treated as empty.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function buildValidatedRow(
		ColumnMap $columnMap,
		ActionTargetEntity $target,
		FormDataConditionResolver $resolver,
		TypeValidator $validator
	): ?array {
		$required = $columnMap->requiredFields();
		$row      = [];

		foreach ($columnMap->canonicalFields() as $canonical)
		{
			$isRequired     = in_array($canonical, $required, true);
			$descriptor     = $columnMap->getDescriptor($canonical);
			$parameterValue = $this->getParameterValue($canonical . '_field');

			// A BOOLEAN field is exposed as a toggle, so its parameter carries the
			// value itself instead of a form element reference to resolve.
			if ($descriptor?->type === FieldTypeEnum::BOOLEAN)
			{
				if ($parameterValue !== null && $parameterValue !== '')
				{
					$row[$canonical] = $this->toBooleanToken($parameterValue);
				}

				continue;
			}

			if (empty($parameterValue))
			{
				if ($isRequired)
				{
					return null;
				}
				continue;
			}

			$value = $this->reduceToScalar($resolver->resolveValue($target, $parameterValue), $canonical, $target->getFile());

			if ($value === null || $value === '')
			{
				if ($isRequired)
				{
					return null;
				}
				continue;
			}

			// Canonicalise before validating, so the stored value never depends on
			// how the form element spelled it (Fabrik phone elements prefix the
			// ISO2 country: "FR+33123456789").
			if ($descriptor?->format === 'E.164' && is_string($value))
			{
				$value = PhoneNumberTransformer::toE164($value) ?? $value;
			}

			if ($descriptor !== null && !empty($validator->validate($value, $descriptor)))
			{
				// Type mismatch (e.g. a form value that is not a valid enum/date):
				// a required field makes the whole row unusable, an optional one is
				// simply dropped so it never reaches the importer's strict casts.
				if ($isRequired)
				{
					return null;
				}
				continue;
			}

			$row[$canonical] = $value;
		}

		return $row;
	}

	/**
	 * Normalizes a toggle value into the token a BOOLEAN column carries in a
	 * tabular source, which the importers read either as a '0' comparison or as
	 * a boolean cast.
	 */
	private function toBooleanToken(mixed $value): string
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
	}

	/**
	 * Reduces a resolved form value to the single scalar the importers expect.
	 *
	 * Multi-valued elements (checkboxes, multiple database joins, ordered lists)
	 * resolve to an array: a lone entry is the scalar itself, while several
	 * entries have no meaningful scalar projection — casting them would store
	 * "Array". Such values are discarded, so a required field makes the row
	 * unusable and an optional one is simply dropped.
	 */
	private function reduceToScalar(mixed $value, string $canonical, ?string $file): mixed
	{
		if ($value === null || is_scalar($value))
		{
			return $value;
		}

		if (is_array($value) && count($value) === 1)
		{
			$single = reset($value);

			if ($single === null || is_scalar($single))
			{
				return $single;
			}
		}

		if ($value !== [])
		{
			Log::add(
				'Discarding field "' . $canonical . '" for file ' . $file . ': the mapped element resolved to a non-scalar value.',
				Log::WARNING,
				'com_emundus.action'
			);
		}

		return null;
	}

	/**
	 * Applies the importer with the same conflict semantics as the import
	 * pipeline: SKIP/UPDATE consult exists(); CREATE_NEW always inserts.
	 * Throws on persistence failure so the caller can flag the target.
	 *
	 * @param array<string, mixed> $row
	 */
	protected function applyImport(
		EntityImporterInterface $importer,
		array $row,
		ImportContext $context,
		ImportConflictModeEnum $mode
	): void {
		if ($mode !== ImportConflictModeEnum::CREATE_NEW && $importer->exists($row, $context))
		{
			if ($mode === ImportConflictModeEnum::UPDATE && $importer instanceof UpdatableEntityImporter)
			{
				$importer->update($row, $context);
			}

			// SKIP: leave the existing record untouched.
			return;
		}

		$importer->persist($row, $context);
	}
}
