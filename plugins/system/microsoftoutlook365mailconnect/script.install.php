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

use Joomla\CMS\Factory;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemMicrosoftoutlook365mailconnectInstallerScript extends PlgSystemMicrosoftoutlook365mailconnectInstallerScriptHelper
{
	public $name           = 'Microsoft/Outlook 365 Mail Connect';
	public $alias          = 'microsoftoutlook365mailconnect';
	public $extension_type = 'plugin';
	public $plugin_folder  = 'system';

	/**
     * Method to run after an install/update/uninstall method
     * @return void
     */
    public function postflight($type, $parent) 
    {
        parent::postflight($type, $parent);

        if ($type == 'install' || $type == 'update')
        {
            $this->setPluginOrder();
        }
    }

    /**
     * Set plugin to the last ordering position
     * @return void
     */
    private function setPluginOrder()
    {
        $db = Factory::getDbo();

        // Get the highest ordering number from system plugins
        $query = $db->getQuery(true)
            ->select('MAX(' . $db->quoteName('ordering') . ')')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $db->setQuery($query);
        $maxOrdering = (int) $db->loadResult();

        // Set new ordering to be one more than the highest
        $newOrdering = $maxOrdering + 1;

        // Update the plugin ordering
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('ordering') . ' = ' . $newOrdering)
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('element') . ' = ' . $db->quote($this->alias))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($this->plugin_folder));
        $db->setQuery($query);
        $db->execute();
    }
}