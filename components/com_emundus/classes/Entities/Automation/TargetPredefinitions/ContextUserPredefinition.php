<?php

namespace Tchooz\Entities\Automation\TargetPredefinitions;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitionEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ContextUserPredefinition extends TargetPredefinitionEntity
{
	public function __construct()
	{
		parent::__construct(
			'context_user',
			'COM_EMUNDUS_AUTOMATION_TARGET_PREDEFINITION_CONTEXT_USER',
			TargetTypeEnum::USER,
			[TargetTypeEnum::USER]
		);
	}

	public function resolve(ActionTargetEntity $context): array
	{
		if (!empty($context->getUserId())) {
			$newContext = clone $context;
			$newContext->setOriginalContext($context);
			return [$newContext];
		}

		return [];
	}
}