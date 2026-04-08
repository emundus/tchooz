<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use EmundusModelFiles;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

class DeleteApplication extends ApplicationFileAction
{

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::DELETE;
	}

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		$deleted = false;

		if (!empty($applicationFileEntity->getFnum()))
		{
			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$filesModel = new EmundusModelFiles();

			$deleted = $filesModel->deleteFile($applicationFileEntity->getFnum(), $currentUser?->id);
		}

		return $deleted;
	}

	public function getRedirectUrl(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): ?string
	{
		return '/';
	}
}