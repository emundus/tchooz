<?php

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;

class EmundusAnalyticsAddonHandler implements AddonHandlerInterface
{
	public function toggle(bool $state): bool
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