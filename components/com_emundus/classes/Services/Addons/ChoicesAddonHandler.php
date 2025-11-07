<?php
/**
 * @package     Tchooz\Factories\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Addons\AddonRepository;

class ChoicesAddonHandler implements AddonHandlerInterface
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

		$state_integer = $state ? 1 : 0;

		// Toggle step type
		$query->update($db->quoteName('#__emundus_setup_step_types'))
			->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
			->where($db->quoteName('label') . ' = ' . $db->quote('COM_EMUNDUS_WORKFLOW_STEP_TYPE_CHOICES'));
		$db->setQuery($query);
		$tasks[] = $db->execute();
		//

		// Toggle acl
		$query->clear()
			->update($db->quoteName('#__emundus_setup_actions'))
			->set($db->quoteName('status') . ' = ' . $db->quote($state_integer))
			->where($db->quoteName('name') . ' = ' . $db->quote('application_choices'));
		$db->setQuery($query);
		$tasks[] = $db->execute();
		//

		// Toggle application menu
		$query->clear()
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
			->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_emundus&view=application&layout=applicationchoices&format=raw'))
			->where($db->quoteName('menutype') . ' = ' . $db->quote('application'));
		$db->setQuery($query);
		$tasks[] = $db->execute();
		//

		// Toggle applicant menu
		$query->clear()
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
			->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_emundus&view=application_choices'))
			->where($db->quoteName('menutype') . ' = ' . $db->quote('applicantmenu'));
		$db->setQuery($query);
		$tasks[] = $db->execute();
		//

		// Toggle the filter on all files views
		$query->clear()
			->select('id, params')
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=com_emundus&view=files'));
		$db->setQuery($query);
		$menus = $db->loadObjectList();

		foreach ($menus as $menu) {
			$params = json_decode($menu->params, true);
			$params['filter_application_choices'] = $state_integer;

			$menu->params = json_encode($params);
			$db->updateObject('#__menu', $menu, 'id');
		}
		//

		$addonValue = $this->addon->getValue();
		$addonValue->setEnabled($state);
		$this->addon->setValue($addonValue);

		$addonRepository = new AddonRepository();
		$tasks[] = $addonRepository->flush($this->addon);

		return !in_array(false, $tasks, true);
	}
}