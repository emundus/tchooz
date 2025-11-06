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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Emundus\Administrator\Attributes\PostflightAttribute;
use Joomla\Database\DatabaseInterface;
use Tchooz\Traits\TraitVersion;

class Com_EmundusInstallerScript
{
	use TraitVersion;

	private DatabaseInterface $db;

	protected array|object|null $manifest_cache;

	protected string|int|null $schema_version;

	public function __construct()
	{
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
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/EmundusTableColumn.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/EmundusColumnTypeEnum.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/EmundusTableForeignKey.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/src/Attributes/PostflightAttribute.php');
	}

	public function preflight(string $type, object $parent): void
	{
		if (version_compare(PHP_VERSION, '7.4.0', '<'))
		{
			EmundusHelperUpdate::displayMessage('This extension works with PHP 7.4.0 or newer. Please contact your web hosting provider to update your PHP version.', 'error');
			exit;
		}

		$query_str = 'SHOW TABLES LIKE ' . $this->db->quote('#__emundus_version');
		$this->db->setQuery($query_str);
		$table_exists = $this->db->loadResult();
		if(!$table_exists)
		{
			$columns = [
				[
					'name'   => 'update_date',
					'type'   => 'date',
					'null'   => 0,
				],
			];
			$primary_key_options = [
				'name' => 'version',
				'type' => 'varchar',
				'length' => 20,
				'auto_increment' => 0,
			];

			EmundusHelperUpdate::createTable('#__emundus_version', $columns,[], '', [], $primary_key_options);
		}
	}

	public function install(object $parent): bool
	{
		$parent->getParent()->setRedirectURL('index.php?option=com_emundus');

		return true;
	}

	public function update(object $parent): bool
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

		$releases_available = scandir($releases_path);
		natcasesort($releases_available);

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

							$date = Factory::getDate()->toSql();
							$existingVersion = $this->getVersion($this->db, $release_version);
							if($existingVersion)
							{
								if(!$this->updateVersion($this->db, $release_version, $date))
								{
									EmundusHelperUpdate::displayMessage('Version ' . $release_version . ' update failed', 'error');
									$succeed = false;
								}
							}
							else
							{
								if (!$this->createVersion($this->db, $release_version, $date))
								{
									EmundusHelperUpdate::displayMessage('Version ' . $release_version . ' creation failed', 'error');
									$succeed = false;
								}
							}
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

	public function uninstall(object $parent): void
	{}

	public function postflight(string $type, object $parent): bool
	{
		$methods       = $this->getPostflightMethods();
		foreach ($methods as $method => $name)
		{
			// Display message
			EmundusHelperUpdate::displayMessage('Exécution de la tâche post-installation : ' . $name);

			if (!$this->$method())
			{
				EmundusHelperUpdate::displayMessage('Erreur lors de l\'exécution de la tâche post-installation : ' . $name, 'error');
			}
		}

		if (!EmundusHelperUpdate::languageBaseToFile()['status'])
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour des fichiers de langue.', 'error');
		}

		if (!EmundusHelperUpdate::clearJoomlaCache())
		{
			EmundusHelperUpdate::displayMessage('Erreur lors de la suppression du cache Joomla.', 'error');
		}

		EmundusHelperUpdate::generateCampaignsAlias();

		return true;
	}

	private function getPostflightMethods(): array
	{
		$reflection = new ReflectionClass(self::class);
		$methods    = $reflection->getMethods();
		$results    = [];

		foreach ($methods as $method)
		{
			$attributes = $method->getAttributes(PostflightAttribute::class);

			if (!empty($attributes))
			{
				/**
				 * @var PostflightAttribute $attributeInstance
				 */
				$attributeInstance           = $attributes[0]->newInstance();
				$results[$method->getName()] = $attributeInstance->name;
			}
		}

		return $results;
	}

	#[PostflightAttribute(name: "Recompile Gantry5")]
	private function recompileGantry5(): bool
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

	#[PostflightAttribute(name: "Set Site Name in database")]
	private function setSitename(): bool
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

	#[PostflightAttribute(name: "Sync logo in Gantry5")]
	private function syncGantryLogo(): bool
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

	#[PostflightAttribute(name: "Clear admins dashboard")]
	private function clearDashboard(): bool
	{
		$query = $this->db->getQuery(true);

		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_dashboard'))
			->where($this->db->quoteName('user') . ' IN (62,95)');
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	#[PostflightAttribute(name: "Set admin color palette")]
	private function setAdminColorPalette(): bool
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

	#[PostflightAttribute(name: "Rebuild .htaccess file")]
	private function checkHtAccess(): bool
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

	#[PostflightAttribute(name: "Check password fields storage")]
	private function checkPasswordFields(): bool
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

	#[PostflightAttribute(name: "Check Fabrik list detail icon")]
	private function checkFabrikIcon(): bool
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

	#[PostflightAttribute(name: "Check Fabrik templates")]
	private function checkFabrikTemplate(): bool
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

	#[PostflightAttribute(name: "Convert Fabrik date elements to jdate")]
	private function convertDateToJDate(): bool
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

	#[PostflightAttribute(name: "Check account application menu")]
	private function checkAccountApplicationMenu(): bool
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
			}
			else
			{
				$account_menu->published = 1;
				$checked                 = $this->db->updateObject('#__menu', $account_menu, 'id');
			}
		}
		catch (Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			$checked = false;
		}

		return $checked;
	}

	#[PostflightAttribute(name: "Check configuration (cookies, session, frontediting)")]
	private function checkConfig(): bool
	{
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
			$this->db->execute();

			$options['frontediting'] = 0;

			//Prepare cookies config
			$cookie_domain = Factory::getApplication()->get('live_site', '');
			// Remove port if exists
			$port = parse_url($cookie_domain, PHP_URL_PORT);
			if (!empty($port))
			{
				$cookie_domain = str_replace(':' . $port, '', $cookie_domain);
			}
			$is_local = false;
			if (strpos($cookie_domain, 'localhost') !== false || strpos($cookie_domain, '127.') !== false)
			{
				$is_local = true;
			}

			if (!empty($cookie_domain) && !$is_local)
			{
				$cookie_domain = explode('//', $cookie_domain);
				$cookie_domain = $cookie_domain[1];
				if (substr($cookie_domain, -1) == '/')
				{
					$cookie_domain = substr($cookie_domain, 0, -1);
				}

				if (!empty($cookie_domain))
				{
					$options['cookie_domain'] = $cookie_domain;
				}

				$options['cookie_path'] = '/';
			}
			//


			$app = Factory::getApplication();
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

	#[PostflightAttribute(name: "Check SSO availability")]
	private function checkSSOAvailable(): bool
	{
		$checked = true;
		$haveSSO = false;

		// Check if we have external authentication
		$emundusOauth2 = PluginHelper::getPlugin('authentication', 'emundus_oauth2');
		$ldap          = PluginHelper::getPlugin('authentication', 'ldap');
		if (!empty($ldap))
		{
			$haveSSO = true;
		}
		elseif (!empty($emundusOauth2))
		{
			$oauth2Config = json_decode($emundusOauth2->params);

			if (!empty($oauth2Config->configurations))
			{
				foreach ($oauth2Config->configurations as $config)
				{
					if (in_array($config->display_on_login, [1, 3, 4]))
					{
						$haveSSO = true;
						break;
					}
				}
			}
		}

		$query = $this->db->getQuery(true);
		if ($haveSSO)
		{
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
		}
		else
		{
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

	#[PostflightAttribute(name: "Check automated task user")]
	private function checkAutomatedUser(): bool
	{
		$automated_user_id = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 0);

		$query = $this->db->getQuery(true);

		$query->select('id')
			->from('#__users')
			->where('email = ' . $this->db->quote('automatedtask@emundus.fr'));
		if (!empty($automated_user_id))
		{
			$query->orWhere('id = ' . $automated_user_id);
		}
		$this->db->setQuery($query);
		$automated_user_id = $this->db->loadResult();

		require_once(JPATH_SITE . '/components/com_emundus/models/users.php');
		require_once(JPATH_SITE . '/components/com_emundus/helpers/users.php');
		require_once(JPATH_SITE . '/components/com_emundus/helpers/date.php');
		$h_users = new \EmundusHelperUsers;
		$m_users = new \EmundusModelUsers();

		$other_param['firstname']    = 'Task';
		$other_param['lastname']     = 'AUTOMATED';
		$other_param['profile']      = 1000;
		$other_param['em_oprofiles'] = '';
		$other_param['univ_id']      = 0;
		$other_param['em_groups']    = [];
		$other_param['em_campaigns'] = [];
		$other_param['news']         = 0;

		if (empty($automated_user_id))
		{
			$user           = clone(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0));
			$user->name     = 'Task AUTOMATED';
			$user->username = 'automatedtask@emundus.fr';
			$user->email    = 'automatedtask@emundus.fr';

			$password       = $h_users->generateStrongPassword(30);
			$user->password = UserHelper::hashPassword($password);

			$now                 = EmundusHelperDate::getNow();
			$user->registerDate  = $now;
			$user->lastvisitDate = null;
			$user->groups        = array(2);
			$user->block         = 0;
			$user->authProvider  = '';

			$acl_aro_groups = $m_users->getDefaultGroup(1000);
			$user->groups   = $acl_aro_groups;

			$usertype       = $m_users->found_usertype($acl_aro_groups[0]);
			$user->usertype = $usertype;

			$automated_user_id = $m_users->adduser($user, $other_param);
		}
		else
		{
			// Check if exist in emundus users
			$query->clear()
				->select('id')
				->from('#__emundus_users')
				->where('user_id = ' . $automated_user_id);
			$this->db->setQuery($query);
			$emundus_user_id = $this->db->loadResult();

			if (empty($emundus_user_id))
			{
				$m_users->addEmundusUser($automated_user_id, $other_param);
			}

			// Update password
			$query->clear()
				->select('id,password')
				->from('#__users')
				->where('id = ' . $automated_user_id);
			$this->db->setQuery($query);
			$user = $this->db->loadObject();

			$user->password       = $h_users->generateStrongPassword(30);
			$user->password = UserHelper::hashPassword($user->password);

			$this->db->updateObject('#__users', $user, 'id');
		}

		// We update it in case of change
		if (!empty($automated_user_id))
		{
			EmundusHelperUpdate::updateComponentParameter('com_emundus', 'automated_task_user', $automated_user_id);
		}

		return !empty($automated_user_id);
	}

	#[PostflightAttribute(name: "Force Hikashop light mode")]
	private function forceHikashopLightMode(): bool
	{
		$query = $this->db->getQuery(true);

		$query->select('config_namekey')
			->from($this->db->quoteName('#__hikashop_config'))
			->where($this->db->quoteName('config_namekey') . ' LIKE ' . $this->db->quote('dark_mode'));
		$this->db->setQuery($query);
		$dark_mode = $this->db->loadResult();

		if (!empty($dark_mode))
		{
			$query->clear()
				->update($this->db->quoteName('#__hikashop_config'))
				->set($this->db->quoteName('config_value') . ' = 0')
				->where($this->db->quoteName('config_namekey') . ' LIKE ' . $this->db->quote('dark_mode'));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		else
		{
			$query->clear()
				->insert($this->db->quoteName('#__hikashop_config'))
				->columns($this->db->quoteName('config_namekey') . ', ' . $this->db->quoteName('config_value') . ', ' . $this->db->quoteName('config_default'))
				->values($this->db->quote('dark_mode') . ', 0' . ', 0');
			$this->db->setQuery($query);

			return $this->db->execute();
		}
	}

	#[PostflightAttribute(name: "Check session GC scheduler task")]
	private function checkSessionGCScheduler(): bool
	{
		$checked = false;

		// Session GC need to be 6 hours
		$query = $this->db->getQuery(true);

		$query->select('id,execution_rules,cron_rules,params,next_execution')
			->from($this->db->quoteName('#__scheduler_tasks'))
			->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('session.gc'));
		$this->db->setQuery($query);
		$sessionGc = $this->db->loadAssoc();

		if (!empty($sessionGc))
		{
			$params                   = json_decode($sessionGc['params'], true);
			$params['individual_log'] = false;
			$params['log_file']       = '';
			$params['notifications']  = [
				'success_mail'       => 0,
				'failure_mail'       => 0,
				'fatal_failure_mail' => 1,
				'orphan_mail'        => 1
			];
			$sessionGc['params']      = json_encode($params);

			$cronRules               = json_decode($sessionGc['cron_rules'], true);
			$cronRules['type']       = 'interval';
			$cronRules['exp']        = 'PT6H';
			$sessionGc['cron_rules'] = json_encode($cronRules);

			$executionRules                   = json_decode($sessionGc['execution_rules'], true);
			$executionRules['rule-type']      = 'interval-hours';
			$executionRules['interval-hours'] = 6;
			$executionRules['exec-time']      = '12:00';
			$executionRules['exec-day']       = '02';
			$sessionGc['execution_rules']     = json_encode($executionRules);

			$next_execution = new DateTime();
			$next_execution->add(new DateInterval('PT6H'));
			$sessionGc['next_execution'] = $next_execution->format('Y-m-d H:i:s');

			$update  = (object) $sessionGc;
			$checked = $this->db->updateObject('#__scheduler_tasks', $update, 'id');
		}

		return $checked;
	}

	#[PostflightAttribute(name: "Disable Dropfiles editor plugin")]
	private function disableDropfilesEditorPlugin(): bool
	{
		$query = $this->db->getQuery(true);

		$query->clear()
			->update($this->db->quoteName('#__extensions'))
			->set($this->db->quoteName('enabled') . ' = 0')
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('dropfilesbtn'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('editors-xtd'));
		return $this->db->setQuery($query)->execute();
	}

	#[PostflightAttribute(name: "Check applicant history menu")]
	private function checkHistoryMenu(): bool
	{
		$query = $this->db->getQuery(true);

		$query->clear()
			->select('id,params,published')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=application&layout=history'))
			->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('applicantmenu'));
		$this->db->setQuery($query);
		$history_menu = $this->db->loadObject();

		if(!empty($history_menu) && !empty($history_menu->id))
		{
			$params = json_decode($history_menu->params, true);
			$params['menu_show'] = 0;

			$history_menu->params = json_encode($params);
			$history_menu->published = 1;

			return $this->db->updateObject('#__menu', $history_menu, 'id');
		}
		else {
			$modules = [];
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module') . ' LIKE ' . $this->db->quote('mod_emundusflow'));
			$this->db->setQuery($query);
			$emundusflow_module = $this->db->loadResult();
			if(!empty($emundusflow_module))
			{
				$modules[] = $emundusflow_module;
			}

			$datas = [
				'menutype'     => 'applicantmenu',
				'title'        => 'Voir mon dossier',
				'alias'        => 'applicant-history',
				'path'         => 'applicant-history',
				'link'         => 'index.php?option=com_emundus&view=application&layout=history',
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params'       => [
					'menu_show' => 0,
					'tabs' => ['history','forms','attachments']
				]
			];

			return EmundusHelperUpdate::addJoomlaMenu($datas,1,1,'last-child',$modules)['status'];
		}
	}

	#[PostflightAttribute(name: "Secure API access")]
	private function secureAPI(): bool
	{
		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$query->clear()
				->select('id, rgt')
				->from($this->db->quoteName('#__usergroups'))
				->where('title = ' . $this->db->quote('Public'));
			$this->db->setQuery($query);
			$publicGroup = $this->db->loadObject();

			$query->clear()
				->select('id, rules')
				->from($this->db->quoteName('#__viewlevels'))
				->where('title = ' . $this->db->quote('Special'));
			$this->db->setQuery($query);
			$specialViewLevel = $this->db->loadObject();

			// Create API v2 group and view levels if they do not exist
			$query->clear()
				->select('id, rules')
				->from($this->db->quoteName('#__viewlevels'))
				->where($this->db->quoteName('title') . ' = ' . $this->db->quote('API v2'));
			$this->db->setQuery($query);
			$apiV2ViewLevel = $this->db->loadObject();

			if(empty($apiV2ViewLevel->id))
			{
				if(!empty($publicGroup))
				{
					$usergroup = [
						'parent_id' => $publicGroup->id,
						'lft'       => $publicGroup->rgt,
						'rgt'       => $publicGroup->rgt + 1,
						'title'     => 'API v2'
					];
					$usergroup = (object) $usergroup;

					if($tasks[] = $this->db->insertObject('#__usergroups', $usergroup))
					{
						$apiV2GroupId = $this->db->insertid();

						// Create the view level
						$viewLevel = [
							'title' => 'API v2',
							'ordering' => 0,
							'rules' => '[' . $apiV2GroupId . ']'
						];
						$viewLevel = (object) $viewLevel;

						$tasks[] = $this->db->insertObject('#__viewlevels', $viewLevel);
					}

					// Update the lft and rgt values of the parent group
					$publicGroup->rgt += 2;
					$this->db->updateObject('#__usergroups', $publicGroup, 'id');
				}
			}
			else {
				$apiV2GroupId = str_replace(['[', ']'], '', $apiV2ViewLevel->rules);
			}
			//

			// Create API v3 group and view levels if they do not exist
			$query->clear()
				->select('id, rules')
				->from($this->db->quoteName('#__viewlevels'))
				->where($this->db->quoteName('title') . ' = ' . $this->db->quote('API v3'));
			$this->db->setQuery($query);
			$apiV3ViewLevel = $this->db->loadObject();

			if(empty($apiV3ViewLevel->id))
			{
				if(!empty($publicGroup))
				{
					$usergroup = [
						'parent_id' => $publicGroup->id,
						'lft'       => $publicGroup->rgt,
						'rgt'       => $publicGroup->rgt + 1,
						'title'     => 'API v3'
					];
					$usergroup = (object) $usergroup;

					if($tasks[] = $this->db->insertObject('#__usergroups', $usergroup))
					{
						$apiV3GroupId = $this->db->insertid();

						// Create the view level
						$viewLevel = [
							'title' => 'API v3',
							'ordering' => 0,
							'rules' => '[' . $apiV3GroupId . ']'
						];
						$viewLevel = (object) $viewLevel;

						$tasks[] = $this->db->insertObject('#__viewlevels', $viewLevel);
					}

					// Update the lft and rgt values of the parent group
					$publicGroup->rgt += 2;
					$tasks[] = $this->db->updateObject('#__usergroups', $publicGroup, 'id');
				}
			}
			else {
				$apiV3GroupId = str_replace(['[', ']'], '', $apiV3ViewLevel->rules);
			}
			//

			// Disable all webservices except emundus
			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = 0')
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('webservices'))
				->where($this->db->quoteName('element') . ' != ' . $this->db->quote('emundus'));
			$this->db->setQuery($query);
			$tasks[] = $this->db->execute();
			//

			// Only allow api v2 and api v3 view levels to generate a token
			$query->clear()
				->select('extension_id, params, enabled, access')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('user'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('token'));
			$this->db->setQuery($query);
			$tokenPlugin = $this->db->loadObject();

			if(!empty($tokenPlugin->extension_id) && !empty($apiV2GroupId) && !empty($apiV3GroupId))
			{
				$params = json_decode($tokenPlugin->params, true);
				$params['allowedUserGroups'] = [$apiV2GroupId, $apiV3GroupId];

				$tokenPlugin->enabled = 1;
				$tokenPlugin->access = $specialViewLevel->id;
				$tokenPlugin->params = json_encode($params);

				$tasks[] = $this->db->updateObject('#__extensions', $tokenPlugin, 'extension_id');
			}
			//

			// For those 2 view levels, only allow access to the API component
			$query->clear()
				->select('id, rules')
				->from($this->db->quoteName('#__assets'))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('root.1'));
			$this->db->setQuery($query);
			$rootAsset = $this->db->loadObject();

			if(!empty($rootAsset->rules) && !empty($apiV2GroupId) && !empty($apiV3GroupId))
			{
				$rules = json_decode($rootAsset->rules, true);
				$rules['core.login.api'] = [
					$publicGroup->id => 1,
				];
				$rules['core.login.admin'][$apiV2GroupId] = 1;
				$rules['core.login.admin'][$apiV3GroupId] = 1;
				$rootAsset->rules = json_encode($rules);
				$tasks[] = $this->db->updateObject('#__assets', $rootAsset, 'id');
			}
			//

			// Allow api v2 and api v3 groups in special view level
			if(!empty($specialViewLevel->id) && !empty($apiV2GroupId) && !empty($apiV3GroupId))
			{
				$rules = json_decode($specialViewLevel->rules, true);
				if(!in_array($apiV2GroupId, $rules))
				{
					$rules[] = (int) $apiV2GroupId;
				}
				if(!in_array($apiV3GroupId, $rules))
				{
					$rules[] = (int) $apiV3GroupId;
				}
				sort($rules);
				$specialViewLevel->rules = json_encode($rules);
				$tasks[] = $this->db->updateObject('#__viewlevels', $specialViewLevel, 'id');
			}
			//

			// Add com_emundus to action logs extension
			$query->clear()
				->select('extension')
				->from($this->db->quoteName('#__action_logs_extensions'))
				->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_emundus'));
			$this->db->setQuery($query);
			$actionLog = $this->db->loadResult();

			if(empty($actionLog))
			{
				$actionLogInsert = [
					'extension' => 'com_emundus'
				];
				$actionLogInsert = (object) $actionLogInsert;
				$tasks[] = $this->db->insertObject('#__action_logs_extensions', $actionLogInsert);
			}

			$query->clear()
				->select('extension_id, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_actionlogs'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$actionLogsComponent = $this->db->loadObject();

			if(!empty($actionLogsComponent->extension_id))
			{
				$params = json_decode($actionLogsComponent->params, true);
				$params['loggable_extensions'][] = 'com_emundus';
				$params['loggable_api'] = 1;
				$params['loggable_verbs'] = ['GET', 'POST', 'PUT', 'DELETE'];
				$actionLogsComponent->params = json_encode($params);
				$tasks[] = $this->db->updateObject('#__extensions', $actionLogsComponent, 'extension_id');
			}
		}
		catch (\Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			return false;
		}

		return !in_array(false, $tasks);
	}

	#[PostflightAttribute(name: "Secure some articles")]
	private function secureSomeArticles(): bool
	{
		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__viewlevels'))
				->where($this->db->quoteName('title') . ' = ' . $this->db->quote('Coordinator'));
			$this->db->setQuery($query);
			$coordinatorViewLevelId = $this->db->loadResult();

			if(!empty($coordinatorViewLevelId))
			{
				$query->clear()
					->select('id, access')
					->from($this->db->quoteName('#__categories'))
					->where($this->db->quoteName('path') . ' = ' . $this->db->quote('gestion-de-projet'))
					->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_content'));
				$this->db->setQuery($query);
				$category = $this->db->loadObject();

				if (!empty($category) && !empty($category->id))
				{
					$update  = [
						'id'     => $category->id,
						'access' => $coordinatorViewLevelId
					];
					$update  = (object) $update;
					$tasks[] = $this->db->updateObject('#__categories', $update, 'id');
				}
			}
		}
		catch (\Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			return false;
		}

		return !in_array(false, $tasks);
	}

	#[PostflightAttribute(name: "Remove old Helium assets file")]
	private function removeOldAssets(): bool
	{
		$file = JPATH_ROOT . '/templates/g5_helium/custom/config/24/page/assets.yaml';

		if(!file_exists($file))
		{
			return true;
		}

		return unlink($file);
	}

	#[PostflightAttribute(name: "Remove AJAX validation from registration form")]
	private function removeAjaxValidation(): bool
	{
		$removed = true;

		$query  = $this->db->createQuery();

		$query->select('id,params')
			->from($this->db->quoteName('#__fabrik_forms'))
			->where($this->db->quoteName('id') . ' = 307');
		$this->db->setQuery($query);
		$registration_form = $this->db->loadObject();

		if ($registration_form)
		{
			$params                     = json_decode($registration_form->params, true);
			$params['ajax_validations'] = 0;

			$query->clear()
				->update($this->db->quoteName('#__fabrik_forms'))
				->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($registration_form->id));
			$this->db->setQuery($query);
			$removed = $this->db->execute();
		}

		return $removed;
	}

	#[PostflightAttribute(name: "Check profile menu translation")]
	private function checkProfileMenuTranslation(): bool
	{
		$checked = true;
		$query   = $this->db->getQuery(true);

		try
		{
			$query->select('form_id')
				->from($this->db->quoteName('#__emundus_setup_formlist'))
				->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('profile'));
			$this->db->setQuery($query);
			$form_id = $this->db->loadResult();

			if (!empty($form_id))
			{
				$query->clear()
					->select('id,params')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_fabrik&view=form&formid=' . $form_id));
				$this->db->setQuery($query);
				$menu = $this->db->loadObject();

				if (!empty($menu->id))
				{
					$query->clear()
						->update($this->db->quoteName('#__falang_content'))
						->set($this->db->quoteName('value') . ' = ' . $this->db->quote('index.php?option=com_fabrik&view=form&formid=' . $form_id))
						->where($this->db->quoteName('reference_table') . ' = ' . $this->db->quote('menu'))
						->where($this->db->quoteName('reference_field') . ' = ' . $this->db->quote('link'))
						->where($this->db->quoteName('reference_id') . ' = ' . $this->db->quote($menu->id));
					$this->db->setQuery($query);
					$this->db->execute();

					$query->clear()
						->update($this->db->quoteName('#__falang_content'))
						->set($this->db->quoteName('value') . ' = ' . $this->db->quote($menu->params))
						->where($this->db->quoteName('reference_table') . ' = ' . $this->db->quote('menu'))
						->where($this->db->quoteName('reference_field') . ' = ' . $this->db->quote('params'))
						->where($this->db->quoteName('reference_id') . ' = ' . $this->db->quote($menu->id));
					$this->db->setQuery($query);
					$checked = $this->db->execute();
				}
			}
		}
		catch (\Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			$checked = false;
		}

		return $checked;
	}

	#[PostflightAttribute(name: "Check Emundus event handler plugin")]
	private function checkEmundusEventHandler(): bool
	{
		$checked = true;
		$query   = $this->db->getQuery(true);

		try
		{
			$query->select('extension_id')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('custom_event_handler'));
			$this->db->setQuery($query);
			$custom_event_handler = $this->db->loadResult();

			if (empty($custom_event_handler))
			{
				$checked = EmundusHelperUpdate::installExtension('PLG_EMUNDUS_CUSTOM_EVENT_HANDLER_TITLE', 'custom_event_handler', null, 'plugin', 1, 'emundus');
			}
			else
			{
				$query->clear()
					->update($this->db->quoteName('#__extensions'))
					->set($this->db->quoteName('enabled') . ' = 1')
					->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($custom_event_handler));
				$this->db->setQuery($query);
				$checked = $this->db->execute();
			}
		}
		catch (\Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			$checked = false;
		}

		return $checked;
	}

	#[PostflightAttribute(name: "Check Emundus ZIP plugin")]
	private function checkZipPlugin(): bool
	{
		$checked = true;
		$query   = $this->db->getQuery(true);

		try
		{
			$query->select('extension_id')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('send_file_archive'));
			$this->db->setQuery($query);
			$send_file_archive = $this->db->loadResult();

			if (empty($send_file_archive))
			{
				$checked = EmundusHelperUpdate::installExtension('Emundus - Send ZIP file to user.', 'send_file_archive', '{"name":"Emundus - Send ZIP file to user.","type":"plugin","creationDate":"19 July 2019","author":"eMundus","copyright":"(C) 2010-2019 EMUNDUS SOFTWARE. All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"https:\/\/www.emundus.fr","version":"6.9.10","description":"This plugin sends a ZIP of the file when it is changed to a certain status or when it is deleted.","group":"","filename":"send_file_archive"}', 'plugin', 1, 'emundus', '{"delete_email":"delete_file"}');
			}
			else
			{
				$query->clear()
					->update($this->db->quoteName('#__extensions'))
					->set($this->db->quoteName('enabled') . ' = 1')
					->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($send_file_archive));
				$this->db->setQuery($query);
				$checked = $this->db->execute();
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_emails'))
				->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('delete_file'));
			$this->db->setQuery($query);
			$delete_file_email = $this->db->loadResult();

			if (empty($delete_file_email))
			{
				$msg = '<p>Dear [NAME],</p>
<p>Your application file <strong><em>[FNUM]</em></strong> has been deleted.</p>
<p>A zip file containing the data deleted is attached to this email.</p>
<hr />
<p>Bonjour [NAME],</p>
<p>Votre dossier de candidature <strong><em>[FNUM]</em></strong> vient d\'&ecirc;tre supprim&eacute;.</p>
<p>Ci-joint, une archive des informations qui ont &eacute;t&eacute; supprim&eacute;es.</p>';
				$insert = (object) [
					'lbl'      => 'delete_file',
					'subject'  => 'Application file deleted / Dossier supprimé',
					'message'  => $msg,
					'type'     => 1,
					'category' => 'Système',
				];
				$checked = $this->db->insertObject('#__emundus_setup_emails', $insert);
			}
		}
		catch (\Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			$checked = false;
		}

		return $checked;
	}

	#[PostflightAttribute(name: "Check Emundus Dropfiles category setup plugin")]
	private function checkDropfilesPlugin(): bool
	{
		$query   = $this->db->getQuery(true);

		$query->select('extension_id')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' LIKE ' . $this->db->quote('setup_category'))
			->where($this->db->quoteName('folder') . ' LIKE ' . $this->db->quote('emundus'));
		$this->db->setQuery($query);
		$emundus_dropfiles_plugin = $this->db->loadResult();

		if (empty($emundus_dropfiles_plugin))
		{
			return EmundusHelperUpdate::installExtension('Emundus - Create new dropfiles category', 'setup_category', '{"name":"Emundus - Create new dropfiles category","type":"plugin","creationDate":"July 2020","author":"eMundus","copyright":"(C) 2010-2019 EMUNDUS SOFTWARE. All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"https:\/\/www.emundus.fr","version":"6.9.10","description":"PLG_EMUNDUS_SETUP_CATEGORY_DESCRIPTION","group":"","filename":"setup_category"}', 'plugin', 1, 'emundus');
		}
		else
		{
			$query->clear()
				->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = 1')
				->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($emundus_dropfiles_plugin));
			$this->db->setQuery($query);
			return $this->db->execute();
		}
	}

	#[PostflightAttribute(name: "Check SecurityCheckPro configuration")]
	private function checkSCPConfiguration(): bool
	{
		$checked = true;
		$query   = $this->db->getQuery(true);

		$query->clear()
			->select('storage_key, storage_value')
			->from($this->db->quoteName('#__securitycheckpro_storage'))
			->where($this->db->quoteName('storage_key') . ' LIKE ' . $this->db->quote('pro_plugin'));
		$this->db->setQuery($query);
		$scp_plugin = $this->db->loadObject();

		if (!empty($scp_plugin) && !empty($scp_plugin->storage_value))
		{
			$storage_value = json_decode($scp_plugin->storage_value, true);

			// Blacklist
			$storage_value['dynamic_blacklist']         = 1;
			$storage_value['dynamic_blacklist_counter'] = 5;
			$storage_value['dynamic_blacklist_time']    = 300;
			$storage_value['blacklist_email']           = 0;

			// Strict mode
			$storage_value['mode'] = 0;

			// Logs
			$storage_value['logs_attacks']              = 1;
			$storage_value['scp_delete_period']         = 90;
			$storage_value['log_limits_per_ip_and_day'] = 5;
			$storage_value['add_access_attempts_logs']  = 1;

			// Redirect
			$storage_value['redirect_after_attack'] = 1;
			$storage_value['redirect_options']      = 1;
			$storage_value['custom_code']           = '<h1 style="text-align: center;">The application\'s firewall has been triggered by your use of the platform.<br />You no longer have access to the platform.<br />Please contact the platform manager so that he can unblock your account.</h1><hr /><h1 style="text-align: center;">Le pare-feu de l\'application vient de se déclencher suite à votre utilisation de la plateforme.<br />Vous n\'avez plus accès à la plateforme.<br />Merci de prendre contact avec le gestionnaire de cette plateforme afin qu\'il débloque votre compte.</h1>';

			// Second level
			$storage_value['second_level']             = 0;
			$storage_value['second_level_redirect']    = 1;
			$storage_value['second_level_limit_words'] = 5;
			$storage_value['second_level_words']       = base64_encode('drop,update,set,admin,select,password,concat,login,load_file,ascii,char,union,group by,order by,insert,values,where,substring,benchmark,md5,sha1,schema,row_count,compress,encode,information_schema,script,javascript,img,src,body,iframe,frame,$_POST,eval,$_REQUEST,base64_decode,gzinflate,gzuncompress,gzinflate,strtrexec,passthru,shell_exec,createElement');

			$storage_value['email_active'] = 0;
			$storage_value['email_from_domain'] = 'security@emundus.fr';
			$storage_value['email_from_name'] = 'eMundus Security';

			// Exceptions
			$storage_value['exclude_exceptions_if_vulnerable'] = 1;
			$storage_value['check_header_referer']             = 1;
			$storage_value['check_base_64']                    = 0;
			$storage_value['base64_exceptions']                = 'com_hikashop,com_emundus,com_fabrik,com_users,com_content';
			$storage_value['strip_all_tags']                   = 0;
			$storage_value['tags_to_filter'] = 'applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,ilayer,layer,meta,object,script,xml';
			$storage_value['strip_tags_exceptions'] = 'com_jdownloads,com_hikashop,com_emundus,com_fabrik,com_gantry5,com_users,com_content,com_languages';
			$storage_value['duplicate_backslashes_exceptions'] = 'com_emundus,com_fabrik,com_content,com_languages,com_users,com_login,com_hikashop,com_menus';
			$storage_value['sql_pattern_exceptions'] = 'com_emundus,com_fabrik';
			$storage_value['line_comments_exceptions'] = 'com_emundus,com_fabrik,com_content,com_users,com_login';
			$storage_value['using_integers_exceptions'] = 'com_jce,com_fabrik,com_users,com_login,com_content';
			$storage_value['escape_strings_exceptions'] = 'com_jce,com_fabrik,com_emundus,com_content,com_users,com_login,com_languages,com_hikashop,com_menus';
			$storage_value['lfi_exceptions'] = 'com_emundus,com_fabrik,com_content,com_users';
			$storage_value['second_level_exceptions'] = '';

			// Session
			$storage_value['session_protection_active']               = 0;
			$storage_value['session_hijack_protection']               = 0;
			$storage_value['session_hijack_protection_what_to_check'] = 2;
			$storage_value['session_protection_groups']               = ["11", "3", "5", "2", "10", "1"];
			$storage_value['track_failed_logins']                     = 1;
			$storage_value['logins_to_monitorize']                    = 0;
			$storage_value['write_log']                               = 1;
			$storage_value['actions_failed_login']                    = 1;
			$storage_value['email_on_admin_login']                    = 0;
			$storage_value['forbid_admin_frontend_login']             = 0;
			$storage_value['forbid_new_admins']                       = 0;

			// Upload scanner
			$storage_value['upload_scanner_enabled']    = 1;
			$storage_value['check_multiple_extensions'] = 1;
			$storage_value['mimetypes_blacklist']       = 'application/x-dosexec,application/x-msdownload ,text/x-php,application/x-php,application/x-httpd-php,application/x-httpd-php-source,application/javascript';
			$storage_value['extensions_blacklist']      = 'php,js,exe';
			$storage_value['delete_files']              = 1;
			$storage_value['actions_upload_scanner']    = 1;

			$scp_plugin->storage_value = json_encode($storage_value);
			$checked = $this->db->updateObject('#__securitycheckpro_storage', $scp_plugin, 'storage_key');
		}

		return $checked;
	}

	#[PostflightAttribute(name: "Check favicon")]
	private function checkFavicon(): bool
	{
		$current_favicon = EmundusHelperUpdate::getYamlVariable('favicon', JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml');
		$current_favicon = str_replace('gantry-media:/', 'images', $current_favicon);

		if (!file_exists($current_favicon)) {
			$current_favicon = 'gantry-media://custom/default_favicon.ico';

			EmundusHelperUpdate::updateYamlVariable('favicon', $current_favicon, JPATH_ROOT . '/templates/g5_helium/custom/config/default/page/assets.yaml');
		}

		return true;
	}

	#[PostflightAttribute(name: "Check registration form autologin")]
	private function checkRegistrationFormAutologin(): bool
	{
		$checked = true;
		$query   = $this->db->getQuery(true);

		$query->clear()
			->select('id, params')
			->from($this->db->quoteName('#__fabrik_forms'))
			->where($this->db->quoteName('label') . ' LIKE ' . $this->db->quote('FORM_REGISTRATION'));
		$this->db->setQuery($query);
		$registration_form = $this->db->loadObject();

		if (!empty($registration_form) && !empty($registration_form->params)) {
			$params                     = json_decode($registration_form->params, true);
			$params['juser_auto_login'] = ["1"];

			$registration_form->params = json_encode($params);
			$checked = $this->db->updateObject('#__fabrik_forms', $registration_form, 'id');
		}

		return $checked;
	}

	#[PostflightAttribute(name: "Check page class for applicant forms")]
	private function checkPageClass(): bool
	{
		$checks = [];

		$query = $this->db->getQuery(true);

		$query->clear()
			->select('menutype')
			->from($this->db->quoteName('#__emundus_setup_profiles'))
			->where($this->db->quoteName('published') . ' = 1')
			->where($this->db->quoteName('status') . ' = ' . $this->db->quote(1));
		$this->db->setQuery($query);
		$menutypes = $this->db->loadColumn();

		foreach ($menutypes as $key => $menutype)
		{
			$menutypes[$key] = $this->db->quote($menutype);
		}

		$query->clear()
			->select('id,params')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' IN (' . implode(',', $menutypes) . ')')
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_fabrik&view=form&formid=%'));
		$this->db->setQuery($query);
		$menus = $this->db->loadObjectList();

		foreach ($menus as $menu)
		{
			$params = json_decode($menu->params, true);

			if ($params['pageclass_sfx'] == '')
			{
				$params['pageclass_sfx'] = 'applicant-form';
				$menu->params = json_encode($params);

				$checks[] = $this->db->updateObject('#__menu', $menu, 'id');
			}
		}

		return !in_array(false, $checks);
	}

	#[PostflightAttribute(name: "Disable Fabrik debug")]
	private function checkFabrikDebug(): bool
	{
		$checked = false;

		$query   = $this->db->getQuery(true);

		try
		{
			$query->select('extension_id, params')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_fabrik'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'));
			$this->db->setQuery($query);
			$fabrikComponent = $this->db->loadObject();

			if(!empty($fabrikComponent) && !empty($fabrikComponent->params))
			{
				$params = json_decode($fabrikComponent->params, true);
				$params['use_fabrikdebug'] = 0;
				$params['burst_js'] = 0;
				$fabrikComponent->params = json_encode($params);

				$checked = $this->db->updateObject('#__extensions', $fabrikComponent, 'extension_id');
			}
		}
		catch (Exception $e)
		{
			EmundusHelperUpdate::displayMessage($e->getMessage(), 'error');
			$checked = false;
		}

		return $checked;
	}
}
