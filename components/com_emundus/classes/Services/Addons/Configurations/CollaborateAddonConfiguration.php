<?php

namespace Tchooz\Services\Addons\Configurations;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Services\Addons\EmundusAddonConfiguration;

class CollaborateAddonConfiguration extends EmundusAddonConfiguration
{
	public const CONFIGURATION_GROUP = 'configuration';

	/**
	 * Rights written on jos_emundus_files_request when an applicant invites a collaborator.
	 */
	public const DEFAULT_RIGHTS = 'default_rights';

	/**
	 * Menu (Fabrik form running the emunduscollaborate plugin) targeted by the invitation link.
	 */
	public const ACCEPTANCE_MENU = 'acceptance_menu';

	public const RIGHTS = ['r', 'u', 'show_history', 'show_shared_users'];

	public const DEFAULT_RIGHTS_VALUE = ['r', 'u'];

	public function getParameters(): array
	{
		$configGroup = new FieldGroup(self::CONFIGURATION_GROUP, Text::_('COM_EMUNDUS_COLLABORATE_ADDON_CONFIGURATION_GROUP_LABEL'));

		return [
			(new ChoiceField(self::DEFAULT_RIGHTS, Text::_('COM_EMUNDUS_COLLABORATE_ADDON_PARAMETER_DEFAULT_RIGHTS_LABEL'), $this->getRightChoices(), false, true, $configGroup))
				->setDefaultValue(self::DEFAULT_RIGHTS_VALUE),
			(new ChoiceField(self::ACCEPTANCE_MENU, Text::_('COM_EMUNDUS_COLLABORATE_ADDON_PARAMETER_ACCEPTANCE_MENU_LABEL'), $this->getMenuChoices(), false, false, $configGroup))
				->setHelpText(Text::_('COM_EMUNDUS_COLLABORATE_ADDON_PARAMETER_ACCEPTANCE_MENU_HELP')),
		];
	}

	public function getDefaultParameters(): array
	{
		return [];
	}

	/**
	 * @return ChoiceFieldValue[]
	 */
	private function getRightChoices(): array
	{
		$labels = [
			'r'                 => 'COM_EMUNDUS_APPLICATION_SHARE_READ',
			'u'                 => 'COM_EMUNDUS_APPLICATION_SHARE_UPDATE',
			'show_history'      => 'COM_EMUNDUS_APPLICATION_SHARE_VIEW_HISTORY',
			'show_shared_users' => 'COM_EMUNDUS_APPLICATION_SHARE_VIEW_OTHERS',
		];

		return array_map(fn(string $right) => new ChoiceFieldValue($right, Text::_($labels[$right])), self::RIGHTS);
	}

	/**
	 * Unpublished menus are listed too: the addon handler unpublishes the acceptance menu on deactivation,
	 * it must stay selectable afterwards.
	 *
	 * @return ChoiceFieldValue[]
	 */
	private function getMenuChoices(): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->select($db->quoteName(['id', 'title']))
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('published') . ' IN (0, 1)')
			->where($db->quoteName('type') . ' = ' . $db->quote('component'))
			->order($db->quoteName('title'));
		$db->setQuery($query);

		return array_map(fn(object $menu) => new ChoiceFieldValue((string) $menu->id, $menu->title), $db->loadObjectList() ?: []);
	}
}
