<?php

namespace Tchooz\Entities\Automation\TargetPredefinitions;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitionEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;

class TriggerUserPredefinition extends TargetPredefinitionEntity
{
	public function __construct()
	{
		parent::__construct(
			'trigger_user',
			'COM_EMUNDUS_AUTOMATION_TARGET_PREDEFINITION_TRIGGER_USER',
			TargetTypeEnum::USER,
			[TargetTypeEnum::USER]
		);
	}

	public function resolve(ActionTargetEntity $context): array
	{
		if (!empty($context->getTriggeredBy())) {

			$newContext = new ActionTargetEntity($context->getTriggeredBy(), null, $context->getTriggeredBy()->id, $context->getParameters(), $context->getCustom());
			return [$newContext];
		}

		return [];
	}
}