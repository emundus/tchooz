<?php

namespace Tchooz\Traits;

use Joomla\CMS\Component\ComponentHelper;

trait TraitAutomatedTask
{

	public function getAutomatedTaskUserId(): ?int
	{
		$userId = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);

		return $userId;
	}

}