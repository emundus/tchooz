<?php

namespace Tchooz\Entities\Automation\TargetPredefinitions;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitionEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;
use EmundusHelperAccess;

class UsersAssociatedToFilePredefinition extends TargetPredefinitionEntity
{
	public function __construct()
	{
		parent::__construct(
			'users_associated_to_file',
			'COM_EMUNDUS_AUTOMATION_TARGET_PREDEFINITION_USERS_ASSOCIATED_TO_FILE',
			TargetTypeEnum::USER,
			[TargetTypeEnum::FILE]
		);
	}

	public function resolve(ActionTargetEntity $context): array
	{
		$targets = [];

		if (!empty($context->getFile()))
		{
			if (!class_exists('EmundusHelperAccess'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
			}

			$userIds = EmundusHelperAccess::getUsersThatCanAccessToFile($context->getFile());

			foreach ($userIds as $userId)
			{
				$targets[] = new ActionTargetEntity($context->getTriggeredBy(), null, $userId, $context->getParameters(), $context->getCustom(), $context);
			}
		}

		return $targets;
	}
}