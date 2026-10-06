<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

/**
 * Opens the collaborators modal client-side (media/com_emundus/js/collaborate.js),
 * which relies on the legacy application endpoints (sharefilewith, removesharedfile, ...).
 */
class ApplicationFileActionCollaborate extends ApplicationFileAction
{

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::COLLABORATE;
	}

	public function isAvailableForFile(ApplicationFileEntity $applicationFileEntity, ?User $currentUser = null): bool
	{
		return $this->isFileOwner($applicationFileEntity, $currentUser);
	}

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		return true;
	}
}
