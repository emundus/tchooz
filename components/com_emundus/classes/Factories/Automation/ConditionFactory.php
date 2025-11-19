<?php

namespace Tchooz\Factories\Automation;

use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;

class ConditionFactory
{
	public function fromJson(object $json): ConditionEntity
	{
		return new ConditionEntity(
			$json->id,
			$json->group_id ?? 0,
			ConditionTargetTypeEnum::from($json->type),
			$json->target,
			ConditionOperatorEnum::from($json->operator),
			$json->value,
		);
	}
}