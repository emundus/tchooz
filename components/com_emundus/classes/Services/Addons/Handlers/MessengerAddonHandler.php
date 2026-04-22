<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Services\Addons\AbstractAddonHandler;

class MessengerAddonHandler extends AbstractAddonHandler
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

		$state_integer = $state ? 1 : 0;

		if(!class_exists('EmundusHelperUpdate'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		}

		try
		{
			// Check if the extension and module is installed
			$query->clear()
				->select('count(extension_id)')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('module'))
				->where($db->quoteName('element') . ' = ' . $db->quote('mod_emundus_messenger_notifications'));
			$db->setQuery($query);
			$extension_installed = $db->loadResult();

			if (empty($extension_installed))
			{
				// Install the extension
				\EmundusHelperUpdate::installExtension('mod_emundus_messenger_notifications', 'mod_emundus_messenger_notifications', null, 'module', $state_integer, '', '{}', false, false);
			}
			else
			{
				$query->clear()
					->update($db->quoteName('#__extensions'))
					->set($db->quoteName('enabled') . ' = ' . $db->quote($state_integer))
					->where($db->quoteName('element') . ' = ' . $db->quote('mod_emundus_messenger_notifications'));
				$db->setQuery($query);
				$tasks[] = $db->execute();
			}

			// Check if the module is installed
			$query->clear()
				->select('count(id)')
				->from($db->quoteName('#__modules'))
				->where($db->quoteName('module') . ' = ' . $db->quote('mod_emundus_messenger_notifications'));
			$db->setQuery($query);
			$module_installed = $db->loadResult();

			if (empty($module_installed))
			{
				// Install the module
				\EmundusHelperUpdate::createModule('[APPLICANT] Messenger', 'header-c', 'mod_emundus_messenger_notifications', '{}', $state_integer, 1, 1, 0, 0, false);
			}
			else
			{
				$query->clear()
					->update($db->quoteName('#__modules'))
					->set($db->quoteName('published') . ' = ' . $db->quote($state_integer))
					->where($db->quoteName('module') . ' = ' . $db->quote('mod_emundus_messenger_notifications'));
				$db->setQuery($query);
				$tasks[] = $db->execute();
			}

			// Publish emails
			$emails = ['messenger_reminder', 'messenger_reminder_group'];
			$query->clear()
				->update($db->quoteName('#__emundus_setup_emails'))
				->set('published = ' . $db->quote($state_integer))
				->where('lbl IN (' . implode(',', $db->quote($emails)) . ')');
			$db->setQuery($query);
			$tasks[] = $db->execute();

			// Publish messages menu in application menu
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set('published = ' . $db->quote($state_integer))
				->where('menutype = ' . $db->quote('application'))
				->where('link LIKE ' . $db->quote('index.php?option=com_emundus&view=messenger&format=raw&layout=coordinator'));
			$db->setQuery($query);
			$tasks[] = $db->execute();
		}
		catch (\Exception $e)
		{
			$tasks[] = false;
		}

		return !in_array(false, $tasks, true);
	}
}

