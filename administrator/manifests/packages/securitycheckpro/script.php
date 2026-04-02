<?php

/**
 * @ package Track Actions
 * @ author Jose A. Luque
 * @ Copyright (c) 2011 - Jose A. Luque
 *
 * @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */
 
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
 
class Pkg_SecuritycheckproInstallerScript
{
	
	/**
     * Se ejecuta antes de install/update/uninstall del paquete
     *
     * @param  string  $type   install|update|uninstall|discover_install
     * @param  object  $parent InstallerAdapter
     */
    public function preflight(string $type, $parent): bool
    {
        if ($type === 'uninstall') {
            // Bandera de "desinstalación del paquete" para permitir
            // que los sub-elementos se desinstalen sin bloquearse.
            if (!\defined('SCP_PKG_UNINSTALLING')) {
                \define('SCP_PKG_UNINSTALLING', true);
            }
        }
		
		// Only allow to install on PHP 8.1.0 or later
        if (!version_compare(PHP_VERSION, '8.1.0', 'ge')) {        
            Factory::getApplication()->enqueueMessage('Securitycheck Pro requires, at least, PHP 8.1.0', 'error');
            return false;
        }  else if (version_compare(JVERSION, '5.0.0', 'lt')) {
            // Only allow to install on Joomla! 5.0.0 or later
            Factory::getApplication()->enqueueMessage("This version only works in Joomla! 5 or higher", 'error');
            return false;
        }

        return true;
    }

    public function uninstall($parent): void
    {
        // Mensaje amable para el usuario
        Factory::getApplication()->enqueueMessage('The Securitycheck Pro Package has been uninstalled correctly.');
    }
	
	 /**
     * Runs after install, update or discover_update
     *
     * @param string     $type   install, update or discover_update
     * @param Installer $parent 
	 *
	 * @return  boolean  True on success
     *
     */
	public function postflight($type, $parent)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
                 
        // Enable and configure module
        $query = "UPDATE #__modules SET position='icon', ordering = '1', published = '1' WHERE module='mod_scpadmin_quickicons'";
        $db->setQuery($query);
        $db->execute();
               
		        
        // Update the URL inspector plugin ordering; it must be published the last
        $query = "UPDATE #__extensions SET ordering = '-100' WHERE name='System - url inspector'";
        $db->setQuery($query);
        $db->execute();
        
        // Check if url plugin is enabled
        $query = "SELECT enabled from #__extensions WHERE name='System - url inspector'";
        $db->setQuery($query);
        $url_plugin_enabled = $db->loadResult();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tableExtensions = $db->quoteName("#__extensions");
        $columnElement   = $db->quoteName("element");
        $columnType      = $db->quoteName("type");
        $columnEnabled   = $db->quoteName("enabled");
            
        // Enable Securitycheck Pro plugin
        $db->setQuery(
            "UPDATE 
				$tableExtensions
			SET
				$columnEnabled=1
			WHERE
				$columnElement='securitycheckpro'
			AND
				$columnType='plugin'"
        );

        $db->execute();        
                     
                
        // Enable Securitycheck Pro Cron Task plugin
        $db->setQuery(
            "UPDATE 
			$tableExtensions
				SET
				$columnEnabled=1
			WHERE
				$columnElement='securitycheckprocron'
			AND
				$columnType='plugin'"
        );

        $db->execute();
		
		// Enable Securitycheck Pro Task Checker
        $db->setQuery(
            "UPDATE 
				$tableExtensions
			SET
				$columnEnabled=1
			WHERE
				$columnElement='securitycheckpro_task_checker'
			AND
				$columnType='plugin'"
        );

        $db->execute();   
		
		return true;
	}	
		
}