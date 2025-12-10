<?php

namespace Tchooz\Services\Automation\Condition;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\MixedField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

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
	 * @param   array  $contextFilters
	 *
	 * @return Field[]
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
				$fields[] = new MixedField($alias, $alias);
			}
		}
		else
		{
			$searchTerm = strtolower($contextFilters['search']);

			foreach ($aliases as $alias)
			{
				if (str_contains(strtolower($alias), $searchTerm))
				{
					$fields[] = new MixedField($alias, $alias);
				}
			}
		}

		usort($fields, function (Field $a, Field $b) {
			return strcmp($a->getLabel(), $b->getLabel());
		});

		return $fields;
	}

	public function resolveValue(ActionTargetEntity $context, string $fieldName): mixed
	{
		$value = null;

		if (!empty($context->getFile()))
		{
			$fabrikHelper = new \EmundusHelperFabrik();
			$value = $fabrikHelper->getValueByAlias($fieldName, $context->getFile(), $context->getUserId());

			if (isset($value['raw']))
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