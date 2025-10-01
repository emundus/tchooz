<?php

/**
 * @ package Track Actions
 * @ author Jose A. Luque
 * @ Copyright (c) 2011 - Jose A. Luque
 *
 * @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */
 
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
 
class Pkg_SecuritycheckproInstallerScript
{
	
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
	}
	
		
}