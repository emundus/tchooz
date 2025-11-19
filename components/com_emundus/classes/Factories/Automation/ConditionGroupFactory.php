<?php

namespace Tchooz\Factories\Automation;

use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Enums\Automation\ConditionsAndorEnum;

class ConditionGroupFactory
{
	public function fromJson(object $json, $allGroups): ConditionGroupEntity
	{
		$conditions = [];
		if (!empty($json->conditions)) {
			$conditionFactory = new ConditionFactory();

			foreach ($json->conditions as $condition) {
				$conditions[] = $conditionFactory->fromJson($condition);
			}
		}

		$subGroups = [];
		foreach ($allGroups as $group) {
			if (isset($group->parent_id) && $group->parent_id === $json->id) {
				$subGroups[] = $this->fromJson($group, $allGroups);
			}
		}

		return new ConditionGroupEntity(
			$json->id,
			$conditions,
			isset($json->operator) ? ConditionsAndorEnum::from($json->operator) : ConditionsAndorEnum::AND,
			$json->parent_id ?? 0,
			$subGroups
		);
	}
}