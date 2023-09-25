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
	protected $manifest_cache;
	protected $schema_version;
	protected EmundusHelperUpdate $h_update;

	public function __construct()
	{
		// Get component manifest cache
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('extension_id, manifest_cache')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('element') . ' = ' . $db->quote('com_emundus'));
		$db->setQuery($query);
		$extension = $db->loadObject();
		$this->manifest_cache = json_decode($extension->manifest_cache);

		$query->clear()
			->select('version_id')
			->from($db->quoteName('#__schemas'))
			->where($db->quoteName('extension_id') . ' = ' . $db->quote($extension->extension_id));
		$db->setQuery($query);
		$this->schema_version = $db->loadResult();

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

        require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
        $cache_version = $this->manifest_cache->version;

        $firstrun = false;
        $regex    = '/^6\.[0-9]*/m';
        preg_match_all($regex, $cache_version, $matches, PREG_SET_ORDER, 0);
        if (!empty($matches)) {
            $cache_version = (string) $parent->manifest->version;
            $firstrun      = true;
        }

        if ($this->manifest_cache) {
            if (version_compare($cache_version, '2.0.0', '<=') || $firstrun) {
				// ...
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
