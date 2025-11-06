<?php

namespace Tchooz\Entities\Automation\TargetPredefinitions;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitionEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;

class ApplicantCurrentFilePredefinition extends TargetPredefinitionEntity
{
	public function __construct()
	{
		parent::__construct(
			'applicant_current_file',
			'COM_EMUNDUS_AUTOMATION_TARGET_PREDEFINITION_APPLICANT_CURRENT_FILE',
			TargetTypeEnum::FILE,
			[TargetTypeEnum::FILE]
		);
	}

	public function resolve(ActionTargetEntity $context): array
	{
		if (!empty($context->getFile())) {
			$newContext = clone $context;
			$newContext->setOriginalContext($context);
			return [$newContext];
		}

		return [];
	}
}