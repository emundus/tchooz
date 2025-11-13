<?php

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Addons\AddonRepository;

class CrcAddonHandler implements AddonHandlerInterface
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