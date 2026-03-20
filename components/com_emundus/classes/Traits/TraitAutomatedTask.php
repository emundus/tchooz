<?php

namespace Tchooz\Traits;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\User;

trait TraitAutomatedTask
{
	public function getAutomatedTaskUserId(): ?int
	{
		return ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);
	}

	public function getAutomatedTaskUser(): ?User
	{
		$userId = $this->getAutomatedTaskUserId();

		return !empty($userId) ? Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId) : null;
	}
}