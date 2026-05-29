<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

class ApplicationFileActionPrint extends ApplicationFileActionRedirectTo
{

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::PRINT;
	}

	public function getRedirectUrl(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): string
	{
		return 'component/emundus/?task=pdf&fnum=' . $applicationFileEntity->getFnum();
	}
}