<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

class ApplicationFileActionCreateTab extends ApplicationFileAction
{
	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::CREATE_TAB;
	}

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		if (empty($parameters['name']))
		{
			throw new \Exception(Text::_('COM_EMUNDUS_NO_APPLICATION_FILE_ACTION_NAME'));
		}

		if (!class_exists('EmundusModelApplication'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
		}
		$applicationModel = new \EmundusModelApplication();
		$tabId = $applicationModel->createTab($parameters['name'], $applicationFileEntity->getUser()->id);

		return !empty($tabId);
	}
}