<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

class ApplicationFileActionMoveToTab extends ApplicationFileAction
{

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::MOVE_TO_TAB;
	}

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		$moved = false;

		if (!isset($parameters['tab']) || !is_numeric($parameters['tab']))
		{
			throw new \Exception('Missing parameter');
		}

		if (!class_exists('EmundusModelApplication'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
		}
		$applicationModel = new \EmundusModelApplication();

		return $applicationModel->moveToTab($applicationFileEntity->getFnum(), $parameters['tab']);
	}
}