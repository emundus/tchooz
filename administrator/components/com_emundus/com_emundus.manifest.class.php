<?php
/**
 * eMundus: Installer Manifest Class
 *
 * @package     Joomla
 * @subpackage  eMundus
 * @author      eMundus
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserHelper;
use scripts\Release2_0_0Installer;

class Com_EmundusInstallerScript
{
	private $db;

	protected $manifest_cache;
	protected $schema_version;

	public function __construct()
	{
		// Get component manifest cache
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$query    = $this->db->getQuery(true);

		$query->select('extension_id, manifest_cache')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_emundus'));
		$this->db->setQuery($query);
		$extension            = $this->db->loadObject();
		$this->manifest_cache = json_decode($extension->manifest_cache);

		$query->clear()
			->select('version_id')
			->from($this->db->quoteName('#__schemas'))
			->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($extension->extension_id));
		$this->db->setQuery($query);
		$this->schema_version = $this->db->loadResult();

		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
	}

	/**
	 * Run before installation or upgrade run
	 *
	 * @param   string  $type    discover_install (Install unregistered extensions that have been discovered.)
	 *                           or install (standard install)
	 *                           or update (update)
	 * @param   object  $parent  installer object
	 *
	 * @return  void
	 */
	public function preflight($type, $parent)
	{
		if (version_compare(PHP_VERSION, '7.4.0', '<'))
		{
			echo "\033[31mThis extension works with PHP 7.4.0 or newer. Please contact your web hosting provider to update your PHP version. \033[0m\n";
			exit;
		}
	}

	/**
	 * Run when the component is installed
	 *
	 * @param   object  $parent  installer object
	 *
	 * @return bool
	 */
	public function install($parent)
	{
		$parent->getParent()->setRedirectURL('index.php?option=com_emundus');

		return true;
	}

	/**
	 * Run when the component is updated
	 *
	 * @param   object  $parent  installer object
	 *
	 * @return  bool
	 */
	public function update($parent)
	{
		$succeed = true;

		$cache_version = $this->manifest_cache->version;

		$firstrun = false;
		$regex    = '/^6\.[0-9]*/m';
		preg_match_all($regex, $cache_version, $matches, PREG_SET_ORDER, 0);
		if (!empty($matches))
		{
			$cache_version = (string) $parent->manifest->version;
			$firstrun      = true;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/release.php';

		$releases_path = JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/releases/';

		$query = $this->db->getQuery(true);

		$releases_available = scandir($releases_path);

		if ($this->manifest_cache)
		{
			foreach ($releases_available as $release)
			{
				if (pathinfo($release, PATHINFO_EXTENSION) === 'php')
				{
					$release_with_underscores = str_replace('.php', '', $release);
					$release_version          = str_replace('_', '.', $release_with_underscores);

					if (version_compare($cache_version, $release_version, '<=') || $firstrun)
					{
						EmundusHelperUpdate::displayMessage('Installing version ' . $release_version . '...');

						require_once $releases_path . $release;
						$class             = '\scripts\Release' . $release_with_underscores . 'Installer';
						$release_installer = new $class();
						$release_installed = $release_installer->install();
						if ($release_installed['status'])
						{
							EmundusHelperUpdate::displayMessage('Version ' . $release_version . ' installed', 'success');
						}
						else
						{
							EmundusHelperUpdate::displayMessage($release_installed['message'], 'error');
							$succeed = false;
						}
					}
				}
			}
		}

		return $succeed;
	}

	/**
	 * Run when the component is uninstalled.
	 *
	 * @param   object  $parent  installer object
	 *
	 * @return  void
	 */
	public function uninstall($parent)
	{
	}

	/**
	 * Run after installation or upgrade run
	 *
	 * @param   string  $type    discover_install (Install unregistered extensions that have been discovered.)
	 *                           or install (standard install)
	 *                           or update (update)
	 * @param   object  $parent  installer object
	 *
	 * @return  bool
	 */
	public function postflight($type, $parent)
	{

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		if (!$this->setSitename())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour du nom du site dans la configuration de eMundus.', 'error');
		}

		if (!$this->syncGantryLogo())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la synchronisation du logo dans la configuration de Gantry5.', 'error');
		}

		if (!$this->clearDashboard())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la suppression des tableaux de bord par défaut.', 'error');
		}

		if (!EmundusHelperUpdate::checkHealth())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification de la santé de l\'installation.', 'error');
		}

		if (!EmundusHelperUpdate::checkPageClass())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification des classes de pages.', 'error');
		}

		if (!$this->setAdminColorPalette())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour de la palette de couleurs de l\'administration.', 'error');
		}

		if (!$this->checkHtAccess())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification du fichier .htaccess.', 'error');
		}

		if (!$this->checkPasswordFields())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification du stockage des champs mot de passe.', 'error');
		}

		if (!$this->checkFabrikIcon())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification de l\'icone détails des listes Fabrik', 'error');
		}

		if (!$this->checkFabrikTemplate())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification des templates Fabrik', 'error');
		}

		if (!$this->convertDateToJDate())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la conversion des champs Fabrik de date à jdate', 'error');
		}

		if (!$this->checkAccountApplicationMenu())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification du menu compte utilisateur', 'error');
		}

		if(!$this->checkSSOAvailable())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification de la disponibilité du SSO', 'error');
		}

		if (!EmundusHelperUpdate::languageBaseToFile()['status'])
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour des fichiers de langue.', 'error');
		}

		if (!$this->recompileGantry5())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la recompilation de Gantry5.', 'error');
		}

		if (!EmundusHelperUpdate::clearJoomlaCache())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la suppression du cache Joomla.', 'error');
		}

		if(!$this->checkConfig())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification de la configuration.', 'error');
		}

		EmundusHelperUpdate::generateCampaignsAlias();

		return true;
	}

	private function recompileGantry5()
	{
		$dir = JPATH_BASE . '/templates/g5_helium/custom/css-compiled';
		if (is_dir($dir) && !empty($dir))
		{
			foreach (glob($dir . '/*') as $file)
			{
				unlink($file);
			}

			rmdir($dir);
		}

		return true;
	}

	private function setSitename()
	{
		$updated = false;

		$query = $this->db->getQuery(true);

		try
		{
			$query->select('custom_data')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('com_emundus'));
			$this->db->setQuery($query);
			$custom_data = $this->db->loadResult();

			if (!empty($custom_data))
			{
				$custom_data = json_decode($custom_data, true);

				$custom_data['sitename'] = Factory::getApplication()->get('sitename');
			}
			else
			{
				$custom_data = [
					'sitename' => Factory::getApplication()->get('sitename'),
				];
			}

			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('custom_data') . ' = ' . $this->db->quote(json_encode($custom_data)))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('com_emundus'));
			$this->db->setQuery($query);
			$updated = $this->db->execute();
		}
		catch (Exception $e)
		{
		}

		return $updated;
	}

	private function syncGantryLogo()
	{
		$synced = false;

		$query = $this->db->getQuery(true);

		if (file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml'))
		{
			$logo = JPATH_SITE . '/images/logo_custom.png';
			$query->clear()
				->select('id,content')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_custom'))
				->where($this->db->quoteName('title') . ' LIKE ' . $this->db->quote('Logo'));
			$this->db->setQuery($query);
			$logo_module = $this->db->loadObject();

			preg_match('#src="(.*?)"#i', $logo_module->content, $tab);
			$pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

			if (preg_match($pattern, $tab[1]))
			{
				$tab[1] = parse_url($tab[1], PHP_URL_PATH);
			}

			if (!empty($tab[1]))
			{
				$logo = str_replace('images/', 'gantry-media://', $tab[1]);

				$synced = EmundusHelperUpdate::updateYamlVariable('image', $logo, JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml');
			}
			elseif (file_exists($logo))
			{
				$logo = str_replace('images/', 'gantry-media://', $tab[1]);

				$synced = EmundusHelperUpdate::updateYamlVariable('image', $logo, JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml');
			}
		}
		else
		{
			$synced = true;
		}

		return $synced;
	}

	private function clearDashboard()
	{
		$query = $this->db->getQuery(true);

		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_dashboard'))
			->where($this->db->quoteName('user') . ' IN (62,95)');
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	private function setAdminColorPalette()
	{
		$colors_updated = false;

		$query = $this->db->getQuery(true);

		try
		{
			$query->select('id,params')
				->from($this->db->quoteName('#__template_styles'))
				->where($this->db->quoteName('template') . ' = ' . $this->db->quote('atum'))
				->where($this->db->quoteName('client_id') . ' = 1');
			$this->db->setQuery($query);
			$atum_template = $this->db->loadObject();

			if (!empty($atum_template->id))
			{
				$params = json_decode($atum_template->params, true);

				$params['bg-light'] = '#e4e6eb';

				$update         = [
					'id'     => $atum_template->id,
					'params' => json_encode($params)
				];
				$update         = (object) $update;
				$colors_updated = $this->db->updateObject('#__template_styles', $update, 'id');
			}
		}
		catch (Exception $e)
		{
		}

		return $colors_updated;
	}

	private function checkHtAccess()
	{
		$current_htaccess = file_get_contents(JPATH_ROOT . '/.htaccess');

		// First we backup current htaccess file
		copy(JPATH_ROOT . '/.htaccess', JPATH_ROOT . '/.htaccess.bak');

		$tchooz_base_rules   = file_get_contents(JPATH_ROOT . '/.htaccess.tchooz.base.rules.txt');
		$joomla_rules        = file_get_contents(JPATH_ROOT . '/htaccess.txt');
		$tchooz_custom_rules = file_get_contents(JPATH_ROOT . '/.htaccess.tchooz.custom.rules.txt');

		// First run we empty current htaccess file
		if (!strpos($current_htaccess, '# Start of Tchooz .htaccess file (BASE)') && !strpos($current_htaccess, '# Start of Tchooz .htaccess file (CUSTOM)'))
		{
			$current_htaccess = '';
			$current_htaccess .= $joomla_rules;
			$current_htaccess .= PHP_EOL . PHP_EOL;
			$current_htaccess .= $tchooz_base_rules;
			$current_htaccess .= PHP_EOL;
			$current_htaccess .= $tchooz_custom_rules;

			$checked = file_put_contents(JPATH_ROOT . '/.htaccess', $current_htaccess);
		}
		else
		{
			// Replace Joomla rules
			$cur_htaccess_joomla_rules = strstr($current_htaccess, '# Start of Tchooz .htaccess file (BASE)', true);
			$current_htaccess          = str_replace($cur_htaccess_joomla_rules, $joomla_rules . PHP_EOL, $current_htaccess);
			//

			// Replace base rules
			$tchooz_base_starts = strpos($tchooz_base_rules, "# Start of Tchooz .htaccess file (BASE)") + strlen("# Start of Tchooz .htaccess file (BASE)");
			$tchooz_base_ends   = strpos($tchooz_base_rules, "# End of Tchooz .htaccess file (BASE)", $tchooz_base_starts);
			$tchooz_base_block  = substr($tchooz_base_rules, $tchooz_base_starts, $tchooz_base_ends - $tchooz_base_starts);

			$cur_htaccess_base_starts = strpos($current_htaccess, "# Start of Tchooz .htaccess file (BASE)") + strlen("# Start of Tchooz .htaccess file (BASE)");
			$cur_htaccess_base_ends   = strpos($current_htaccess, "# End of Tchooz .htaccess file (BASE)", $cur_htaccess_base_starts);
			$cur_htaccess_base_block  = substr($current_htaccess, $cur_htaccess_base_starts, $cur_htaccess_base_ends - $cur_htaccess_base_starts);

			$current_htaccess = str_replace($cur_htaccess_base_block, $tchooz_base_block, $current_htaccess);
			//

			// Check if Custom rules are present
			if (!strpos($current_htaccess, '# Start of Tchooz .htaccess file (CUSTOM)'))
			{
				$current_htaccess .= PHP_EOL;
				$current_htaccess .= $tchooz_custom_rules;
			}
			//

			// Save file
			$checked = file_put_contents(JPATH_ROOT . '/.htaccess', $current_htaccess);
		}

		return $checked;
	}

	private function checkPasswordFields()
	{
		$checked = true;

		$query = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where('json_valid(`params`)')
				->where('json_extract(`params`, "$.password") = "1"');
			$this->db->setQuery($query);
			$password_elements = $this->db->loadObjectList();

			foreach ($password_elements as $password_element)
			{
				$params                = json_decode($password_element->params, true);
				$params['store_in_db'] = "0";

				$query->clear()
					->update($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($password_element->id));
				$this->db->setQuery($query);
				$checked = $this->db->execute();
			}
		}
		catch (Exception $e)
		{
			$checked = false;
		}

		return $checked;
	}

	private function checkFabrikIcon()
	{
		$checked = true;

		$query = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_lists'))
				->where('json_valid(`params`)')
				->where('json_extract(`params`, "$.list_detail_link_icon") IS NULL OR json_extract(`params`, "$.list_detail_link_icon") = "search"');
			$this->db->setQuery($query);
			$fabrik_lists = $this->db->loadObjectList();

			// Update list_detail_link_icon by visibility
			foreach ($fabrik_lists as $fabrik_list)
			{
				$params                          = json_decode($fabrik_list->params, true);
				$params['list_detail_link_icon'] = "visibility";

				$query->clear()
					->update($this->db->quoteName('#__fabrik_lists'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($fabrik_list->id));
				$this->db->setQuery($query);
				$checked = $this->db->execute();
			}
		}
		catch (Exception $e)
		{
			$checked = false;
		}

		return $checked;
	}

	private function checkFabrikTemplate()
	{
		$checked = true;
		$query   = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->update($this->db->quoteName('#__fabrik_lists'))
				->set($this->db->quoteName('template') . ' = ' . $this->db->quote('emundus'))
				->where($this->db->quoteName('template') . ' = ' . $this->db->quote('bootstrap'));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('form_template') . ' = ' . $this->db->quote('emundus'))
				->where($this->db->quoteName('form_template') . ' = ' . $this->db->quote('bootstrap'));
			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('view_only_template') . ' = ' . $this->db->quote('emundus'))
				->where($this->db->quoteName('view_only_template') . ' = ' . $this->db->quote('bootstrap'));
			$this->db->setQuery($query);
			$checked = $this->db->execute();
		}
		catch (Exception $e)
		{
			$checked = false;
		}

		return $checked;
	}

	private function convertDateToJDate()
	{
		$converted = true;

		$query = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('plugin') . ' LIKE ' . $this->db->quote('date'))
				->where('json_valid(`params`)');
			$this->db->setQuery($query);
			$date_elements = $this->db->loadObjectList();

			foreach ($date_elements as $date_element)
			{
				$params = json_decode($date_element->params, true);

				$params['jdate_showtime']              = $params['date_showtime'];
				$params['jdate_time_24']               = $params['date_24hour'];
				$params['jdate_show_week_numbers']     = "0";
				$params['jdate_time_format']           = "";
				$params['jdate_store_as_local']        = $params['date_store_as_local'];
				$params['jdate_table_format']          = $params['date_table_format'];
				$params['jdate_form_format']           = $params['date_form_format'];
				$params['jdate_defaulttotoday']        = $params['date_defaulttotoday'];
				$params['jdate_alwaystoday']           = $params['date_alwaystoday'];
				$params['jdate_allow_typing_in_field'] = $params['date_allow_typing_in_field'];
				$params['jdate_csv_offset_tz']         = $params['date_csv_offset_tz'];

				unset($params['date_which_time_picker']);
				unset($params['date_showtime']);
				unset($params['date_show_seconds']);
				unset($params['date_24hour']);
				unset($params['bootstrap_time_class']);
				unset($params['date_store_as_local']);
				unset($params['date_table_format']);
				unset($params['date_form_format']);
				unset($params['date_defaulttotoday']);
				unset($params['date_alwaystoday']);
				unset($params['date_allow_typing_in_field']);
				unset($params['date_csv_offset_tz']);
				unset($params['date_firstday']);
				unset($params['date_advanced']);
				unset($params['date_allow_func']);
				unset($params['date_allow_php_func']);
				unset($params['date_observe']);

				$query->clear()
					->update($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('plugin') . ' = ' . $this->db->quote('jdate'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($date_element->id));
				$this->db->setQuery($query);
				$converted = $this->db->execute();
			}

			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('plugin') . ' LIKE ' . $this->db->quote('jdate'))
				->where('json_valid(`params`)');
			$this->db->setQuery($query);
			$jdate_elements = $this->db->loadObjectList();

			// Check format slashes for jdate elements
			foreach ($jdate_elements as $jdate_element)
			{
				$params = json_decode($jdate_element->params, true);

				$params['jdate_table_format'] = str_replace('\\', '', $params['jdate_table_format']);
				$params['jdate_form_format']  = str_replace('\\', '', $params['jdate_form_format']);

				$query->clear()
					->update($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($jdate_element->id));
				$this->db->setQuery($query);
				$this->db->execute();
			}
		}
		catch (Exception $e)
		{
			$converted = false;
		}

		return $converted;
	}

	private function checkAccountApplicationMenu()
	{
		$checked = false;

		try
		{
			$query = $this->db->getQuery(true);

			$query->select('id,published')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=application&format=raw&layout=account'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('application'));
			$this->db->setQuery($query);
			$account_menu = $this->db->loadObject();

			if (empty($account_menu->id))
			{
				$datas        = [
					'menutype'     => 'application',
					'title'        => 'Compte de l\'utilisateur',
					'alias'        => 'user-account',
					'link'         => 'index.php?option=com_emundus&view=application&format=raw&layout=account',
					'note'         => '12|r',
					'type'         => 'url',
					'component_id' => 0,
					'access'       => 6
				];
				$account_menu = EmundusHelperUpdate::addJoomlaMenu($datas);

				if ($account_menu['status'])
				{
					$checked = EmundusHelperUpdate::insertFalangTranslation(1, $account_menu['id'], 'menu', 'title', 'User account');
				}
			} else {
				$account_menu->published = 1;
				$checked = $this->db->updateObject('#__menu', $account_menu, 'id');
			}
		}
		catch (Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			$checked = false;
		}

		return $checked;
	}

	private function checkConfig()
	{
		$checked = false;
		$options = [];

		$query = $this->db->getQuery(true);

		try
		{
			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = 0')
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('plg_authentication_cookie'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('authentication'));
			$this->db->setQuery($query);
			$checked = $this->db->execute();

			$options['frontediting'] = 0;

			//Prepare cookies config
			$cookie_domain = Factory::getApplication()->get('live_site','');
			if(!empty($cookie_domain) && strpos($cookie_domain, 'localhost') === false) {
				$cookie_domain = explode('//',$cookie_domain);
				$cookie_domain = $cookie_domain[1];
				if(substr($cookie_domain, -1) == '/') {
					$cookie_domain = substr($cookie_domain, 0, -1);
				}

				if(!empty($cookie_domain)) {
					$options['cookie_domain'] = $cookie_domain;
				}

				$options['cookie_path'] = '/';
			}
			//


			$app                     = Factory::getApplication();
			if (!$app->get('shared_session') || $app->get('session_name') == 'site')
			{
				$options['shared_session'] = true;
				$options['session_name']   = UserHelper::genRandomPassword(16);

				$query->clear()
					->delete($this->db->quoteName('#__session'));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			$checked = EmundusHelperUpdate::updateConfigurationFile($options);
		}
		catch (Exception $e)
		{
			$checked = false;
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
		}

		return $checked;
	}

	private function checkSSOAvailable()
	{
		$checked = true;
		$haveSSO = false;

		// Check if we have external authentication
		$emundusOauth2 = PluginHelper::getPlugin('authentication','emundus_oauth2');
		$ldap = PluginHelper::getPlugin('authentication','ldap');
		if(!empty($ldap)) {
			$haveSSO = true;
		}
		elseif(!empty($emundusOauth2))
		{
			$oauth2Config = json_decode($emundusOauth2->params);

			if(!empty($oauth2Config->configurations)) {
				foreach ($oauth2Config->configurations as $config) {
					if(in_array($config->display_on_login,[1,3,4])) {
						$haveSSO = true;
						break;
					}
				}
			}
		}

		$query = $this->db->getQuery(true);
		if($haveSSO) {
			// Enable new_account_sso email
			try
			{
				$query->clear()
					->update($this->db->quoteName('#__emundus_setup_emails'))
					->set($this->db->quoteName('published') . ' = 1')
					->where($this->db->quoteName('lbl') . ' = ' . $this->db->quote('new_account_sso'));
				$this->db->setQuery($query);
				$checked = $this->db->execute();
			}
			catch (Exception $e)
			{
				EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
				$checked = false;
			}
		} else {
			// Disable new_account_sso email
			try
			{
				$query->clear()
					->update($this->db->quoteName('#__emundus_setup_emails'))
					->set($this->db->quoteName('published') . ' = 0')
					->where($this->db->quoteName('lbl') . ' = ' . $this->db->quote('new_account_sso'));
				$this->db->setQuery($query);
				$checked = $this->db->execute();
			}
			catch (Exception $e)
			{
				EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
				$checked = false;
			}
		}

		return $checked;
	}
}
