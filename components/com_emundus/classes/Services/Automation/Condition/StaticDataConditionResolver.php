<?php

namespace Tchooz\Services\Automation\Condition;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\ValueFormatEnum;

class StaticDataConditionResolver implements ConditionTargetResolverInterface
{

	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::STATICVALUE->value;
	}

	public static function getAllowedActionTargetTypes(): array
	{
		return [];
	}

	public function getAvailableFields(array $contextFilters): array
	{
		return [];
	}

	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed
	{
		$value = $fieldName;

		if (!empty($value)) {
			$value = trim($value, '"\' ');
			$value = strip_tags($value);
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
		return false;
	}
}