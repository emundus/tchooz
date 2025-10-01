<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

/**
 * Script file of Securitycheck Pro component
 */
class com_SecuritycheckproInstallerScript
{
    /**
     * The extension version we are updating from
     *
     * @var    string
     * @since  3.7
     */
    protected $fromVersion = null;
	
	// Defaul values to create the scheduled task
	protected $exec_time = '02:00';
	protected $old_task_set = 'integrity';
	protected $cachetimeout = 1;
	
	/**
     * 
     *
     * @var array Obsolete files and folders to remove after new UI  
     */
    private $ObsoleteFilesAndFolders = array
    (
		'files'    => array
		(   
		// Remove old php files
		'administrator/components/com_securitycheckpro/helpers/common.php',
		'administrator/components/com_securitycheckpro/helpers/controlcenter.php',
		'administrator/components/com_securitycheckpro/helpers/cpanel.php',
		'administrator/components/com_securitycheckpro/helpers/cron.php',
		'administrator/components/com_securitycheckpro/helpers/databaseupdates.php',
		'administrator/components/com_securitycheckpro/helpers/dbcheck.php',
		'administrator/components/com_securitycheckpro/helpers/end.php',
		'administrator/components/com_securitycheckpro/helpers/fileintegrity.php',
		'administrator/components/com_securitycheckpro/helpers/filemanager.php',
		'administrator/components/com_securitycheckpro/helpers/ip.php',
		'administrator/components/com_securitycheckpro/helpers/firewallconfig.php',
		'administrator/components/com_securitycheckpro/helpers/j3_firewallconfig.php',
		'administrator/components/com_securitycheckpro/helpers/logs.php',
		'administrator/components/com_securitycheckpro/helpers/malwarescan.php',
		'administrator/components/com_securitycheckpro/helpers/onlinechecks.php',
		'administrator/components/com_securitycheckpro/helpers/protection.php',
		'administrator/components/com_securitycheckpro/helpers/rules.php',
		'administrator/components/com_securitycheckpro/helpers/ruleslog.php',
		'administrator/components/com_securitycheckpro/helpers/securitycheckpros.php',
		'administrator/components/com_securitycheckpro/helpers/sysinfo.php',
		'administrator/components/com_securitycheckpro/helpers/trackactions_logs.php',
		'administrator/components/com_securitycheckpro/helpers/upload.php',
		// Remove old image files
		'media/com_securitycheckpro/images/compat_icon_1_6.png',
		'media/com_securitycheckpro/images/compat_icon_1_7.png',
		'media/com_securitycheckpro/images/compat_icon_2_5.png',
		'media/com_securitycheckpro/images/compat_icon_3_x.png',
		'media/com_securitycheckpro/images/glyphicons-halflings.png',
		'media/com_securitycheckpro/images/glyphicons-halflings-white.png',
		'media/com_securitycheckpro/images/loading.gif',
		'media/com_securitycheckpro/images/opa-icons-black16.png',
		'media/com_securitycheckpro/images/opa-icons-black32.png',
		'media/com_securitycheckpro/images/opa-icons-blue16.png',
		'media/com_securitycheckpro/images/opa-icons-blue32.png',
		'media/com_securitycheckpro/images/opa-icons-color16.png',
		'media/com_securitycheckpro/images/opa-icons-color32.png',
		'media/com_securitycheckpro/images/opa-icons-darkgray16.png',
		'media/com_securitycheckpro/images/opa-icons-darkgray32.png',
		'media/com_securitycheckpro/images/opa-icons-gray16.png',
		'media/com_securitycheckpro/images/opa-icons-gray32.png',
		'media/com_securitycheckpro/images/opa-icons-green16.png',
		'media/com_securitycheckpro/images/opa-icons-green32.png',
		'media/com_securitycheckpro/images/opa-icons-orange16.png',
		'media/com_securitycheckpro/images/opa-icons-orange32.png',
		'media/com_securitycheckpro/images/opa-icons-red16.png',
		'media/com_securitycheckpro/images/opa-icons-red32.png',
		'media/com_securitycheckpro/images/opa-icons-white16.png',
		'media/com_securitycheckpro/images/opa-icons-white32.png',
		'media/com_securitycheckpro/images/row_bkg.png',
		'media/com_securitycheckpro/images/header_bkg.png',
		'media/com_securitycheckpro/images/arrows.png',
		),
		'folders'    => array
		(
		// Remove new, fonts and stylesheet folders
		'media/com_securitycheckpro/new',
		'media/com_securitycheckpro/stylesheets',
		'media/com_securitycheckpro/fonts',
		)
    );
            
    
	/**
     * Removes obsolete files and folders
     *
     * @param array $ObsoleteFilesAndFolders
     */
    private function _removeObsoleteFilesAndFolders($ObsoleteFilesAndFolders)
    {
        // Remove files
        if(!empty($ObsoleteFilesAndFolders['files'])) { foreach($ObsoleteFilesAndFolders['files'] as $file)
            {
                $f = JPATH_ROOT.'/'.$file;            
                if(!file_exists($f)) { continue;
                }
                try{		
					$res = File::delete($f);
				} catch (Exception $e)
				{					
				}            
        }
        }
        
        /* Remove folders */
        if(!empty($ObsoleteFilesAndFolders['folders'])) { foreach($ObsoleteFilesAndFolders['folders'] as $folder)
            {
                $f = JPATH_ROOT.'/'.$folder;
                if( !file_exists($f) ) { continue;
                }   
				if( !is_dir($f) ) { continue;
                }      
				try{		
					$res = Folder::delete($f);
				} catch (Exception $e)
				{
				}
                                            
        }
        }
    }
    
    
    /* Delete files and folders */
    private function _4_version_changes()
    {
    
		// Extraemos la información necesario de la tabla #_extensions sobre el paquete trackactions       
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		$installer = new Installer();
		$installer->setDatabase($db);
		
		try {
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__extensions'));
			$query->where($db->quoteName('element') . ' = ' . $db->quote('pkg_trackactions'));
			$db->setQuery($query);
			$db->execute();
			$result = $db->loadAssocList();
		} catch(\Exception $e)
        {
			$result = null;
        }
		        
        // Si no existe versión previa no es necesario hacer ninguna acción
        if (!empty($result)) {
        
            // Decodificamos la información de la versión, que está en formato json en la entrada 'manifest_cache'
            $stack = json_decode($result[0]["manifest_cache"], true);
            
            // Versión de Securitycheck Pro instalada
            $trackactions_version = $stack["version"];
            
            // Si la versión instalada es menor a la 2.0, la desinstalamos
            if (version_compare($trackactions_version, "2.0", "lt")) {
                    
				$columnName      = $db->quoteName("extension_id");
				$tableExtensions = $db->quoteName("#__extensions");
				$type              = $db->quoteName("type");
				$columnElement   = $db->quoteName("element");

				// Uninstall Trackactions package
				$db->setQuery(
					"SELECT 
							$columnName
						FROM
							$tableExtensions
						WHERE
							$type='package'
						AND
							$columnElement='pkg_trackactions'"        
				);

				$id_trackactions = $db->loadResult();

				if ($id_trackactions) {
					$installer->uninstall('package', $id_trackactions, 1);
					Factory::getApplication()->enqueueMessage('The Trackactions package has been uninstalled due to compatibility issues with this version. Please, install the 2.0 version or higher.', 'error');
				}
			}
        }
		
		// Extraemos la información necesario de la tabla #_extensions sobre el plugin trackactions_k2 (no debería existir!)    
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		try {
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__extensions'));
			$query->where($db->quoteName('element') . ' = ' . $db->quote('trackactions_k2'));
			$db->setQuery($query);
			$db->execute();
			$result_trackactions_k2 = $db->loadAssocList();
		} catch(\Exception $e)
        {
			$result_trackactions_k2 = null;
        }
		        
        // Si no existe versión previa no es necesario hacer ninguna acción
        if (!empty($result_trackactions_k2)) {
                
			$columnName      = $db->quoteName("extension_id");
			$tableExtensions = $db->quoteName("#__extensions");
			$type              = $db->quoteName("type");
			$columnElement   = $db->quoteName("element");

			// Uninstall Track actions k2 plugin
			$db->setQuery(
				"SELECT 
					$columnName
				FROM
					$tableExtensions
				WHERE
					$type='plugin'
				AND
					$columnElement='trackactions_k2'"        
			);

			$id_trackactions_k2 = $db->loadResult();

			if ($id_trackactions_k2) {
				$installer->uninstall('plugin', $id_trackactions_k2, 1);
				Factory::getApplication()->enqueueMessage('The Trackactions k2 plugin has been uninstalled as currently there is no K2 version for J4.', 'error');
			}			
        }
		
		// Extraemos la información necesario de la tabla #_extensions sobre el plugin "update database"       
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		try {
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__extensions'));
			$query->where($db->quoteName('element') . ' = ' . $db->quote('securitycheckpro_update_database'));
			$db->setQuery($query);
			$db->execute();
			$result = $db->loadAssocList();
		} catch(\Exception $e)
        {
			$result = null;
        }
		        
        // Si no existe versión previa no es necesario hacer ninguna acción
        if (!empty($result)) {
        
            // Decodificamos la información de la versión, que está en formato json en la entrada 'manifest_cache'
            $stack = json_decode($result[0]["manifest_cache"], true);
            
            // Versión de Securitycheck Pro instalada
            $update_database_version = $stack["version"];
            
            // Si la versión instalada es menor a la 2.0, la desinstalamos
            if (version_compare($update_database_version, "2.0", "lt")) {
               // Disable update database plugin
				$tableExtensions = $db->quoteName("#__extensions");
				$columnElement   = $db->quoteName("element");
				$columnType      = $db->quoteName("type");
				$columnEnabled   = $db->quoteName("enabled");
				$db->setQuery(
					"UPDATE 
						$tableExtensions
					SET
						$columnEnabled=0
					WHERE
						$columnElement='securitycheckpro_update_database'
					AND
						$columnType='plugin'"
				);
				$db->execute(); 
				Factory::getApplication()->enqueueMessage('The update database plugin has been disabled. Please, install 2.0 version or higher.', 'warning');
			}
			
        }
        
    }
	
	    
    /**
     * Function to act prior to installation process begins
     *
     * @param   string     $action     Which action is happening (install|uninstall|discover_install|update)
     * @param   Installer  $installer  The class calling this method
     *
     * @return  boolean  True on success
     *
     * @since   3.7.0
     */
    public function preflight($action, $installer)
    {
		if ($action === 'update') {
            // Get the version we are updating from
            if (!empty($installer->extension->manifest_cache)) {
                $manifestValues = json_decode($installer->extension->manifest_cache, true);

                if (\array_key_exists('version', $manifestValues)) {
                    $this->fromVersion = $manifestValues['version'];
                }
            }
        }
		
        // Only allow to install on PHP 5.3.0 or later
        if (!version_compare(PHP_VERSION, '5.3.0', 'ge')) {        
            Factory::getApplication()->enqueueMessage('Securitycheck Pro requires, at least, PHP 5.3.0', 'error');
            return false;
        } else if (version_compare(JVERSION, '4.0.0', 'lt')) {
            // Only allow to install on Joomla! 4.0.0 or later
            Factory::getApplication()->enqueueMessage("This version only works in Joomla! 4 or higher", 'error');
            return false;
        }
        
        // Check if the 'mb_strlen' function is enabled
        if (!function_exists("mb_strlen")) {
            Factory::getApplication()->enqueueMessage("The 'mb_strlen' function is not installed in your host. Please, ask your hosting provider about how to install it.", 'warning');
            return false;
        }
        
        // Do changes for versions previous to 4.0
        $this->_4_version_changes();  
        
        $this->uninstallExtensions();    
        
    }
    
    /**
     * Runs after install, update or discover_update
     *
     * @param string     $type   install, update or discover_update
     * @param Installer $parent 
     */
    function postflight($type, $parent)
    {
				
		try
        {
			// Do this only during installs
			if ($type == "install")
			{			
				// Establecemos la configuración 'Easy config' para la configuración inicial
				try
				{
					$filePath = JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/helpers/installerhelper.php';
					include_once $filePath;
					$installer_model = new installerhelper();
					$two_factor = $installer_model->Set_Easy_Config();					
				}
				catch (Throwable $e)
				{					
					Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
					Factory::getApplication()->enqueueMessage('The "Easy config" option has not been applied.', 'warning');
					return null;
				}
				
				
			}
        }
        catch(\Exception $e)
        {
          
        }
		// Remove obsolete files and folders
		$this->_removeObsoleteFilesAndFolders($this->ObsoleteFilesAndFolders);        
    }
	
	/**
     * method to install the component
     *
     * @return void
     */
    function install($parent)
    {
		// Create the scheduled task during the first install
        if ( empty($this->fromVersion) ) {			
           // Create the scheduled task
			$this->create_scheduled_task();        
        }
	}
    
    
    /**
     * method to update the component
     *
     * @return void
     */
    function update($parent)
    {        
        // Uninstall extensions before removing their files and folders
        try {
            Log::add('Securitycheck Pro installation', Log::INFO, 'Update');
            $this->uninstallExtensions();
        } catch (\Throwable $e) {
           
        }
		       
    }
	
	/**
     * Uninstall old cron plugin
     *
     * @return  void
     *
     * @since   5.0.0
     */
    protected function uninstallExtensions()
    {
		
        // Don't uninstall extensions when not updating from a version older than 4.2
       /* if (empty($this->fromVersion) || version_compare($this->fromVersion, '4.2', 'ge')) {			
            return true;
        }*/

        $extensions = [
            /**
             * Define here the extensions to be uninstalled and optionally migrated on update.
             * For each extension, specify an associative array with following elements (key => value):
             * 'type'         => Field `type` in the `#__extensions` table
             * 'element'      => Field `element` in the `#__extensions` table
             * 'folder'       => Field `folder` in the `#__extensions` table
             * 'client_id'    => Field `client_id` in the `#__extensions` table
             * 'pre_function' => Name of an optional migration function to be called before
             *                   uninstalling, `null` if not used.
             */
            ['type' => 'plugin', 'element' => 'securitycheckpro_cron', 'folder' => 'system', 'client_id' => 0, 'pre_function' => 'migrateoldCronPlugin'],   
			['type' => 'package', 'element' => 'pkg_securitycheck', 'folder' => '', 'client_id' => 0, 'pre_function' => ''],
			['type' => 'component', 'element' => 'com_securitycheck', 'folder' => '', 'client_id' => 1, 'pre_function' => ''],
			['type' => 'plugin', 'element' => 'securitycheck', 'folder' => 'system', 'client_id' => 0, 'pre_function' => ''],
			
        ];
		
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($extensions as $extension) {
            $row = $db->setQuery(
                $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote($extension['type']))
                    ->where($db->quoteName('element') . ' = ' . $db->quote($extension['element']))
                    ->where($db->quoteName('folder') . ' = ' . $db->quote($extension['folder']))
                    ->where($db->quoteName('client_id') . ' = ' . $db->quote($extension['client_id']))
            )->loadObject();
						
            // Skip migrating and uninstalling if the extension doesn't exist
            if (!$row) {
                continue;
            }

            // If there is a function for migration to be called before uninstalling, call it
            if ($extension['pre_function'] && method_exists($this, $extension['pre_function'])) {
                $this->{$extension['pre_function']}($row);
            }
			

            try {
                $db->transactionStart();				

                // Unlock and unprotect the plugin so we can uninstall it
                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__extensions'))
                        ->set($db->quoteName('locked') . ' = 0')
                        ->set($db->quoteName('protected') . ' = 0')
                        ->where($db->quoteName('extension_id') . ' = :extension_id')
                        ->bind(':extension_id', $row->extension_id, ParameterType::INTEGER)
                )->execute();
				
                // Uninstall the plugin
                $installer = new Installer();
                $installer->setDatabase($db);
                $installer->uninstall($extension['type'], $row->extension_id);

                $db->transactionCommit();
            } catch (\Throwable $e) {			
                $db->transactionRollback();
                throw $e;
            }
        }
    }
	
	private function create_scheduled_task(){
				
        /** @var SchedulerComponent $component */
        $component = Factory::getApplication()->bootComponent('com_scheduler');

        /** @var TaskModel $model*/
        $model = $component->getMVCFactory()->createModel('Task', 'Administrator', ['ignore_request' => true]);
		
						
		try {
			$task = [
				'title'           => 'SCP Cron',
				'type'            => 'securitycheckpro.cron',
				'execution_rules' => [
					'rule-type'     => 'interval-days',
					'interval-days' => $this->cachetimeout,
					'exec-time'     => $this->exec_time,
					'exec-day'      => gmdate('d'),
				],
				'state'  => 1,
				'params' => [
					'task_to_be_launched' => $this->old_task_set,
				],
			];	
		} catch (\Exception $e) { 
			
        }	
					
        $model->save($task);
	}	
	
	/**
     * Migrate plugin parameters of obsolete system plugin to simulate cron
     *
     * @param   \stdClass  $rowOld  Object with the obsolete plugin's record in the `#__extensions` table
     *
     * @return  void
     *
     * @since   5.0.0
     */
    private function migrateoldCronPlugin($data)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
						
		try {
			$old_plugin_config = $db->setQuery(
				$db->getQuery(true)
					->select('storage_value')
					->from($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key') . ' = "cron_plugin"')
			)->loadObject();
		} catch (\Exception $e) { 
			$old_plugin_config = null;
        }
				
		if (!empty($old_plugin_config)){
			$old_plugin_params = json_decode($old_plugin_config->storage_value,true);
			if ((int) $old_plugin_params['launch_time'] < 10) {
				$this->exect_time = '0' . $old_plugin_params['launch_time'] . ':00';
			} else {
				$this->exect_time = $old_plugin_params['launch_time'] . ':00';
			}
			$this->old_task_set = $old_plugin_params['tasks'];
		} 		
		
		//Create the scheduled task
		$this->create_scheduled_task();
    }
    
    
}
?>
