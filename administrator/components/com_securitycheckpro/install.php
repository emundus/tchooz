<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;

/**
 * Script file of Securitycheck Pro component
 */
class com_SecuritycheckproInstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
    // Check if we are calling update method. It's used in 'install_message' function
    public $update = false;
    
    // Resultado de la desinstalación del componente Securitycheck
    public $result_free = "";
    public $id_free;
    
    // 'memory_limit' demasiado bajo
    public $memory_limit = '';
    
    // url plugin habilitado?
    public $url_plugin_enabled = false;        
    	
	// Badge style bg- for J4 and higher
	private $badge_style = "bg-";
	
	/**
     * The extension ersion we are updating from
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
            
    /* Función que desinstala el componente Securitycheck */
    private function _unistall_Securitycheck()
    {
        
        $db = Factory::getDbo();
        $installer = new Installer();
        
        $columnName      = $db->quoteName("extension_id");
        $tableExtensions = $db->quoteName("#__extensions");
        $type              = $db->quoteName("type");
        $columnElement   = $db->quoteName("element");

        // Uninstall Securitycheck component
        $db->setQuery(
            "SELECT 
					$columnName
				FROM
					$tableExtensions
				WHERE
					$type='component'
				AND
					$columnElement='com_securitycheck'"        
        );

        $this->id_free = $db->loadResult();

        if ($this->id_free) {
            $this->result_free = $installer->uninstall('component', $this->id_free, 1);
        }
    } 

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
        $db = Factory::getDBO();
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
               $installer = new Installer();
        
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
        $db = Factory::getDBO();
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
        
            $installer = new Installer();
        
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
        $db = Factory::getDBO();
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
        
        $this->_unistall_Securitycheck();    
        
    }
    
    /**
     * Runs after install, update or discover_update
     *
     * @param string     $type   install, update or discover_update
     * @param Installer $parent 
     */
    function postflight($type, $parent)
    {
		// Inicializamos las variables
        $existe_tabla = false;
                
        $db = Factory::getDBO();
        $total_rows = $db->getTableList();
        
        if (!(is_null($total_rows))) {
            foreach ($total_rows as $table_name)
            {
                if (strstr($table_name, "securitycheckpro_logs")) {
                    $existe_tabla = true;
                }
            }
        }
        
        if ( ($type == "install") && (!$existe_tabla) ) {
            // Disable Securitycheck Pro plugin
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
					$columnElement='securitycheckpro'
				AND
					$columnType='plugin'"
            );
            $db->execute();
            
            // Disable Securitycheck Pro Cron Task plugin
            $db->setQuery(
                "UPDATE 
					$tableExtensions
				SET
					$columnEnabled=0
				WHERE
					$columnElement='securitycheckprocron'
				AND
					$columnType='plugin'"
            );

            $db->execute();
            Factory::getApplication()->enqueueMessage('Error creating some mandatory tables in database. Securitycheck Pro and Cron plugins have been disabled.', 'warning');
        }    
		
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
        // General settings
        $status = new stdClass();
        $status->modules = array();
        
        // Array to store module and plugin installation results
        $result = array();
        $indice = 0;
        
        $installer = new Installer();
        
        
        $manifest = $parent->getParent()->getManifest();
        $source = $parent->getParent()->getPath('source');
        
        // Install module
        $db = Factory::getDbo();
        $result[$indice] = $installer->install($source. DIRECTORY_SEPARATOR .'modules' . DIRECTORY_SEPARATOR .'mod_scpadmin_quickicons');
        $indice++;
                
        // Enable and configure module
        $query = "UPDATE #__modules SET position='icon', ordering = '1', published = '1' WHERE module='mod_scpadmin_quickicons'";
        $db->setQuery($query);
        $db->execute();
        
        $query = "SELECT id FROM #__modules WHERE module='mod_scpadmin_quickicons'";
        $db->setQuery($query);
        $modID = $db->loadResult();
                
        // If the module_id is empty, we'll get an SQL error and the installion process will break
        if ((!empty($modID)) && (is_int(intval($modID)))) {                        
           /* $query = "REPLACE #__modules_menu (moduleid,menuid) VALUES ({$modID}, 0)";
			$query = "UPDATE #__modules_menu SET ordering = '-100' WHERE name='System - url inspector'";			
			$db->setQuery($query);
            $db->execute();*/
        }
                
        $status->modules[] = array('name'=>'Securitycheck Pro - Quick Icons','client'=>'administrator', 'result'=>$result); 
        
        // Install plugins
                        
        foreach($manifest->plugins->plugin as $plugin)
        {
            $installer = new Installer();
            $attributes = $plugin->attributes();
            $plg = $source . DIRECTORY_SEPARATOR . $attributes['folder']. DIRECTORY_SEPARATOR . $attributes['plugin'];
			$result[$indice] = $installer->install($plg);
            $indice++;
        }
        
        // Update the URL inspector plugin ordering; it must be published the last
        $query = "UPDATE #__extensions SET ordering = '-100' WHERE name='System - url inspector'";
        $db->setQuery($query);
        $db->execute();
        
        // Check if url plugin is enabled
        $query = "SELECT enabled from #__extensions WHERE name='System - url inspector'";
        $db->setQuery($query);
        $this->url_plugin_enabled = $db->loadResult();

        $db = Factory::getDbo();
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
        
                
        // Extract 'memory_limit' value cutting the last character
        $memory_limit = ini_get('memory_limit');
        $memory_limit = (int) substr($memory_limit, 0, -1);
                
                
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
		
		// Create the scheduled task during the first install
        if ( empty($this->fromVersion) ) {			
           // Create the scheduled task
			$this->create_scheduled_task();        
        }
		
                       
        // Install message
        $this->install_message($this->id_free, $this->result_free, $result, $status, $memory_limit);
    }
    
    /**
     * method to uninstall the component
     *
     * @return void
     */
    function uninstall($parent)
    {
    
        // General settings
        $status = new stdClass();
        $status->modules = array();
        
        // Array to store uninstall results
        $result = array();
        
        $db = Factory::getDbo();
        
        // Uninstall module
        $db->setQuery("SELECT extension_id FROM #__extensions WHERE type = 'module' AND element = 'mod_scpadmin_quickicons' LIMIT 1");
        (int) $id = $db->loadResult();
        if ($id) {
            $installer = new Installer();
            $result[0] = $installer->uninstall('module', $id);
            $status->modules[] = array('name'=>'Securitycheck Pro - Quick Icons','client'=>'administrator', 'result'=>$result);            
        }
        
        $columnName      = $db->quoteName("extension_id");
        $tableExtensions = $db->quoteName("#__extensions");
        $type              = $db->quoteName("type");
        $columnElement   = $db->quoteName("element");
        $columnType      = $db->quoteName("folder");
        $result = array();
            
        // Uninstall  Securitycheck Pro plugin
        $db->setQuery(
            "SELECT 
				$columnName
			FROM
				$tableExtensions
			WHERE
				$type='plugin'
			AND
				$columnElement='securitycheckpro'
			AND
				$columnType='system'"
        );

        $id = $db->loadResult();

        if ($id) {
            $installer = new Installer();
            $result[1] = $installer->uninstall('plugin', $id, 1);        
        } else {
            $result[1] = false;
        }
        
        // Uninstall  Securitycheck Pro Cron Task plugin
        $db->setQuery(
            "SELECT 
				$columnName
			FROM
				$tableExtensions
			WHERE
				$type='plugin'
			AND
				$columnElement='securitycheckprocron'
			AND
				$columnType='task'"
        );

        $id = $db->loadResult();

        if ($id) {
            $installer = new Installer();
            $result[2] = $installer->uninstall('plugin', $id, 1);        
        } else 
        {
            $result[2] = false;
        }
        
        // Uninstall  Securitycheck Pro URL inspector
        $db->setQuery(
            "SELECT 
				$columnName
			FROM
				$tableExtensions
			WHERE
				$type='plugin'
			AND
				$columnElement='url_inspector'
			AND
				$columnType='system'"
        );

        $id = $db->loadResult();

        if ($id) {
            $installer = new Installer();
            $result[3] = $installer->uninstall('plugin', $id, 1);        
        } else {
            $result[3] = false;
        }    

		// Uninstall  Securitycheck Pro Task Checker
        $db->setQuery(
            "SELECT 
				$columnName
			FROM
				$tableExtensions
			WHERE
				$type='plugin'
			AND
				$columnElement='securitycheckpro_task_checker'
			AND
				$columnType='system'"
        );

        $id = $db->loadResult();

        if ($id) {
            $installer = new Installer();
            $result[4] = $installer->uninstall('plugin', $id, 1);        
        } else {
            $result[4] = false;
        }  
		
		// Remove scheduled tasks
		try {
            $sql = "DELETE FROM #__scheduler_tasks  WHERE type='securitycheckpro.cron'";
			$db->setQuery($sql);
			$db->execute();  		
        } catch (\Throwable $e) {
           Factory::getApplication()->enqueueMessage($e->getMessge(), 'error');
        }
		                        
        // Uninstall message
        $this->uninstall_message($result, $status);
        
    }
    
    /**
     * method to update the component
     *
     * @return void
     */
    function update($parent)
    {        
        // This variable is updated.
        $this->update = true;
		// Uninstall extensions before removing their files and folders
        try {
            Log::add('Securitycheck Pro installation', Log::INFO, 'Update');
            $this->uninstallExtensions();
        } catch (\Throwable $e) {
           
        }
		
        $this->install($parent);        
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
        if (empty($this->fromVersion) || version_compare($this->fromVersion, '4.2', 'ge')) {			
            return true;
        }

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
        ];

        $db = Factory::getDbo();

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
        $db = Factory::getDbo();
						
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
    
    /**
     * method to show the install message
     *
     * @return void
     */
    function install_message($id_free,$result_free,$result,$status,$memory_limit)
    {
        // Initialize variables
        $cabecera = '';
        $result_ok = '';
        $result_not_ok = '';
            
        if (!($this->update)) {
            $cabecera = Text::_('COM_SECURITYCHECKPRO_HEADER_INSTALL');
            $result_ok = Text::_('COM_SECURITYCHECKPRO_INSTALLED');
            $result_not_ok = Text::_('COM_SECURITYCHECKPRO_NOT_INSTALLED');
        } else 
        {
            $cabecera = Text::_('COM_SECURITYCHECKPRO_HEADER_UPDATE');
            $result_ok = Text::_('COM_SECURITYCHECKPRO_UPDATED');
            $result_not_ok = Text::_('COM_SECURITYCHECKPRO_NOT_UPDATED');
        }
        
        ?>
        <img src='../media/com_securitycheckpro/images/tick_48x48.png' style='float: left; margin: 5px;'>
        <?php
        if (!($this->update)) {            
            ?>
            <h1><?php echo $cabecera ?></h1>
            <h2><?php echo Text::_('COM_SECURITYCHECKPRO_WELCOME'); ?></h2>
            <?php 
        } else {
            ?>
            <h2><?php echo $cabecera ?></h2>
            <?php
        }
        ?>
            <div class="securitycheck-bootstrap">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="title" colspan="2"><?php echo Text::_('COM_SECURITYCHECKPRO_EXTENSION'); ?></th>
                        <th width="30%"><?php echo Text::_('COM_SECURITYCHECKPRO_STATUS'); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <td colspan="2">Securitycheck Pro <?php echo Text::_('COM_SECURITYCHECKPRO_COMPONENT'); ?></td>
                        <td>
        <?php 
                                $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
        ?>
          <?php echo $span . $result_ok; ?>
                            </span>
                        </td>
                    </tr>
                    <tr class="row0">
                        <td class="key" colspan="2">Securitycheck Pro <?php echo Text::_('COM_SECURITYCHECKPRO_PLUGIN'); ?></td>
        <?php
        if ($result[1]) {
            ?>
                            <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . $result_ok; ?>
                                </span>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "info\">";    
            $message = Text::_('COM_SECURITYCHECKPRO_PLUGIN_ENABLED');                                                                                    
            ?>
            <?php echo $span . $message; ?>
                            </td>
            <?php
        } else {
            ?>
                            <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . $result_not_ok; ?>
                                </span>
                            </td>
            <?php
        }
        ?>
                    </tr>
                    <tr class="row0">
                        <td class="key" colspan="2">Securitycheck Pro Task Cron <?php echo Text::_('Plugin'); ?></td>
        <?php
        if ($result[4]) {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . $result_ok; ?>
                            </span>
            <?php 
            $limit = false;
			$span = "<span class=\"badge " . $this->badge_style . "info\">";    
            $message = Text::_('COM_SECURITYCHECKPRO_PLUGIN_ENABLED');
            if ($memory_limit <= 128) {               
                $limit = true;
            }
            ?>
            <?php echo $span . $message; ?>
				</span>
            </td>
            <?php
        } else {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . $result_not_ok; ?>
                            </span>
                        </td>
            <?php
        }
        ?>
                    </tr>
                    <tr class="row0">
                        <td class="key" colspan="2">URL Inspector <?php echo Text::_('Plugin'); ?></td>
        <?php
        if ($result[2]) {
            ?>
                            <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . $result_ok; ?>
                                </span>
            <?php 
            if ($this->url_plugin_enabled) {
                $span = "<span class=\"badge " . $this->badge_style . "info\">";    
                $message = Text::_('COM_SECURITYCHECKPRO_PLUGIN_ENABLED');
            } else 
            {
                $span = "<span class=\"badge " . $this->badge_style . "danger\">";    
                $message = Text::_('COM_SECURITYCHECKPRO_PLUGIN_DISABLED');
            }
            ?>
            <?php echo $span . $message; ?>                                
                            </td>
            <?php
        } else
                        {
            ?>
                            <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . $result_not_ok; ?>
                                </span>
                            </td>
            <?php
        }
        ?>
                    </tr>      
			<tr class="row0">
                        <td class="key" colspan="2">Securitycheck Pro Task Checker <?php echo Text::_('Plugin'); ?></td>
        <?php
        if ($result[3]) {
            ?>
                            <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . $result_ok; ?>
                                </span>
            <?php 
				$span = "<span class=\"badge " . $this->badge_style . "info\">";
                $message = Text::_('COM_SECURITYCHECKPRO_PLUGIN_ENABLED');            
            ?>
            <?php echo $span . $message; ?>                                
                </td>
            <?php
        } else
                        {
            ?>
                            <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . $result_not_ok; ?>
                                </span>
                            </td>
            <?php
        }
        ?>
                    </tr>
        <?php
        if (count($status->modules) > 0) {
            ?>
                        <tr class="row0">
                        <td class="key" colspan="2">Securitycheck Pro Info <?php echo Text::_('COM_SECURITYCHECKPRO_MODULE'); ?></td>
            <?php
            if ($status->modules['0']['result']) {
                ?>
                            <td>
                <?php 
                $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
                ?>
                <?php echo $span . $result_ok; ?>
                                </span>
                <?php 
                $span = "<span class=\"badge " . $this->badge_style . "info\">";    
                $message = Text::_('COM_SECURITYCHECKPRO_PLUGIN_ENABLED');                                                                                    
                ?>
                <?php echo $span . $message; ?>
                            </td>
                <?php
            } else
            {
                ?>
                            <td>
                <?php 
                $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
                ?>
                <?php echo $span . $result_not_ok; ?>
                                </span>
                            </td>                            
                <?php
            }
            ?>
                        </tr>
            <?php
        }
        if ($id_free) {
            ?>
                        <tr class="row0">
                            <td class="key" colspan="2">Securitycheck <?php echo Text::_('COM_SECURITYCHECK_COMPONENT'); ?></td>
            <?php
            if ($result_free) {
                ?>
                            <td>
                <?php 
                $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
                ?>
                <?php echo $span . Text::_('COM_SECURITYCHECKPRO_UNINSTALLED'); ?>
                                </span>
                            </td>                                    
                <?php
            } else 
            {
                ?>
                            <td>
                <?php 
                $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
                ?>
                <?php echo $span . Text::_('COM_SECURITYCHECK_NOT_UNINSTALLED'); ?>
                                </span>
                            </td>                            
                <?php
            }
            ?>
                        </tr>
            <?php
        }
        ?>
                </tbody>
            </table>
            </div>
        <?php
    }

    /**
     * method to show the uninstall message
     *
     * @return void
     */
    function uninstall_message($result,$status)
    {
        ?>
        <h1><?php echo Text::_('COM_SECURITYCHECKPRO_HEADER_UNINSTALL'); ?></h1>
        <h2><?php echo Text::_('COM_SECURITYCHECKPRO_GOODBYE'); ?></h2>
        <div class="securitycheck-bootstrap">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="title" colspan="2"><?php echo Text::_('COM_SECURITYCHECKPRO_EXTENSION'); ?></th>
                    <th width="30%"><?php echo Text::_('COM_SECURITYCHECKPRO_STATUS'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr>
                    <td colspan="2">Securitycheck Pro <?php echo Text::_('COM_SECURITYCHECKPRO_COMPONENT'); ?></td>
                    <td>
        <?php 
          $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
        ?>
         <?php echo $span . Text::_('COM_SECURITYCHECKPRO_UNINSTALLED'); ?>
                        </span>
                    </td>                    
                </tr>
                <tr class="row0">
                    <td class="key" colspan="2">Securitycheck Pro <?php echo Text::_('COM_SECURITYCHECKPRO_PLUGIN'); ?></td>
        <?php
        if ($result[1]) {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_UNINSTALLED'); ?>
                            </span>
                        </td>
            <?php
        } else 
        {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_NOT_INSTALLED'); ?>
                            </span>
                        </td>                        
            <?php
        }
        ?>
                </tr>
                <tr class="row0">
                    <td class="key" colspan="2">Securitycheck Pro Cron <?php echo Text::_('Plugin'); ?></td>
        <?php
        if ($result[2]) {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_UNINSTALLED'); ?>
                            </span>
                        </td>
            <?php
        } else 
        {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_NOT_INSTALLED'); ?>
                            </span>
                        </td>
            <?php
        }
        ?>
                </tr>
                <tr class="row0">
                    <td class="key" colspan="2">URL Inspector <?php echo Text::_('Plugin'); ?></td>
        <?php
        if ($result[3]) {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_UNINSTALLED'); ?>
                            </span>
                        </td>
            <?php
        } else
        {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_NOT_INSTALLED'); ?>
                            </span>
                        </td>
            <?php
        }
        ?>
                </tr> 
				<tr class="row0">
                    <td class="key" colspan="2">Securitycheck Pro Task Checker <?php echo Text::_('Plugin'); ?></td>
        <?php
        if ($result[4]) {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_UNINSTALLED'); ?>
                            </span>
                        </td>
            <?php
        } else
        {
            ?>
                        <td>
            <?php 
            $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
            ?>
            <?php echo $span . Text::_('COM_SECURITYCHECKPRO_NOT_INSTALLED'); ?>
                            </span>
                        </td>
            <?php
        }
        ?>
                </tr>
        <?php
        if (count($status->modules) > 0) {
            ?>
                    <tr class="row0">
                    <td class="key" colspan="2">Securitycheck Pro Info <?php echo Text::_('COM_SECURITYCHECKPRO_MODULE'); ?></td>
            <?php
            if ($status->modules['0']['result']) {
                ?>
                        <td>
                <?php 
                   $span = "<span class=\"badge " . $this->badge_style . "success\">";                                
                ?>
                <?php echo $span . Text::_('COM_SECURITYCHECKPRO_UNINSTALLED'); ?>
                            </span>
                        </td>
                    <?php
            } else
            {
                ?>
                        <td>
                <?php 
                 $span = "<span class=\"badge " . $this->badge_style . "danger\">";                                
                ?>
                <?php echo $span . Text::_('COM_SECURITYCHECKPRO_NOT_INSTALLED'); ?>
                            </span>
                        </td>
                  <?php
            }
            ?>
                    </tr>
            <?php
        }
        ?>
            </tbody>
        </table>
        </div>
        <?php
    }
}
?>
