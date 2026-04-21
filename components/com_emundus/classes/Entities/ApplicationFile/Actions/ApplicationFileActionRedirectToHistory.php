<?php

namespace Tchooz\Entities\ApplicationFile\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\ApplicationFile\ApplicationFileActionsEnum;

class ApplicationFileActionRedirectToHistory extends ApplicationFileActionRedirectTo
{

	public function getActionType(): ApplicationFileActionsEnum
	{
		return ApplicationFileActionsEnum::HISTORY;
	}

	public function getRedirectUrl(ApplicationFileEntity $applicationFileEntity, array $parameters = [], ?User $currentUser = null): string
	{
		$link = 'index.php?option=com_emundus&view=application&layout=history';

		$items = Factory::getApplication()->getMenu()->getItems(['link'], [$link]);
		$redirectUrl = !empty($items) ? $items[0]->route : Route::_($link, false);

		$params = [
			'ccid' => $applicationFileEntity->getId(),
			'fnum' => $applicationFileEntity->getFnum(),
		];

		$query = http_build_query($params);
		$redirectUrl = !str_starts_with($redirectUrl, '/') ? Uri::base() . $redirectUrl : Uri::base() . substr($redirectUrl, 1);

		return $redirectUrl . (str_contains($redirectUrl, '?') ? '&' : '?') . $query;
	}
}