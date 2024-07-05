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
use scripts\Release2_0_0Installer;

class Com_EmundusInstallerScript
{
	private $db;

	protected $manifest_cache;
	protected $schema_version;

	public function __construct()
	{
		// Get component manifest cache
		$this->db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $this->db->getQuery(true);

		$query->select('extension_id, manifest_cache')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_emundus'));
		$this->db->setQuery($query);
		$extension = $this->db->loadObject();
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
     * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
     *                         or install (standard install)
     *                         or update (update)
     * @param   object $parent installer object
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
     * @param   object $parent installer object
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
     * @param   object $parent installer object
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
        if (!empty($matches)) {
            $cache_version = (string) $parent->manifest->version;
            $firstrun      = true;
        }

		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/release.php';

		$releases_path = JPATH_ADMINISTRATOR . '/components/com_emundus/scripts/releases/';

		$query = $this->db->getQuery(true);

        if ($this->manifest_cache) {
            if (version_compare($cache_version, '2.0.0', '<=') || $firstrun) {
	            EmundusHelperUpdate::displayMessage('Installation de la version 2.0.0...');

	            require_once $releases_path . '2_0_0.php';

	            $release_installer = new Release2_0_0Installer();
	            $release_installed = $release_installer->install();
	            if($release_installed['status']) {
					EmundusHelperUpdate::displayMessage('Installation de la version 2.0.0 réussi.', 'success');
	            }
	            else {
		            EmundusHelperUpdate::displayMessage($release_installed['message'], 'error');
		            $succeed = false;
	            }
            }
        }

        return $succeed;
    }

    /**
     * Run when the component is uninstalled.
     *
     * @param   object $parent installer object
     *
     * @return  void
     */
    public function uninstall($parent)
    {
    }

    /**
     * Run after installation or upgrade run
     *
     * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
     *                         or install (standard install)
     *                         or update (update)
     * @param   object $parent installer object
     *
     * @return  bool
     */
    public function postflight($type, $parent)
    {

	    $db    = Factory::getContainer()->get('DatabaseDriver');
	    $query = $db->getQuery(true);

		if(!$this->setSitename()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour du nom du site dans la configuration de eMundus.', 'error');
		}

		if(!$this->syncGantryLogo()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la synchronisation du logo dans la configuration de Gantry5.', 'error');
		}

	    if(!EmundusHelperUpdate::languageBaseToFile()['status']) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour des fichiers de langue.', 'error');
	    }

	    if(!EmundusHelperUpdate::recompileGantry5()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la recompilation de Gantry5.', 'error');
	    }

	    if(!EmundusHelperUpdate::clearJoomlaCache()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la suppression du cache Joomla.', 'error');
	    }

	    if(!$this->clearDashboard()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la suppression des tableaux de bord par défaut.', 'error');
	    }

	    if(!EmundusHelperUpdate::checkHealth()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification de la santé de l\'installation.', 'error');
	    }

	    if(!EmundusHelperUpdate::checkPageClass()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification des classes de pages.', 'error');
	    }

		if(!$this->setAdminColorPalette()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la mise à jour de la palette de couleurs de l\'administration.', 'error');
		}

		if(!$this->checkHtAccess()) {
			EmundusHelperUpdate::displayMessage('Erreur lors de la vérification du fichier .htaccess.', 'error');
		}

	    if(!$this->checkPasswordFields()) {
		    EmundusHelperUpdate::displayMessage('Erreur lors de la vérification du stockage des champs mot de passe.', 'error');
	    }

		return true;
    }

	private function setSitename() {
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
		{}

		return $updated;
	}

	private function syncGantryLogo() {
		$synced = false;

		$query = $this->db->getQuery(true);

		if(file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml')) {
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

			if (preg_match($pattern, $tab[1])) {
				$tab[1] = parse_url($tab[1], PHP_URL_PATH);
			}

			if (!empty($tab[1])) {
				$logo = str_replace('images/', 'gantry-media://', $tab[1]);

				$synced = EmundusHelperUpdate::updateYamlVariable('image', $logo, JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml');
			} elseif(file_exists($logo)) {
				$logo = str_replace('images/', 'gantry-media://', $tab[1]);

				$synced = EmundusHelperUpdate::updateYamlVariable('image', $logo, JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml');
			}
		} else {
			$synced = true;
		}

		return $synced;
	}

	private function clearDashboard() {
		$query = $this->db->getQuery(true);

		$query->clear()
			->delete($this->db->quoteName('#__emundus_setup_dashboard'))
			->where($this->db->quoteName('user') . ' IN (62,95)');
		$this->db->setQuery($query);
		return $this->db->execute();
	}

	private function setAdminColorPalette() {
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

			if(!empty($atum_template->id)) {
				$params = json_decode($atum_template->params, true);

				$params['bg-light'] = '#e4e6eb';

				$update = [
					'id' => $atum_template->id,
					'params' => json_encode($params)
				];
				$update = (object) $update;
				$colors_updated = $this->db->updateObject('#__template_styles', $update, 'id');
			}
		}
		catch (Exception $e)
		{}

		return $colors_updated;
	}

	private function checkHtAccess()
	{
		$current_htaccess = file_get_contents(JPATH_ROOT . '/.htaccess');

		// First we backup current htaccess file
		copy(JPATH_ROOT . '/.htaccess', JPATH_ROOT . '/.htaccess.bak');

		$tchooz_base_rules = file_get_contents(JPATH_ROOT . '/.htaccess.tchooz.base.rules.txt');
		$joomla_rules = file_get_contents(JPATH_ROOT . '/htaccess.txt');
		$tchooz_custom_rules = file_get_contents(JPATH_ROOT . '/.htaccess.tchooz.custom.rules.txt');

		// First run we empty current htaccess file
		if(!strpos($current_htaccess,'# Start of Tchooz .htaccess file (BASE)') && !strpos($current_htaccess,'# Start of Tchooz .htaccess file (CUSTOM)'))
		{
			$current_htaccess = '';
			$current_htaccess .= $joomla_rules;
			$current_htaccess .= PHP_EOL.PHP_EOL;
			$current_htaccess .= $tchooz_base_rules;
			$current_htaccess .= PHP_EOL;
			$current_htaccess .= $tchooz_custom_rules;

			$checked = file_put_contents(JPATH_ROOT . '/.htaccess', $current_htaccess);
		} else {
			// Replace Joomla rules
			$cur_htaccess_joomla_rules = strstr($current_htaccess, '# Start of Tchooz .htaccess file (BASE)', true);
			$current_htaccess = str_replace($cur_htaccess_joomla_rules, $joomla_rules.PHP_EOL, $current_htaccess);
			//

			// Replace base rules
			$tchooz_base_starts = strpos($tchooz_base_rules, "# Start of Tchooz .htaccess file (BASE)") + strlen("# Start of Tchooz .htaccess file (BASE)");
			$tchooz_base_ends = strpos($tchooz_base_rules, "# End of Tchooz .htaccess file (BASE)", $tchooz_base_starts);
			$tchooz_base_block = substr($tchooz_base_rules, $tchooz_base_starts, $tchooz_base_ends - $tchooz_base_starts);

			$cur_htaccess_base_starts = strpos($current_htaccess, "# Start of Tchooz .htaccess file (BASE)") + strlen("# Start of Tchooz .htaccess file (BASE)");
			$cur_htaccess_base_ends = strpos($current_htaccess, "# End of Tchooz .htaccess file (BASE)", $cur_htaccess_base_starts);
			$cur_htaccess_base_block = substr($current_htaccess, $cur_htaccess_base_starts, $cur_htaccess_base_ends - $cur_htaccess_base_starts);

			$current_htaccess = str_replace($cur_htaccess_base_block, $tchooz_base_block, $current_htaccess);
			//

			// Check if Custom rules are present
			if (!strpos($current_htaccess,'# Start of Tchooz .htaccess file (CUSTOM)')) {
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

		try {
			$query->clear()
				->select('id,params')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where('json_valid(`params`)')
				->where('json_extract(`params`, "$.password") = "1"');
			$this->db->setQuery($query);
			$password_elements = $this->db->loadObjectList();

			foreach ($password_elements as $password_element) {
				$params = json_decode($password_element->params, true);
				$params['store_in_db'] = "0";

				$query->clear()
					->update($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($params)))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($password_element->id));
				$this->db->setQuery($query);
				$checked = $this->db->execute();
			}
		}
		catch (Exception $e) {
			$checked = false;
		}

		return $checked;
	}
}
