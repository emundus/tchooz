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
use Joomla\Component\Scheduler\Administrator\Model\TaskModel;

class com_SecuritycheckproInstallerScript
{
    /**
     * The extension version we are updating from
     *
     * @var    string
     * @since  3.7
     */
    protected $fromVersion = null;
	
	private int $cachetimeout = 1;
	private string $exec_time = '02:00';
	/** @var string */
	private string $old_task_set = 'integrity';
	
	/**
     * 
     *
     * @var array<string,mixed> Obsolete files and folders to remove after new UI  
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
		// Remove log-type and read/unread icons replaced by Bootstrap badges
		'media/com_securitycheckpro/images/xss.png',
		'media/com_securitycheckpro/images/xss_base64.png',
		'media/com_securitycheckpro/images/sql_injection.png',
		'media/com_securitycheckpro/images/sql_injection_base64.png',
		'media/com_securitycheckpro/images/local_file_inclusion.png',
		'media/com_securitycheckpro/images/local_file_inclusion_base64.png',
		'media/com_securitycheckpro/images/permitted.png',
		'media/com_securitycheckpro/images/blocked.png',
		'media/com_securitycheckpro/images/dinamically_blocked.png',
		'media/com_securitycheckpro/images/second_level.png',
		'media/com_securitycheckpro/images/http.png',
		'media/com_securitycheckpro/images/session_protection.png',
		'media/com_securitycheckpro/images/session_hijack.png',
		'media/com_securitycheckpro/images/upload_scanner.png',
		'media/com_securitycheckpro/images/spam_protection.png',
		'media/com_securitycheckpro/images/url_inspector.png',
		'media/com_securitycheckpro/images/injection.png',
		'media/com_securitycheckpro/images/read.png',
		'media/com_securitycheckpro/images/no_read.png',
		'media/com_securitycheckpro/images/logs.png',
		'media/com_securitycheckpro/images/tick_16x16.png',
		// Remove mode (alert/strict) feature — only strict mode remains
		'administrator/components/com_securitycheckpro/helpers/firewall_config_mode_tab.php',
		// Remove DB optimization feature (InnoDB always reports no need to optimize)
		'administrator/components/com_securitycheckpro/src/Controller/DbcheckController.php',
		'administrator/components/com_securitycheckpro/src/Model/DbcheckModel.php',
		'administrator/components/com_securitycheckpro/src/View/Dbcheck/HtmlView.php',
		'administrator/components/com_securitycheckpro/tmpl/dbcheck/default.php',
		'media/com_securitycheckpro/js/Dbcheck.js',
		),
		'folders'    => array
		(
		// Remove new, fonts and stylesheet folders
		'media/com_securitycheckpro/new',
		'media/com_securitycheckpro/stylesheets',
		'media/com_securitycheckpro/fonts',
		// Remove DB optimization feature
		'administrator/components/com_securitycheckpro/src/View/Dbcheck',
		'administrator/components/com_securitycheckpro/tmpl/dbcheck',
		)
    );
            
    
	/**
     * Removes obsolete files and folders
     *
     * @param array<string,mixed> $ObsoleteFilesAndFolders
	 *
     * @return  void
	 *
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
				} catch (\Exception $e)
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
				} catch (\Exception $e)
				{
				}                                            
			}
        }
    }
    
	/**
     * Delete files and folders since version 4
     *
     *
     * @return  void
	 *
     */
    private function _4_version_changes()
    {
    
		// Extraemos la informaci�n necesario de la tabla #_extensions sobre el paquete trackactions       
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
		        
        // Si no existe versi�n previa no es necesario hacer ninguna acci�n
        if (!empty($result)) {
        
            // Decodificamos la informaci�n de la versi�n, que est� en formato json en la entrada 'manifest_cache'
            $stack = json_decode($result[0]["manifest_cache"], true);

            if (!is_array($stack) || !isset($stack["version"])) {
                return;
            }

            // Versi�n de Securitycheck Pro instalada
            $trackactions_version = $stack["version"];

            // Si la versi�n instalada es menor a la 2.0, la desinstalamos
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
					$installer->uninstall('package', $id_trackactions);
					Factory::getApplication()->enqueueMessage('The Trackactions package has been uninstalled due to compatibility issues with this version. Please, install the 2.0 version or higher.', 'error');
				}
			}
        }
		
		// Extraemos la informaci�n necesario de la tabla #_extensions sobre el plugin trackactions_k2 (no deber�a existir!)    
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
		        
        // Si no existe versi�n previa no es necesario hacer ninguna acci�n
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
				$installer->uninstall('plugin', $id_trackactions_k2);
				Factory::getApplication()->enqueueMessage('The Trackactions k2 plugin has been uninstalled as currently there is no K2 version for J4.', 'error');
			}			
        }
		
		// Extraemos la informaci�n necesario de la tabla #_extensions sobre el plugin "update database"       
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
		        
        // Si no existe versi�n previa no es necesario hacer ninguna acci�n
        if (!empty($result)) {
        
            // Decodificamos la informaci�n de la versi�n, que est� en formato json en la entrada 'manifest_cache'
            $stack = json_decode($result[0]["manifest_cache"], true);

            if (!is_array($stack) || !isset($stack["version"])) {
                return;
            }

            // Versi�n de Securitycheck Pro instalada
            $update_database_version = $stack["version"];

            // Si la versi�n instalada es menor a la 2.0, la desinstalamos
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
		if ($action === 'uninstall') {
            // Si NO se est� desinstalando el paquete, impedimos desinstalar este sub-elemento.
            if (!\defined('SCP_PKG_UNINSTALLING')) {
                Factory::getApplication()->enqueueMessage(
                    Text::_('COM_SECURITYCHECKPRO_UNINSTALL_BLOCKED'),
                    'warning'
                );
                return false; // Abortamos la desinstalaci�n de este elemento.
            }
			$this->deleteSchedulerTasksByType('securitycheckpro.cron');
        }
		
		if ($action === 'update') {
            // Get the version we are updating from
            if (!empty($installer->extension->manifest_cache)) {
                $manifestValues = json_decode($installer->extension->manifest_cache, true);

                if (is_array($manifestValues) && \array_key_exists('version', $manifestValues)) {
                    $this->fromVersion = $manifestValues['version'];					
                }
            }
        }
		// Only allow to install on PHP 8.1.0 or later
        if (!version_compare(PHP_VERSION, '8.1.0', 'ge')) {        
            Factory::getApplication()->enqueueMessage('Securitycheck Pro requires, at least, PHP 8.1.0', 'error');
            return false;
		// @phpstan-ignore-next-line
        }  else if (version_compare(JVERSION, '5.0.0', 'lt')) {
            // Only allow to install on Joomla! 5.0.0 or later
            Factory::getApplication()->enqueueMessage("This version only works in Joomla! 5 or higher", 'error');
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
		
		return true;
        
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
    function postflight($type, $parent)
    {
				
		try
        {
			// Do this only during installs
			if ($type == "install")
			{			
				// Establecemos la configuraci�n 'Easy config' para la configuraci�n inicial
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
					return false;
				}				
			}
        }
        catch(\Exception $e)
        {
          
        }
		// Remove obsolete files and folders
		$this->_removeObsoleteFilesAndFolders($this->ObsoleteFilesAndFolders);       

		return true;
    }
	
	/**
     * Method to install the component
     *
	 * @param   Installer       $parent    The parent class
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
     * Method to update the component
     *
	 * @param   Installer       $parent    The parent class
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
	
	private function schedulerTaskExists(string $type): bool
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$id = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__scheduler_tasks'))
				->where($db->quoteName('type') . ' = ' . $db->quote($type))
		)->loadResult();

		return (int) $id > 0;
	}
	
	/**
	 * Borra tareas del Scheduler (y logs) por type.
	 *
	 * @param string $type
	 * @return void
	 */
	private function deleteSchedulerTasksByType(string $type): void
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$ids = $db->setQuery(
			$db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__scheduler_tasks'))
				->where($db->quoteName('type') . ' = ' . $db->quote($type))
		)->loadColumn();

		if (!is_array($ids) || $ids === []) {
			return;
		}

		$taskIds = array_values(array_filter(array_map('intval', $ids), static fn (int $v): bool => $v > 0));
		if ($taskIds === []) {
			return;
		}

		// Logs
		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__scheduler_logs'))
				->where($db->quoteName('id') . ' IN (' . implode(',', $taskIds) . ')')
		)->execute();

		// Tasks
		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__scheduler_tasks'))
				->where($db->quoteName('id') . ' IN (' . implode(',', $taskIds) . ')')
		)->execute();
	}
	
	/**
	 * @return void
	 */
	protected function uninstallExtensions(): void
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		/** @var array<int, array{
		 *   type: string,
		 *   element: string,
		 *   folder: string,
		 *   client_id: int,
		 *   pre: (callable(object): void)|null
		 * }> $extensions
		 */
		$extensions = [
			[
				'type' => 'plugin',
				'element' => 'securitycheckpro_cron',
				'folder' => 'system',
				'client_id' => 0,
				'pre' => [$this, 'migrateoldCronPlugin'],
			],
			[
				'type' => 'package',
				'element' => 'pkg_securitycheck',
				'folder' => '',
				'client_id' => 0,
				'pre' => null,
			],
			[
				'type' => 'component',
				'element' => 'com_securitycheck',
				'folder' => '',
				'client_id' => 1,
				'pre' => null,
			],
			[
				'type' => 'plugin',
				'element' => 'securitycheck',
				'folder' => 'system',
				'client_id' => 0,
				'pre' => null,
			],
		];

		foreach ($extensions as $extension) {
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote($extension['type']))
				->where($db->quoteName('element') . ' = ' . $db->quote($extension['element']))
				->where($db->quoteName('folder') . ' = ' . $db->quote($extension['folder']))
				->where($db->quoteName('client_id') . ' = ' . (int) $extension['client_id']);

			$row = $db->setQuery($query)->loadObject();

			if ($row === null) {
				continue;
			}

			if ($extension['pre'] !== null) {
				($extension['pre'])($row);
			}

			try {
				$db->transactionStart();
				
				$extension_id = (int) $row->extension_id;

				$db->setQuery(
					$db->getQuery(true)
						->update($db->quoteName('#__extensions'))
						->set($db->quoteName('locked') . ' = 0')
						->set($db->quoteName('protected') . ' = 0')
						->where($db->quoteName('extension_id') . ' = :extension_id')
						->bind(':extension_id', $extension_id, ParameterType::INTEGER)
				)->execute();

				$installer = new Installer();
				$installer->setDatabase($db);
				$installer->uninstall($extension['type'], (int) $row->extension_id);

				$db->transactionCommit();
			} catch (\Throwable $e) {
				$db->transactionRollback();
				throw $e;
			}
		}
	}
	
	/**
	 * Crea las tareas programadas
	 *
	 * @return void
	 */
	private function create_scheduled_task(): void
	{
		 // No sobrescribir configuraci�n del usuario
		if ($this->schedulerTaskExists('securitycheckpro.cron')) {
			return;
		}
	
		// Valida cachetimeout (interval-days) como int > 0
		$intervalDays = (int) ($this->cachetimeout ?? 0);
		if ($intervalDays <= 0) {
			// Si prefieres no lanzar, puedes return; pero en instalaci�n suele ser mejor fallar claro
			throw new \RuntimeException('Invalid cachetimeout (interval-days) value.');
		}

		// Valida exec_time como "HH:MM"
		$execTime = (string) ($this->exec_time ?? '');
		if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $execTime)) {
			throw new \RuntimeException('Invalid exec_time. Expected HH:MM.');
		}

		/** @var \Joomla\CMS\Extension\ComponentInterface $component */
		$component = Factory::getApplication()->bootComponent('com_scheduler');

		/** @var \Joomla\Component\Scheduler\Administrator\Model\TaskModel $model */
		// @phpstan-ignore-next-line
		$model = $component->getMVCFactory()->createModel('Task', 'Administrator', ['ignore_request' => true]);

		/** @var array<string, mixed> $task */
		$task = [
			'title' => 'SCP Cron',
			'type'  => 'securitycheckpro.cron',
			'execution_rules' => [
				'rule-type'     => 'interval-days',
				'interval-days' => $intervalDays,
				'exec-time'     => $execTime,
				// Si realmente lo necesitas; si no, qu�talo para evitar �reglas� ambiguas
				'exec-day'      => (int) gmdate('d'),
			],
			'state'  => 1,
			'params' => [
				'task_to_be_launched' => $this->old_task_set,
			],
		];
		$result = $model->save($task);

		// En Joomla, save() puede devolver false en error (o un valor truthy). Lo tratamos de forma segura.
		if ($result === false) {
			throw new \RuntimeException('Failed to create scheduled task (model->save returned false).');
		}
	}
	
	/**
	 * Migrate plugin parameters of obsolete system plugin to simulate cron.
	 *
	 * @return void
	 *
	 * @since 5.0.0
	 */
	private function migrateoldCronPlugin(): void
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		/** @var object{storage_value?: mixed}|null $oldPluginConfig */
		$oldPluginConfig = null;

		try {
			$oldPluginConfig = $db->setQuery(
				$db->getQuery(true)
					->select($db->quoteName('storage_value'))
					->from($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key') . ' = ' . $db->quote('cron_plugin'))
			)->loadObject();
		} catch (\Throwable $e) {
			$oldPluginConfig = null;
		}

		if ($oldPluginConfig === null) {
			return;
		}

		$raw = $oldPluginConfig->storage_value ?? null;
		if (!is_string($raw) || $raw === '') {
			return;
		}

		$decoded = json_decode($raw, true);

		if (!is_array($decoded)) {
			return;
		}

		// launch_time
		$launchTime = $decoded['launch_time'] ?? null;
		if (is_int($launchTime) || (is_string($launchTime) && ctype_digit($launchTime))) {
			$hour = (int) $launchTime;
			if ($hour >= 0 && $hour <= 23) {
				$this->exec_time = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
			}
		}

		// tasks
		$this->old_task_set = $decoded['tasks'] ?? 'integrity';

		// Solo crear si no existe ya una task configurada por el usuario
		if (!$this->schedulerTaskExists('securitycheckpro.cron')) {
			$this->create_scheduled_task();
		}
	}   
    
}
?>
