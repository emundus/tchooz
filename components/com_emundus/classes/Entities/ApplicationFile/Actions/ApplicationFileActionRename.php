<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

class ApplicationFileActionRename extends ApplicationFileAction
{
	public CONST PARAMETER_NAME_KEY = 'name';

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		$status = false;

		if (!empty($applicationFileEntity->getFnum())) {
			if (!empty($parameters[self::PARAMETER_NAME_KEY]))
			{
				$applicationFileEntity->setName($parameters[self::PARAMETER_NAME_KEY]);
				$applicationFileRepository = new ApplicationFileRepository();
				$status = $applicationFileRepository->flush($applicationFileEntity, $currentUser?->id);
			}
		}

		return $status;
	}

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::RENAME;
	}
}