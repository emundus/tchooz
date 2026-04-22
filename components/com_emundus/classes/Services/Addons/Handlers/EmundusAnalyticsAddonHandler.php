<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Services\Addons\AbstractAddonHandler;

class EmundusAnalyticsAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		return $this->toggleExtension(true);
	}

	public function onDeactivate(): bool
	{
		return $this->toggleExtension(false);
	}

	private function toggleExtension(bool $state): bool
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = ' . (int) $state)
			->where($db->quoteName('element') . ' = ' . $db->quote('emundus_analytics'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
		$db->setQuery($query);
		return (bool) $db->execute();
	}

	public function checkEnabled(): bool
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->select($db->quoteName('enabled'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('element') . ' = ' . $db->quote('emundus_analytics'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
		$db->setQuery($query);
		return (bool) $db->loadResult();
	}
}