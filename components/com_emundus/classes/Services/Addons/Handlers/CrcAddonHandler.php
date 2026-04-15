<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Services\Addons\AbstractAddonHandler;

class CrcAddonHandler extends AbstractAddonHandler
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
			// toggle menu items
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set('published = ' . $db->quote($intState))
				->where('alias IN (' . $db->quote('relation-client') . ', ' . $db->quote('contact-form') . ', ' . $db->quote('organization-form') .')');
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// ACL
			$query->clear()
				->update($db->quoteName('#__emundus_setup_actions'))
				->set('status = ' . $db->quote($intState))
				->where('label IN (' . $db->quote('COM_EMUNDUS_ACL_CONTACT') . ', ' . $db->quote('COM_EMUNDUS_ACL_ORGANIZATION') .')');
			$db->setQuery($query);
			$tasks[] = $db->execute();
		} catch (\Exception $e) {
			$tasks[] = false;
		}

		return !in_array(false, $tasks);
	}
}