<?php

namespace Tchooz\Services\Addons;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Fields\Field;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\YesnoField;

class PublicSessionAddonHandler implements AddonHandlerInterface
{
	private AddonEntity $addon;

	public function __construct(AddonEntity $addon)
	{
		$this->addon = $addon;
	}

	public function toggle(bool $state): bool
	{
		$updates = [];
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();


		$query->clear()
			->select('value')
			->from($db->quoteName('#__emundus_setup_config'))
			->where($db->quoteName('namekey') . ' = ' . $db->quote($this->addon->getNamekey()));

		$db->setQuery($query);
		$config = json_decode($db->loadResult(), true);

		$config['enabled'] = $state;
		$query->clear()
			->update($db->quoteName('#__emundus_setup_config'))
			->set($db->quoteName('value') . ' = ' . $db->quote(json_encode($config)))
			->where($db->quoteName('namekey') . ' = ' . $db->quote($this->addon->getNamekey()));

		$db->setQuery($query);
		$updates[] = $db->execute();

		// toggle menus published state
		$links = ['index.php?option=com_emundus&view=publicaccess&layout=storetoken', 'index.php?option=com_emundus&view=publicaccess'];
		$query->clear()
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('published') . ' = ' . (int) $state)
			->where($db->quoteName('link') . ' IN (' . implode(',', $db->quote($links)) . ')');
		$db->setQuery($query);
		$updates[] = $db->execute();

		// toggle plugin state
		$query->clear()
			->update($db->quoteName('#__extensions'))
			->from($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . ' = ' . (int) $state)
			->where($db->quoteName('element') . ' = ' . $db->quote('emunduspublicaccess'))
			->andWhere($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->andWhere($db->quoteName('folder') . ' = ' . $db->quote('system'));
		$db->setQuery($query);
		$updates[] = $db->execute();

		if (!$state)
		{
			// TODO: destroy current public sessions
		}

		return !in_array(false, $updates, true);
	}

	/**
	 * @return array<Field>
	 */
	public function getParameters(): array
	{
		return [
			(new NumericField('token_validity_duration', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_DISPLAY_TOKEN_VALIDITY_DURATION_LABEL'), true))->setMin(1)->setMax(365),
			new YesnoField('display_import_public_file_action', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_DISPLAY_IMPORT_FILE_ACTION_LABEL'), true),
			new YesnoField('confirm_public_application_creation', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_CONFIRM_APPLICATION_CREATION_LABEL'), true),
			new YesnoField('display_retrieve_public_access_file_login_page', Text::_('COM_EMUNDUS_ADDON_PUBLIC_SESSION_DISPLAY_RETRIEVE_PUBLIC_ACCESS_FILE_LOGIN_PAGE_LABEL'), true),
		];
	}
}