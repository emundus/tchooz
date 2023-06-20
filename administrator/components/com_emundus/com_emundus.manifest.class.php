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

class Com_EmundusInstallerScript
{
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
        $succeed = [];

        require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
        $cache_version = $this->manifest_cache->version;

        # Check first run
        $firstrun = false;
        $regex    = '/^6\.[0-9]*/m';
        preg_match_all($regex, $cache_version, $matches, PREG_SET_ORDER, 0);
        if (!empty($matches)) {
            $cache_version = (string) $parent->manifest->version;
            $firstrun      = true;
        }

        if ($this->manifest_cache) {
            # First run condition
            if (version_compare($cache_version, '2.0.0', '<=') || $firstrun) {
                EmundusHelperUpdate::insertTranslationsTag('JLOGIN_DESC', 'Pour accéder à votre espace personnel');
                EmundusHelperUpdate::insertTranslationsTag('JLOGIN_DESC', 'To access your personal space', 'override', null, null, null, 'en-GB');
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
    }
}
