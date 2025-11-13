<?php

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Addons\AddonRepository;

class AutomationAddonHandler implements AddonHandlerInterface
{
	private AddonEntity $addon;

	public function __construct(AddonEntity $addon)
	{
		$this->addon = $addon;
	}


	public function toggle(bool $state): bool
	{
		$tasks = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$intState = $state ? 1 : 0;

		try {
			// ACL
			$query->clear()
				->update($db->quoteName('#__emundus_setup_actions'))
				->set($db->quoteName('status') . ' = ' . $db->quote($intState))
				->where($db->quoteName('name') . ' = ' . $db->quote('automation'));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// toggle menu items
			$menuAliases = ['emundus-automations', 'automation-edit', 'emundus-tasks-history'];
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($intState))
				->where($db->quoteName('alias') . ' IN (' . implode(',', array_map([$db, 'quote'], $menuAliases)) . ')');
			$db->setQuery($query);
			$tasks[] = $db->execute();

			$addonValue = $this->addon->getValue();
			$addonValue->setEnabled($state);
			$this->addon->setValue($addonValue);

			$addonRepository = new AddonRepository();
			$tasks[] = $addonRepository->flush($this->addon);
		} catch (\Exception $e) {
			$tasks[] = false;
		}

		return !in_array(false, $tasks);
	}
}