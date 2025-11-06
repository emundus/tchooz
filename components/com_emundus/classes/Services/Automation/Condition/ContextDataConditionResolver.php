<?php

namespace Tchooz\Services\Automation\Condition;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Services\Automation\EventDefinitionRegistry;

class ContextDataConditionResolver implements ConditionTargetResolverInterface
{

	/**
	 * @inheritDoc
	 */
	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::CONTEXTDATA->value;
	}

	/**
	 * @inheritDoc
	 */
	public function getAvailableFields(array $contextFilters): array
	{
		$fields = [];

		if (!empty($contextFilters['eventName'])) {
			$eventDefinitionRegistry = new EventDefinitionRegistry();
			$eventDefinition = $eventDefinitionRegistry->getEventDefinitionInstance($contextFilters['eventName']);

			if ($eventDefinition) {
				$fields = $eventDefinition->getParameters();
			}
		}

		return $fields;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveValue(ActionTargetEntity $context, string $fieldName): mixed
	{
		$foundValue = null;

		if (isset($context->getParameters()[$fieldName])) {
			$foundValue = $context->getParameters()[$fieldName];
		}

		return $foundValue;
	}

	public function searchable(): bool
	{
		return false;
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

	public static function getAllowedActionTargetTypes(): array
	{
		return [];
	}
}