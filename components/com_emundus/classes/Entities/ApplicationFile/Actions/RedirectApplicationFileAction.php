<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

abstract class RedirectApplicationFileAction extends ApplicationFileAction
{
	abstract public function getRedirectUrl(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): string;

	public function execute(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): bool
	{
		return true;
	}
}