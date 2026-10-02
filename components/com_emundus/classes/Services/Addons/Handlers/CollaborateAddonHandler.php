<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Services\Addons\AbstractAddonHandler;
use Tchooz\Services\Addons\Configurations\CollaborateAddonConfiguration;

class CollaborateAddonHandler extends AbstractAddonHandler
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
		$menuId = (int) $this->addon->getParam(CollaborateAddonConfiguration::ACCEPTANCE_MENU, CollaborateAddonConfiguration::CONFIGURATION_GROUP);

		if (empty($menuId))
		{
			return true;
		}

		try
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . ($state ? 1 : 0))
				->where($db->quoteName('id') . ' = ' . $menuId);
			$db->setQuery($query);

			return $db->execute();
		}
		catch (\Exception $e)
		{
			Log::add(
				'Failed to switch collaborate addon state to ' . ($state ? 'activated' : 'deactivated') . ': ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.addon'
			);

			return false;
		}
	}
}
