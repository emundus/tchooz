<?php
/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.8 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Demo: 
 # Support: support@web357.com
 # Last modified: Tuesday 03 February 2026, 10:20:16 AM
 ========================================================= */
defined('_JEXEC') or die;

use Joomla\CMS\Version;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;
class PlgSystemMicrosoftoutlook365mailconnectInstallerScriptHelper
{
	public $name            = '';
	public $alias           = '';
	public $extname         = '';
	public $extension_type  = '';
	public $plugin_folder   = 'system';
	public $module_position = 'web357';
	public $client_id       = 0;
	public $install_type    = 'install';
	public $show_message    = true;
	public $db              = null;
	public $softbreak       = null;
	public $mini_version 	= null;

	public function __construct(&$params)
	{
		$this->extname = $this->extname ?: $this->alias;
		$this->db      = Factory::getDbo();

		// Get Joomla's version
		$jversion = new Version();
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$this->mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
	}

	public function preflight($route, $adapter)
	{
		if (!in_array($route, array('install', 'update')))
		{
			return true;
		}

		Factory::getLanguage()->load('plg_system_web357installer', JPATH_PLUGINS . '/system/web357installer');

		if ($this->show_message && $this->isInstalled())
		{
			$this->install_type = 'update';
		}

		if ($this->onBeforeInstall($route) === false)
		{
			return false;
		}

		return true;
	}

	public function postflight($route, $adapter)
	{
		$this->removeGlobalLanguageFiles();

		if (version_compare($this->mini_version, "4.0", "<"))
		{
			$this->removeUnusedLanguageFiles();
		}

		Factory::getLanguage()->load($this->getPrefix() . '_' . $this->extname, $this->getMainFolder());

		if (!in_array($route, array('install', 'update')))
		{
			return true;
		}

		$this->updateHttptoHttpsInUpdateSites();

		if ($this->onAfterInstall($route) === false)
		{
			return false;
		}

		if ($route == 'install')
		{
			$this->publishExtension();
		}

		if ($this->show_message)
		{
			$this->addInstalledMessage();
		}

		Factory::getCache()->clean('com_plugins');
		Factory::getCache()->clean('_system');

		return true;
	}

	public function isInstalled()
	{
		if ( ! is_file($this->getInstalledXMLFile()))
		{
			return false;
		}

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('extension_id'))
			->from('#__extensions')
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote($this->extension_type))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->getElementName()));
		$this->db->setQuery($query, 0, 1);
		$result = $this->db->loadResult();

		return empty($result) ? false : true;
	}

	public function getMainFolder()
	{
		switch ($this->extension_type)
		{
			case 'plugin' :
				return JPATH_PLUGINS . '/' . $this->plugin_folder . '/' . $this->extname;

			case 'component' :
				return JPATH_ADMINISTRATOR . '/components/com_' . $this->extname;

			case 'module' :
				return JPATH_SITE . '/modules/mod_' . $this->extname;

			case 'library' :
				return JPATH_SITE . '/libraries/' . $this->extname;
		}
	}

	public function getInstalledXMLFile()
	{
		return $this->getXMLFile($this->getMainFolder());
	}

	public function getCurrentXMLFile()
	{
		return $this->getXMLFile(__DIR__);
	}

	public function getXMLFile($folder)
	{
		switch ($this->extension_type)
		{
			case 'module' :
				return $folder . '/mod_' . $this->extname . '.xml';

			default :
				return $folder . '/' . $this->extname . '.xml';
		}
	}

	public function uninstallExtension($extname, $type = 'plugin', $folder = 'system', $show_message = true)
	{
		if (empty($extname))
		{
			return;
		}

		$folders = array();

		switch ($type)
		{
			case 'plugin';
				$folders[] = JPATH_PLUGINS . '/' . $folder . '/' . $extname;
				break;

			case 'component':
				$folders[] = JPATH_ADMINISTRATOR . '/components/com_' . $extname;
				$folders[] = JPATH_SITE . '/components/com_' . $extname;
				break;

			case 'module':
				$folders[] = JPATH_ADMINISTRATOR . '/modules/mod_' . $extname;
				$folders[] = JPATH_SITE . '/modules/mod_' . $extname;
				break;
		}

		if ( ! $this->foldersExist($folders))
		{
			return;
		}

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('extension_id'))
			->from('#__extensions')
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->getElementName($type, $extname)))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote($type));

		if ($type == 'plugin')
		{
			$query->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($folder));
		}

		$this->db->setQuery($query);
		$ids = $this->db->loadColumn();

		if (empty($ids))
		{
			foreach ($folders as $folder)
			{
				Folder::delete($folder);
			}

			return;
		}

		$ignore_ids = Factory::getApplication()->getUserState('rl_ignore_uninstall_ids', array());

		if (Factory::getApplication()->input->get('option') == 'com_installer' && Factory::getApplication()->input->get('task') == 'remove')
		{
			// Don't attempt to uninstall extensions that are already selected to get uninstalled by them selves
			$ignore_ids = array_merge($ignore_ids, Factory::getApplication()->input->get('cid', array(), 'array'));
			Factory::getApplication()->input->set('cid', array_merge($ignore_ids, $ids));
		}

		$ids = array_diff($ids, $ignore_ids);

		if (empty($ids))
		{
			return;
		}

		$ignore_ids = array_merge($ignore_ids, $ids);
		Factory::getApplication()->setUserState('rl_ignore_uninstall_ids', $ignore_ids);

		foreach ($ids as $id)
		{
			$tmpInstaller = new Installer();
			$tmpInstaller->uninstall($type, $id);
		}

		if ($show_message)
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf(
					'COM_INSTALLER_UNINSTALL_SUCCESS',
					Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($type))
				)
			);
		}
	}

	public function foldersExist($folders = array())
	{
		foreach ($folders as $folder)
		{
			if (is_dir($folder))
			{
				return true;
			}
		}

		return false;
	}

	public function uninstallPlugin($extname, $folder = 'system', $show_message = true)
	{
		$this->uninstallExtension($extname, 'plugin', $folder, $show_message);
	}

	public function uninstallComponent($extname, $show_message = true)
	{
		$this->uninstallExtension($extname, 'component', null, $show_message);
	}

	public function uninstallModule($extname, $show_message = true)
	{
		$this->uninstallExtension($extname, 'module', null, $show_message);
	}

	public function publishExtension()
	{
		switch ($this->extension_type)
		{
			case 'plugin' :
				$this->publishPlugin();

			case 'module' :
				$this->publishModule();
		}
	}

	public function publishPlugin()
	{
		// if plugin is system, should be disabled by default
		if ($this->plugin_folder == 'system') {
			$enabled = 0;
		} else {
			$enabled = 1;
		}

		$query = $this->db->getQuery(true)
			->update('#__extensions')
			->set($this->db->quoteName('enabled') . ' = ' . $enabled)
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->extname))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($this->plugin_folder));
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function publishModule()
	{
		// Get module id
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from('#__modules')
			->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_' . $this->extname))
			->where($this->db->quoteName('client_id') . ' = ' . (int) $this->client_id);
		$this->db->setQuery($query, 0, 1);
		$id = $this->db->loadResult();

		if ( ! $id)
		{
			return;
		}

		// check if module is already in the modules_menu table (meaning is is already saved)
		$query->clear()
			->select($this->db->quoteName('moduleid'))
			->from('#__modules_menu')
			->where($this->db->quoteName('moduleid') . ' = ' . (int) $id);
		$this->db->setQuery($query, 0, 1);
		$exists = $this->db->loadResult();

		if ($exists)
		{
			return;
		}

		// Get highest ordering number in position
		$query->clear()
			->select($this->db->quoteName('ordering'))
			->from('#__modules')
			->where($this->db->quoteName('position') . ' = ' . $this->db->quote($this->module_position))
			->where($this->db->quoteName('client_id') . ' = ' . (int) $this->client_id)
			->order('ordering DESC');
		$this->db->setQuery($query, 0, 1);
		$ordering = $this->db->loadResult();
		$ordering++;

		// publish module and set ordering number
		$query->clear()
			->update('#__modules')
			->set($this->db->quoteName('published') . ' = 1')
			->set($this->db->quoteName('ordering') . ' = ' . (int) $ordering)
			->set($this->db->quoteName('position') . ' = ' . $this->db->quote($this->module_position))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);
		$this->db->setQuery($query);
		$this->db->execute();

		// add module to the modules_menu table
		$query->clear()
			->insert('#__modules_menu')
			->columns(array($this->db->quoteName('moduleid'), $this->db->quoteName('menuid')))
			->values((int) $id . ', 0');
		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function addInstalledMessage()
	{
		Factory::getApplication()->enqueueMessage(
			Text::sprintf(
				Text::_($this->install_type == 'update' ? 'W357_THE_EXTENSION_HAS_BEEN_UPDATED_SUCCESSFULLY' : 'W357_THE_EXTENSION_HAS_BEEN_INSTALLED_SUCCESSFULLY'),
				'<strong>' . Text::_($this->name) . '</strong>',
				'<strong>' . $this->getVersion() . '</strong>',
				$this->getFullType()
			)
		);
	}

	public function getPrefix()
	{
		switch ($this->extension_type)
		{
			case 'plugin';
				return Text::_('plg_' . strtolower($this->plugin_folder));

			case 'component':
				return Text::_('com');

			case 'module':
				return Text::_('mod');

			case 'library':
				return Text::_('lib');

			default:
				return $this->extension_type;
		}
	}

	public function getElementName($type = null, $extname = null)
	{
		$type    = is_null($type) ? $this->extension_type : $type;
		$extname = is_null($extname) ? $this->extname : $extname;

		switch ($type)
		{
			case 'component' :
				return 'com_' . $extname;

			case 'module' :
				return 'mod_' . $extname;

			case 'plugin' :
			default:
				return $extname;
		}
	}

	public function getFullType()
	{
		return Text::_('W357_' . strtoupper($this->getPrefix()));
	}

	public function getVersion($file = '')
	{
		$file = $file ?: $this->getCurrentXMLFile();

		if ( ! is_file($file))
		{
			return '';
		}

		$xml = Installer::parseXMLInstallFile($file);

		if ( ! $xml || ! isset($xml['version']))
		{
			return '';
		}

		return $xml['version'];
	}

	public function isNewer()
	{
		if ( ! $installed_version = $this->getVersion($this->getInstalledXMLFile()))
		{
			return true;
		}

		$package_version = $this->getVersion();
		return version_compare($installed_version, $package_version, '<=');
	}

	public function canInstall()
	{
		// The extension is not installed yet
		if ( ! $installed_version = $this->getVersion($this->getInstalledXMLFile()))
		{
			return true;
		}

		// The free version is installed. So any version is ok to install
		if (strpos($installed_version, 'PRO') === false)
		{
			return true;
		}

		// Current package is a pro version, so all good
		if (strpos($this->getVersion(), 'PRO') !== false)
		{
			return true;
		}

		Factory::getLanguage()->load($this->getPrefix() . '_' . $this->extname, __DIR__);

		Factory::getApplication()->enqueueMessage(Text::_('W357_ERROR_PRO_TO_FREE'), 'error');

		Factory::getApplication()->enqueueMessage(
			html_entity_decode(
				Text::sprintf(
					'W357_ERROR_UNINSTALL_FIRST',
					'<a href="//www.web357.com/product/' . $this->url_alias . '" target="_blank">',
					'</a>',
					Text::_($this->name)
				)
			), 'error'
		);

		return false;
	}

	public function onBeforeInstall($route)
	{
		if ( ! $this->canInstall())
		{
			return false;
		}

		return true;
	}

	public function onAfterInstall($route)
	{
	}

	public function delete($files = array())
	{
		foreach ($files as $file)
		{
			if (is_dir($file) && is_dir($file))
			{
				Folder::delete($file);
			}

			if (is_file($file) && is_file($file))
			{
				File::delete($file);
			}
		}
	}

	public function fixAssetsRules($rules = '{"core.admin":[],"core.manage":[]}')
	{
		// replace default rules value {} with the correct initial value
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__assets'))
			->set($this->db->quoteName('rules') . ' = ' . $this->db->quote($rules))
			->where($this->db->quoteName('title') . ' = ' . $this->db->quote('com_' . $this->extname))
			->where($this->db->quoteName('rules') . ' = ' . $this->db->quote('{}'));
		$this->db->setQuery($query);
		$this->db->execute();
	}

	private function updateHttptoHttpsInUpdateSites()
	{
		$query = $this->db->getQuery(true)
			->update('#__update_sites')
			->set($this->db->quoteName('location') . ' = REPLACE('
				. $this->db->quoteName('location') . ', '
				. $this->db->quote('http://') . ', '
				. $this->db->quote('https://')
				. ')')
			->where($this->db->quoteName('location') . ' LIKE ' . $this->db->quote('http://updates.web357%'));
		$this->db->setQuery($query);
		$this->db->execute();
	}

	private function removeGlobalLanguageFiles()
	{
		if ($this->extension_type == 'library' || $this->alias === 'web357framework')
		{
			return;
		}

		$language_files = Folder::files(JPATH_ADMINISTRATOR . '/language', '\.' . $this->getPrefix() . '_' . $this->extname . '\.', true, true);

		// Remove override files
		foreach ($language_files as $i => $language_file)
		{
			if (strpos($language_file, '/overrides/') === false)
			{
				continue;
			}

			unset($language_files[$i]);
		}

		if (empty($language_files))
		{
			return;
		}

		File::delete($language_files);
	}

	private function removeUnusedLanguageFiles()
	{
		if ($this->extension_type == 'library')
		{
			return;
		}

		$main_language_dir_path = Folder::folders(__DIR__ . '/language');

		$installed_languages = array_merge(
			Folder::folders(JPATH_SITE . '/language'),
			Folder::folders(JPATH_ADMINISTRATOR . '/language')
		);

		$languages = array();
		if (is_array($main_language_dir_path) && is_array($installed_languages))
		{
			$languages = array_diff(
				$main_language_dir_path,
				$installed_languages 
			);
		}

		$delete_languages = array();

		if (!empty($languages))
		{
			foreach ($languages as $language)
			{
				$delete_languages[] = $this->getMainFolder() . '/language/' . $language;
			}
		}

		if (empty($delete_languages))
		{
			return;
		}

		// Remove folders
		$this->delete($delete_languages);
	}
}