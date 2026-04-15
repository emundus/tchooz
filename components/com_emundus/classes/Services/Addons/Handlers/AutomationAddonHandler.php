<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class AutomationAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		return $this->applyState(true);
	}

	public function onDeactivate(): bool
	{
		return $this->applyState(false);
	}

	private function applyState(bool $state): bool
	{
		$tasks = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$intState = $state ? 1 : 0;

		try {
			// ACL
			$actionRepository = new ActionRepository();
			$automationAction = $actionRepository->getByName('automation');
			$automationAction->setStatus($state);
			$tasks[] = $actionRepository->flush($automationAction);

			// toggle menu items
			$menuAliases = ['emundus-automations', 'automation-edit', 'emundus-tasks-history'];
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($intState))
				->where($db->quoteName('alias') . ' IN (' . implode(',', array_map([$db, 'quote'], $menuAliases)) . ')');
			$db->setQuery($query);
			$tasks[] = $db->execute();
		} catch (\Exception $e) {
			$tasks[] = false;
		}

		return !in_array(false, $tasks);
	}
}