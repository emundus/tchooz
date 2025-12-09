<?php

namespace Tchooz\Traits;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;

trait TraitAutomatedTask
{

	public function getAutomatedTaskUserId(): ?int
	{
		$userId = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);

		return $userId;
	}

	public function getAutomatedTaskUser(): ?\Joomla\CMS\User\User
	{
		$userId = $this->getAutomatedTaskUserId();

		return Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
	}

}