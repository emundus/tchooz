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

use Joomla\CMS\Factory;

class Com_EmundusInstallerScript
{
	private $db;

	protected $manifest_cache;
	protected $schema_version;
	protected EmundusHelperUpdate $h_update;

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
		$this->h_update = new EmundusHelperUpdate();
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

		$query = $this->db->getQuery(true);

        if ($this->manifest_cache) {
            if (version_compare($cache_version, '2.0.0', '<=') || $firstrun) {
				$disabled = EmundusHelperUpdate::disableEmundusPlugins('webauthn');
				if($disabled) {
					EmundusHelperUpdate::displayMessage('Le plugin WebAuthn a été désactivé.', 'success');
				}

				$query->update($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('eval') . ' = 0')
					->set($this->db->quoteName('default') . ' = ' . $this->db->quote(''))
					->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('fnum'))
					->where($this->db->quoteName('eval') . ' = 1');
				$this->db->setQuery($query);
				if($this->db->execute()) {
					EmundusHelperUpdate::displayMessage('Les valeurs par défaut des champs fnums ont été retirées, ces valeurs sont désormais pré-remplis via le plugin emundus_events', 'success');
				}
				else {
					EmundusHelperUpdate::displayMessage('Erreur lors de la modification des champs fnums', 'error');
					$succeed = false;
				}

	            $column_added = EmundusHelperUpdate::addColumn('jos_emundus_setup_attachments', 'max_filesize', 'DOUBLE(6,2)');
				if($column_added['status']) {
					EmundusHelperUpdate::displayMessage('La colonne max_filesize a été ajoutée à la table jos_emundus_setup_attachments', 'success');
				}
				else {
					EmundusHelperUpdate::displayMessage('Erreur lors de l\'ajout de la colonne max_filesize à la table jos_emundus_setup_attachments', 'error');
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

	    $query->select('custom_data')
		    ->from($db->quoteName('#__extensions'))
		    ->where($db->quoteName('element') . ' LIKE ' . $db->quote('com_emundus'));
	    $db->setQuery($query);
	    $custom_data = $db->loadResult();

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
		    ->update($db->quoteName('#__extensions'))
		    ->set($db->quoteName('custom_data') . ' = ' . $db->quote(json_encode($custom_data)))
		    ->where($db->quoteName('element') . ' LIKE ' . $db->quote('com_emundus'));
	    $db->setQuery($query);

		if(!$db->execute()) {
			return false;
		}

	    // Insert new translations in overrides files
	    EmundusHelperUpdate::languageBaseToFile();

	    // Recompile Gantry5 css at each update
	    EmundusHelperUpdate::recompileGantry5();

	    // Clear Joomla Cache
	    EmundusHelperUpdate::clearJoomlaCache();

		return true;
    }
}
