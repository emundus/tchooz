<?php

namespace Tchooz\Services\Automation\Condition;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\MixedField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Services\Automation\FieldTransformer;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');

class AliasDataConditionResolver implements ConditionTargetResolverInterface
{

	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::ALIASDATA->value;
	}

	public static function getAllowedActionTargetTypes(): array
	{
		return [
			TargetTypeEnum::FILE,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableFields(array $contextFilters): array
	{
		$fields = [];
		$aliases = \EmundusHelperFabrik::getAllFabrikAliases();
		$sizeLimit = 100;

		// todo: for aliases, we could try to determine the type of field based on the element(s) type in fabrik
		// problem is that one alias can correspond to multiple elements of different types (ex: database join), or even different options (ex: not the same selection options for a database join, not the same radio options, etc.)
		if (empty($contextFilters['search']))
		{
			// limit the number of aliases returned to avoid performance issues
			$aliases = array_slice($aliases, 0, $sizeLimit);
			foreach ($aliases as $alias)
			{
				$fields[] = $this->getFieldFromAlias($alias);
			}
		}
		else
		{
			$searchTerm = strtolower($contextFilters['search']);

			foreach ($aliases as $alias)
			{
				if (str_contains(strtolower($alias), $searchTerm))
				{
					$fields[] = $this->getFieldFromAlias($alias);
				}
			}
		}

		if (!empty($contextFilters['storedValues']))
		{
			$storedValues = $contextFilters['storedValues'];
			foreach ($storedValues as $storedValue)
			{
				$found = false;
				foreach ($fields as $field)
				{
					if ($field->getName() === $storedValue)
					{
						$found = true;
						break;
					}
				}

				if (!$found)
				{
					$fields[] = $this->getFieldFromAlias($storedValue);
				}
			}
		}
		$fields = array_filter($fields);

		usort($fields, function (Field $a, Field $b) {
			return strcmp($a->getLabel(), $b->getLabel());
		});

		return $fields;
	}

	/**
	 * @param   string  $alias
	 *
	 * @return Field|null
	 */
	private function getFieldFromAlias(string $alias): ?Field
	{
		$field = null;

		if (!empty($alias))
		{
			$elements = \EmundusHelperFabrik::getElementsByAlias($alias);

			if (sizeof($elements) === 1)
			{
				$field = FieldTransformer::transformFabrikElementIntoField($elements[0]);
				$field->setName($alias);
				$field->setLabel($alias);
				$field->setGroup(null);
			}
			else if (sizeof($elements) > 1)
			{
				// check if all elements are of the same type
				$firstElementType = $elements[0]->plugin;
				$sameType = true;

				foreach ($elements as $element)
				{
					if ($element->plugin !== $firstElementType)
					{
						$sameType = false;
						break;
					}
				}

				if ($sameType)
				{
					$field = FieldTransformer::transformFabrikElementIntoField($elements[0]);
					$field->setName($alias);
					$field->setLabel($alias);
					$field->setGroup(null);
				}
			}

			if (empty($field))
			{
				$field = new MixedField($alias, $alias);
			}
		}

		return $field;
	}

	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed
	{
		$value = null;

		if (!empty($context->getFile()))
		{
			$fabrikHelper = new \EmundusHelperFabrik();
			$value = $fabrikHelper->getValueByAlias($fieldName, $context->getFile());

			if ($format === ValueFormatEnum::FORMATTED)
			{
				$value = $value['value'];
			}
			else if (isset($value['raw']))
			{
				$value = $value['raw'];
			}
		}

		return $value;
	}

	public function getColumnsForField(string $field): array
	{
		return [];
	}

	public function getJoins(string $field): array
	{
		return [];
	}

	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		return [];
	}

	public function searchable(): bool
	{
		return true;
	}
}