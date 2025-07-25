<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel;
use Joomla\Component\Users\Administrator\Model\UserModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Updater\Update;
use Joomla\CMS\Updater\Updater;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Client\FtpClient;
use Joomla\Filesystem\Path;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\AuthenticationHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Event\AbstractEvent;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\CpanelModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\ProtectionModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\DatabaseupdatesModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\SecuritycheckproModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;

if (!defined('SCP_USER_AGENT')) define('SCP_USER_AGENT', 'Securitycheck Pro User agent');

class JsonModel extends BaseModel
{

	const    STATUS_OK                    = 200;    // Normal reply
	const    STATUS_NOT_AUTH                = 401;    // Invalid credentials
	const    STATUS_NOT_ALLOWED            = 403;    // Not enough privileges
	const    STATUS_NOT_FOUND            = 404;  // Requested resource not found
	const    STATUS_INVALID_METHOD        = 405;    // Unknown JSON method
	const    STATUS_ERROR                = 500;    // An error occurred
	const    STATUS_NOT_IMPLEMENTED        = 501;    // Not implemented feature
	const    STATUS_NOT_AVAILABLE        = 503;    // Remote service not activated

	const    CIPHER_RAW            = 1;    // Data in plain-text JSON
	const    CIPHER_AESCBC256        = 2;    // Data in AES-256 standard (CBC) mode encrypted JSON

	private    $json_errors = array(
	'JSON_ERROR_NONE' => 'No error has occurred (probably emtpy data passed)',
	'JSON_ERROR_DEPTH' => 'The maximum stack depth has been exceeded',
	'JSON_ERROR_CTRL_CHAR' => 'Control character error, possibly incorrectly encoded',
	'JSON_ERROR_SYNTAX' => 'Syntax error'
	);

		// Inicializamos las variables
	private    $status = 200;  // Estado de la petici�n
	private $cipher = 2;    // M�todo usado para cifrar los datos
	private $clear_data = '';        // Datos enviados en la petici�n del cliente (ya en claro)
	public $data = '';        // Datos devueltos al cliente
	private $password = null;
	private $method_name = null;
	private $log_buffer = '******* Start of file ******* </br>';    // Buffer para almacenar el continido del fichero de logs
	private $createfolder = false;    // �Se ha creado el directorio para guardar los resultados?
	private $remote_site = '';
	private $same_branch = true;    // �Pertenecen los dos sitios a la misma versi�n de Joomla?
	private $stored_filename = '';    // Fichero remoto descargado
	private $database_name = '';    // Nombre del fichero .out
	private $maintain_db_structure = 0;    // Indica si hemos de mantener la estructura (establecida en configuration.php) del sitio local
	private $database_prefix = null;    // Prefijo de la BBDD local, necesaria si hemos de mantener la estructura de la BBDD local
	private $remote_database_prefix = null;    // Prefijo de la BBDD remota, necesaria si hemos de mantener la estructura de la BBDD local
	private $delete_existing_db = 0;    // Indica si hemos de borrar la BBDD local (aplicable s�lo si no hemos de mantener la estructura del sitio)
	private $cipher_file = 0;    // Indica si el fichero remoto est� cifrado
	private $backupinfo = array('product' => '', 'latest' => '', 'latest_status' => '', 'latest_type' => '');
	private $update_database_plugin_needs_update = 0;   // Indica si el plugin 'Update Database' necesita actualizarse
	private $info = null;  // Contendr� informaci�n sobre el sistema: versi�n de php, mysql y servidor
	private $site = null;  // Contendr� la url a la que hemos de devolver el callback
	private $site_id = null;  // Contendr� la id de la web en Control Center
	public $log_filename = '';    // Nombre del fichero de logs
	// Establecemos la ruta donde se almacenar�n los escaneos
    private $folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans';
	private $array_result = array(); // Contendr� el resultado de las actualizaciones
	
	public function register_task($json)
	{
		$task_checker_enabled = $this->PluginStatus(9);
		
		if ($task_checker_enabled == 0)
		{
			return "Error: task checker plugin is disabled"; 
		} else
		{
		
			$db = $this->getDbo();	
					
			$object = (object)array(
			'storage_key'        => 'remote_task',
			'storage_value'        => $json
			);				
			
			try 
			{
				$result = $db->insertObject('#__securitycheckpro_storage', $object);            
			} catch (\Throwable $e)
			{    			
				$this->log_filename = "error.php";
				$message = $e->getMessage();
				$this->write_log($message,"ERROR");
				return "Error:" . $message; 
			}
			
			// Launch the 'onSCPTaskAdded', observed by the Securitycheckpro_task_checker plugin
            $event = AbstractEvent::create(
                'onSCPTaskAdded',
                [
					'subject'   => $this,
                ]
            );

            Factory::getApplication()->getDispatcher()->dispatch('onSCPTaskAdded', $event);
			
			return "Task added";
		}
	}
	
	// Funci�n que realiza una determinada funci�n seg�n los par�metros especificados en la variable pasada como argumento
	public function execute($json)
	{
		$db = Factory::getDBO();
		$query = "DELETE FROM #__securitycheckpro_storage WHERE storage_key='remote_task'";
		$db->setQuery($query);
		$db->execute();
						
		// Decodificamos el string json
		$json_trimmed = rtrim($json, chr(0));
		
		// Comprobamos que el string JSON es v�lido y que tiene al menos 12 caracteres (longitud m�nima de un mensaje v�lido)
		if ((strlen($json_trimmed) < 12) || (substr($json_trimmed, 0, 1) != '{') || (substr($json_trimmed, -1) != '}'))
		{
			// El string JSON no es v�lido, no podemos hacer nada ya que no sabemos a qu� direcci�n devolver la petici�n
			$this->log_filename = "error.php";
			$message = "Function Execute. JSON not valid.";
			$this->write_log($message,"ERROR");
			return;
		}
		else
		{
			// Decodificamos la petici�n
			$request = json_decode($json, true);
		}	
								
		if (is_null($request))
		{
			// El string JSON no es v�lido, no podemos hacer nada ya que no sabemos a qu� direcci�n devolver la petici�n
			$this->log_filename = "error.php";
			$message = "Function Execute. JSON is null.";
			$this->write_log($message,"ERROR");
			return;
		}
		
		// Extraemos los par�metros necesarios para mandar las peticiones en caso de error		
		$this->cipher = $request['cipher'];
		// Site id
		$this->site_id = $request['body']['id'];
		if ( empty($this->site_id) )
		{
			// El site_id no es v�lido, no podemos hacer nada ya que no sabemos a qu� sitio devolver la petici�n
			return;
		}
		
		// Comprobamos si el frontend est� habilitado
		$config = $this->Config('controlcenter');
		
		if (is_null($config))
		{
			// Vamos a usar el referrer como url a la que devolver la petici�n
			$this->site = $request['referrer'];
			$this->data = "Can't get configuration";
			$this->status = self::STATUS_ERROR;
			$this->cipher = self::CIPHER_RAW;
			
			$this->log_filename = "error.php";
			$message = "Function Execute. Can't get configuration.";
			$this->write_log($message,"ERROR");

			return $this->sendResponse();
		}

		if (!array_key_exists('control_center_enabled', $config))
		{
			$enabled = false;
		}
		else
		{
			$enabled = $config['control_center_enabled'];
		}

		if (array_key_exists('secret_key', $config))
		{
			$this->password = $config['secret_key'];
		}
		else
		{
			// Vamos a usar el referrer como url a la que devolver la petici�n
			$this->site = $request['referrer'];
			$this->data = 'Remote password not configured';
			$this->status = self::STATUS_NOT_AUTH;
			$this->cipher = self::CIPHER_RAW;
			
			$this->log_filename = "error.php";
			$message = "Function Execute. Remote password not configured.";
			$this->write_log($message,"ERROR");

			return $this->sendResponse();
		}
				
		// Si el frontend no est� habilitado, devolvemos un error 503
		if (!$enabled)
		{
			// Vamos a usar el referrer como url a la que devolver la petici�n
			$this->site = $request['referrer'];
			$this->data = 'Access denied';
			$this->status = self::STATUS_NOT_AVAILABLE;
			$this->cipher = self::CIPHER_RAW;
						
			$this->log_filename = "error.php";
			$message = "Function Execute. Frontend disabled.";
			$this->write_log($message,"ERROR");

			return $this->sendResponse();
		}
		
		
		// Site to return the callback to; let's decypher it
		if ( !empty($request['body']['site']) )
		{
			$this->site = $request['body']['site'];				
			$this->site = $this->decrypt($this->site, $this->password);
									
			if ( (empty($this->site)) || (strstr($this->site,"Internal") !== false ) )
			{
				if ( empty($this->site) ){
					$this->data = 'Error decrypting data. Are both secret keys equals?';
				} else 
				{
					$this->data = $this->site . '. Are both secret keys equals?';
					$this->log_filename = "error.php";
					$message = "Getting site error. Error decrypting data. Are both secret keys equals?";
					$this->write_log($message,"ERROR");
				}
				// Vamos a usar el referrer como url a la que devolver la petici�n
				if ( (array_key_exists('referrer',$request)) && (!empty($request['referrer'])) ) 
				{
					$this->site = $request['referrer'];
					$this->status = self::STATUS_ERROR;
					$this->cipher = self::CIPHER_RAW;				
										
					return $this->sendResponse();
				}
			}
				
		} else
		{
			$this->log_filename = "error.php";
			$message = "Function Execute. Error decrypting data. Are both secret keys equals?";
			$this->write_log($message,"ERROR");
			
			if ( (array_key_exists('referrer',$request)) && (!empty($request['referrer'])) ) 
			{
				// Vamos a usar el referrer como url a la que devolver la petici�n
				$this->site = $request['referrer'];
				
				$this->data = 'Error decrypting data. Are both secret keys equals?';
				$this->status = self::STATUS_ERROR;
				$this->cipher = self::CIPHER_RAW;				
										
				return $this->sendResponse();
			}
		}			
		
		
					
		// Decodificamos el 'body' de la petici�n
		if (isset($request['cipher']) && isset($request['body']))
		{
			switch ($request['cipher'])
			{
				case self::CIPHER_RAW:
					if (($request['body']['task'] == "getStatus") || ($request['body']['task'] == "checkVuln") || ($request['body']['task'] == "checkLogs") || ($request['body']['task'] == "checkPermissions") || ($request['body']['task'] == "checkIntegrity") || ($request['body']['task'] == "deleteBlocked") || ($request['body']['task'] == "checkmalware") || ($request['body']['task'] == "UpdateExtension") || ($request['body']['task'] == "Backup") || ($request['body']['task'] == "unlocktables") || ($request['body']['task'] == "locktables") || ($request['body']['task'] == "server_statistics") || ($request['body']['task'] == "enable_analytics") || ($request['body']['task'] == "disable_analytics"))
					{
						/* Los resultados de todas las tareas se devuelven cifrados; si recibimos una petici�n para devolverlos sin cifrar, la rechazamos
						porque ser� fraudulenta */
						$this->data = 'Go away, hacker!';
						$this->status = self::STATUS_NOT_ALLOWED;
						$this->cipher = self::CIPHER_RAW;

						return $this->sendResponse();
					}
				break;				

				case self::CIPHER_AESCBC256:
					if (!is_null($request['body']['data']))
					{
						// $this->clear_data = $this->mc_decrypt_256($request->body->data, $this->password);
					}
				break;
			}	
				
			// Let's update the url from which we have received the task and prepare the log file
			try
			{
				$params = ComponentHelper::getParams('com_securitycheckpro');
				$max_log_size = $params->get('controlcenter_log_size', 2048);
				$cc_config = $this->Config('controlcenter');
				$cc_config['control_center_url'] = $this->site;
				$this->SaveStorageParams($cc_config,'controlcenter');			
								
				$filemanager_model = new FilemanagerModel();
				$this->log_filename = $filemanager_model->get_log_filename("controlcenter_log", true);
				if (empty($this->log_filename)) {
					$this->log_filename = $filemanager_model->prepareLog("controlcenter",true);					
				} else if ( (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->log_filename)) && (filesize($this->folder_path.DIRECTORY_SEPARATOR.$this->log_filename) > ($max_log_size * 1024)) ) {
					//Rotate log file
					File::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->log_filename);
					$this->log_filename = $filemanager_model->prepareLog("controlcenter",true);
				}	
				
			} catch (Exception $e)
			{
				$this->log_filename = "error.php";
				$message = "Function Execute. " . $e->getMessage();
				$this->write_log($message,"ERROR");				
			} 			
			
						
			switch ($request['body']['task'])
			{
				case "getStatus":
					$this->getStatus();
					break;

				case "checkVuln":
					$this->checkVuln();
					break;

				case "checkLogs":
					$this->checkLogs();
					break;

				case "checkPermissions":
					$this->checkPermissions();
					break;

				case "checkIntegrity":
					$this->checkIntegrity();
					break;

				case "deleteBlocked":
					$this->deleteBlocked();
					break;

				case "checkmalware":
					$this->checkMalware();
					break;

				case "UpdateComponent":
					$this->UpdateComponent();
					break;

				case "UpdateExtension":
					$this->UpdateExtension($request['body']['data']);
					break;

				case "Backup":
					$this->Backup($request['body']['data']);
					break;

				case "Uploadinstall":
					$this->Upload_install($request['body']['data']);
					break;

				case "Connect":
					$this->Connect();
					break;

				case "UpdateConnect":					
					$this->UpdateConnect($request['body']['data']);
					break;

				case "unlocktables":
					$this->write_log("UNLOCKTABLES task received");
					$this->unlocktables();
					break;

				case "locktables":
					$this->locktables();
					break;

				case "server_statistics":
					$this->server_statistics();
					break;
					
				case "enable_analytics":
					$this->write_log("ENABLE_ANALYTICS task received");
					$this->enable_analytics($request['body']['data']);
					break;
					
				case "disable_analytics":
					$this->write_log("DISABLE_ANALYTICS task received");
					$this->disable_analytics($request['body']['data']);
					break;

				case self::CIPHER_AESCBC256:
					break;
					
				default:
					$this->data = 'Method not configured';
					$this->status = self::STATUS_NOT_FOUND;
					$this->cipher = self::CIPHER_RAW;
					return $this->sendResponse();
			}

			return $this->sendResponse();
		}
	}

		// Funci�n que empaqueta una respuesta en formato JSON codificado, cifrando los datos si es necesario

	public function sendResponse($connect_back_url=null)
	{
		
		if ( !is_null($connect_back_url) ) {
			$this->cipher = self::CIPHER_RAW;			
		}
		
		// Inicializamos la respuesta
		$response = array(
			'cipher'    => $this->cipher,
			'body'        => array(
				'status'        => $this->status,
				'data'            => null,
				'id'            => $this->site_id
			)
		);
		
		
		// Codificamos los datos enviados en formato JSON
		$data = json_encode($this->data);
		
		$this->write_log("Sending response. Data: " . $data);		
				
		// Ciframos o no los datos seg�n el m�todo establecido en la petici�n
		switch ($this->cipher)
		{
			case self::CIPHER_RAW:
			break;		

			case self::CIPHER_AESCBC256:
				$data = $this->encrypt($data, $this->password);
			break;
		}

		// Guardamos los datos...
		$response['body']['data'] = $data;
		
		$response = json_encode($response);
		
		// If 'connect_back_url' is not empty will contain the url to which return the result. Used in the "Connect" task
		if (!empty($connect_back_url)) {
			$this->site = $connect_back_url;
		}
		
		//Get the token
		$token = '';
			
		$cc_config = $this->getControlCenterConfig();
		if ( (is_array($cc_config)) && (array_key_exists('token',$cc_config)) ) {
			$token = $cc_config['token'];
		}
		
		if (empty($token)) {
			$this->log_filename = "error.php";
			$message = "Can't send the reply to the Control Center. Token is empty or doesn't match with Control Center.";
			$this->write_log($message,"ERROR");
			return;
		}
		
		$headers = [
			'Token: ' . $token
		];
								
		// ... y los devolvemos al cliente
		$ch = curl_init($this->site . "index.php?option=com_securitycheckprocontrolcenter&view=json&format=raw&json=" . urlencode($response));
		
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Esto es importante para seguir las redirecciones; si est� a false no podremos seguirlas
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);	
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, SCP_USER_AGENT);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);	
			
		$response = curl_exec($ch);
		
		$this->write_log("Response sent to " . $this->site);
		if ($response === false) {
			$message = curl_error($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);		
			$this->write_log("RESPONSE: Error " . $httpcode . " " . $message);	
		} else {
			$this->write_log("Curl reply " . $response);
		}
	}

	// Extraemos los par�metros del componente
	private function Config($key_name)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote($key_name));
		$db->setQuery($query);
		$res = $db->loadResult();
		$res = json_decode($res, true);

		return $res;
	}
	
	// Guardamos los par�metros del componente
	private function SaveStorageParams($params,$key_name)
	{
		$db = Factory::getDBO();
		
		$storage_value = json_encode($params);
		// Instanciamos un objeto para almacenar los datos que ser�n sobreescritos/a�adidos
        $object = new \StdClass();                    
        $object->storage_key = $key_name;
        $object->storage_value = $storage_value;
		
		try {
			$db->updateObject('#__securitycheckpro_storage', $object, 'storage_key');
		} catch (Exception $e)
		{
			$this->log_filename = "error.php";
			$message = "Function SaveStorageParams. " . $e->getMessage();
			$this->write_log($message,"ERROR");
		} 		
	}
	
	/* Devuelve una fecha datetime usando el offset establecido en Joomla */
	public function get_Joomla_timestamp()
	{
		// Obtenemos el timezone de Joomla y sobre esa informaci�n calculamos el timestamp
		$config = Factory::getConfig();
		$offset = $config->get('offset');
						
		if (empty($offset))
		{
			$offset = 'UTC';
		}
		
		$date = new \DateTime("now", new \DateTimeZone($offset) );
		$timestamp_joomla_timezone = $date->format('Y-m-d H:i:s');
			
		return $timestamp_joomla_timezone;
	}
	
	/* Crea un log de una tarea lanzada */
    function write_log($message,$level="INFO")
    {
		$fp2 = @fopen($this->folder_path.DIRECTORY_SEPARATOR.$this->log_filename, 'ab');		
		
		if (empty($fp2)) {
            return;
        }
	
		$string = $level . "    |   ";
		$timestamp = $this->get_Joomla_timestamp();
		$string .= $timestamp . "   |   $message |\r\n";	

		@fwrite($fp2, $string);
		@fclose($fp2);
    }
	

	// Funci�n que verifica una fecha
	public function verifyDate($date, $strict = true)
	{
		$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date);

		if ($strict)
		{
			$errors = \DateTime::getLastErrors();

			if (!empty($errors['warning_count']))
			{
				return false;
			}
		}

		return $dateTime !== false;
	}

	// Funci�n que devuelve el estado de la extensi�n remota

	public function getStatus($opcion=true)
	{
		
		$this->write_log("Launching GETSTATUS task");
				
		// Inicializamos las variables
		$extension_updates = null;
		$installed_version = "0.0.0";
		$hasUpdates = 0;
		$today_logs = 0;
		$updates_to_send = '';
		$remote_data = array();

		$db = Factory::getDBO();

		// Buscamos la versi�n de SCP instalada
		$query = $db->getQuery(true)
			->select($db->quoteName('manifest_cache'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('name') . ' = ' . $db->quote('Securitycheck Pro'));
		$db->setQuery($query);
		$result = $db->loadResult();
		$manifest = json_decode($result);
		$installed_version = isset($manifest->version) ? $manifest->version : "0.0.0";
		
		$this->write_log("Importing models...");
				
		
		$cpanel_model = new CpanelModel();
		$filemanager_model = new FileManagerModel();
		$update_model = new DatabaseupdatesModel();
				
		if ((empty($cpanel_model)) || (empty($filemanager_model)) || (empty($update_model)))
		{
			$this->write_log("Error retreiving external models","ERROR");
			return;
		}

		$this->write_log("Getting update database plugin status...");
		// Comprobamos el estado del plugin Update Database
		$update_database_plugin_installed = $update_model->PluginStatus(4);
		$update_database_plugin_version = $update_model->get_database_version();
		$update_database_plugin_last_check = $update_model->last_check();
		
		$this->write_log("Checking vulnerable extensions...");
		// Vulnerable components
		$db = Factory::getDBO();
		$query = "SELECT COUNT(*) FROM #__securitycheckpro WHERE Vulnerable='Si'";
		$db->setQuery($query);
		$db->execute();
		$vuln_extensions = $db->loadResult();
		
		$this->write_log("Checking unread logs...");
		// Check for unread logs
		(int) $logs_pending = $cpanel_model->LogsPending();		
				
		$this->write_log("Getting info from permissions, integrity and malware scan...");
		// Get files with incorrect permissions from database
		$files_with_incorrect_permissions = $filemanager_model->loadStack("filemanager_resume", "files_with_incorrect_permissions");

		// If permissions task has not been launched, we set a '0' value.
		if (is_null($files_with_incorrect_permissions))
		{
			$files_with_incorrect_permissions = 0;
		}

		// FileManager last check
		$last_check = $filemanager_model->loadStack("filemanager_resume", "last_check");

		// Get files with incorrect integrity from database
		$files_with_bad_integrity = $filemanager_model->loadStack("fileintegrity_resume", "files_with_bad_integrity");

		// If permissions task has not been launched, whe set a '0' value.
		if (is_null($files_with_bad_integrity))
		{
			$files_with_bad_integrity = 0;
		}

		// FileIntegrity last check
		$last_check_integrity = $filemanager_model->loadStack("fileintegrity_resume", "last_check_integrity");

		// Malwarescan last check
		$last_check_malwarescan = $filemanager_model->loadStack("malwarescan_resume", "last_check_malwarescan");

		// Get suspicious files
		$suspicious_files = $filemanager_model->loadStack("malwarescan_resume", "suspicious_files");

		// �ltima optimizaci�n bbdd
		$last_check_database_optimization = $this->get_campo_filemanager('last_check_database');

		// If malwarescan has not been launched, we set a '0' value.
		if (is_null($suspicious_files))
		{
			$suspicious_files = 0;
		}
		
		$this->write_log("Getting backup info...");
		// Comprobamos el estado del backup
		$this->getBackupInfo();

		// Verificamos si el core est� actualizado (obviando la cach�)
		$updatemodel = new UpdateModel;
		
		$updatemodel->refreshUpdates(true);
		$coreInformation = $updatemodel->getUpdateInformation();

		// Si el plugin 'Update Batabase' est� instalado, comprobamos si est� actualizado
		if ($update_database_plugin_installed)
		{
			$this->update_database_plugin_needs_update = $this->checkforUpdate();
		}
		else
		{
			$this->update_database_plugin_needs_update = 0;
		}
		
		$this->write_log("Getting system info...");
		// A�adimos la informaci�n del sistema
		$this->getInfo();
		
		$this->write_log("Getting htaccess protection config...");
		// Obtenemos las opciones de protecci�n .htaccess
		$ConfigApplied = new ProtectionModel();
		$ConfigApplied = $ConfigApplied->GetConfigApplied();

		// Si el directorio de administraci�n est� protegido con contrase�a, marcamos la opci�n de protecci�n del backend como habilitada
		if (!$ConfigApplied['hide_backend_url'])
		{
			if (file_exists(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . '.htpasswd'))
			{
				$ConfigApplied['hide_backend_url'] = '1';
			}
		}

		// Si se ha seleccionado la opci�n de "backend protected using other options" ponemos "hide_backend_url" como enable porque esta opci�n marca si el backend est� habilitado
		if ($ConfigApplied['backend_protection_applied'] == 1)
		{
			$ConfigApplied['hide_backend_url'] = '1';
		}
		
		$this->write_log("Getting firewall config...");
		// Obtenemos los par�metros del Firewall
		$FirewallOptions = new BaseModel;
		$FirewallOptions = $FirewallOptions->getConfig();
				
		$this->write_log("Checking if kickstart exists...");
		// Chequeamos si existe el fichero kickstart
		$kickstart = $this->check_kickstart();

		$this->write_log("Getting 2FA status...");
		// Chequeamos si el segundo factor de autenticaci�n est� habilitado
		$two_factor = $this->get_two_factor_status(true);

		$this->write_log("Getting info about outdated extensions...");
		// A�adimos la informaci�n sobre las extensiones no actualizadas. Esta opci�n no es necesaria cuando escogemos la opci�n 'System Info'
		if ($opcion)
		{
			$extension_updates = $this->getNotUpdatedExtensions();
			$outdated_extensions = json_decode($extension_updates, true);
			$sc_to_find = "Securitycheck Pro";
			$key_sc = array_search($sc_to_find, array_column($outdated_extensions, 2));

			if ($key_sc !== false)
			{
				$installed_version = $outdated_extensions[$key_sc][4];
				$hasUpdates = 1;
			}
		}

		// Si no hay backup establecemos la fecha actual para evitar un error en la bbdd al insertar el valor
		$is_valid_date = $this->verifyDate($this->backupinfo['latest']);

		if (!$is_valid_date)
		{
			$this->backupinfo['latest'] = "0000-00-00 00:00:00";
		}
		
		$this->write_log("Getting lock tables status...");
		// Chequeamos si las tablas est�n bloqueadas
		$tables_locked = $this->check_locked_tables();
		
		$this->write_log("Getting attacks stopped today...");
		// Informaci�n sobre el n�mero de logs del d�a
		try 
        {
            $query = "SELECT COUNT(*) from #__securitycheckpro_logs WHERE DATE(time) = CURDATE()";            
            $db->setQuery($query);
            $today_logs= $db->loadResult();
        } catch (\Throwable $e)
        {            
        }  
		
		$this->write_log("Getting info about updates...");
		// Informaci�n sobre las extensiones/core actualizadas
		try 
        {
			$query = 'SELECT storage_value FROM #__securitycheckpro_storage WHERE storage_key="installs_remote"';
			$db->setQuery($query);
			$db->execute();
			$updates_to_send = $db->loadResult();                 
        } catch (\Throwable $e)
        {            
        } 
		
		if ( ($today_logs > 0) || (!empty($updates_to_send)) ) {
			if ($today_logs > 0) {
				$remote_data['logs'] = $today_logs;
			}
			if (!empty($updates_to_send)){
				$remote_data['updates'] = $updates_to_send;
				// Borramos la informaci�n de la tabla "installs_remote"
				try 
				{
					$query = "DELETE FROM #__securitycheckpro_storage WHERE storage_key='installs_remote'";
					$db->setQuery($query);
					$db->execute();                
				} catch (\Throwable $e)
				{            
					$this->log_filename = "error.php";
					$message = "Error deleting info from installs_remote table. Error: " . $e->getMessage();
					$this->write_log($message,"ERROR");
				} 				
			}			
			$remote_data = json_encode($remote_data);
		} else {
			$remote_data = null;
		}
		
		$this->data = array(
			'vuln_extensions'        => $vuln_extensions,
			'logs_pending'    => $logs_pending,
			'files_with_incorrect_permissions'        => $files_with_incorrect_permissions,
			'last_check' => $last_check,
			'files_with_bad_integrity'        => $files_with_bad_integrity,
			'last_check_integrity' => $last_check_integrity,
			'installed_version'    => $installed_version,
			'hasUpdates'    => $hasUpdates,
			'coreinstalled'    => $coreInformation['installed'],
			'corelatest'    => $coreInformation['latest'],
			'last_check_malwarescan' => $last_check_malwarescan,
			'suspicious_files'        => $suspicious_files,
			'update_database_plugin_installed'    => $update_database_plugin_installed,
			'update_database_plugin_version'    => $update_database_plugin_version,
			'update_database_plugin_last_check'    => $update_database_plugin_last_check,
			'update_database_plugin_needs_update'    => $this->update_database_plugin_needs_update,
			'backup_info_product'    => $this->backupinfo['product'],
			'backup_info_latest'    => $this->backupinfo['latest'],
			'backup_info_latest_status'    => $this->backupinfo['latest_status'],
			'backup_info_latest_type'    => $this->backupinfo['latest_type'],
			'php_version'    => $this->info['phpversion'],
			'database_version'    => $this->info['dbversion'],
			'web_server'    => $this->info['server'],
			'extension_updates'    => $extension_updates,
			'last_check_database_optimization'    => $last_check_database_optimization,
			'overall'    => 200,
			'twofactor_enabled'    => $two_factor,
			'backend_protection'    => $ConfigApplied['hide_backend_url'],
			'forbid_new_admins'        => $FirewallOptions['forbid_new_admins'],
			'kickstart_exists'    => $kickstart,
			'tables_blocked'    => $tables_locked,
			'remote_data'	=>	$remote_data
		);

		// Obtenemos el porcentaje para 'Overall security status'
		$overall = $this->getOverall($this->data);
		$this->data['overall'] = $overall;
		
		$this->write_log("GETSTATUS task finished");

	}

	// Chequea si la opci�n "Lock tables" est� habilitada
	function check_locked_tables()
	{
		$locked = false;

		try
		{
			$db = $this->getDbo();
			$query = 'SELECT storage_value FROM #__securitycheckpro_storage WHERE storage_key="locked"';
			$db->setQuery($query);
			$db->execute();
			$locked = $db->loadResult();
		}
		catch (Exception $e)
		{
			$this->log_filename = "error.php";
			$message = "Function check_locked_tables. " . $e->getMessage();
			$this->write_log($message,"ERROR");
			return 0;
		}

		return $locked;
	}

	// Chequea si el fichero kickstart.php existe en la ra�z del sitio. Esto sucede cuando se restaura un sitio y se olvida (junto con alg�n backup) eliminarlo.
	function check_kickstart()
	{
		$found = false;
		$akeeba_kickstart_file = JPATH_ROOT . DIRECTORY_SEPARATOR . "kickstart.php";

		if (file_exists($akeeba_kickstart_file))
		{
			if (strpos(file_get_contents($akeeba_kickstart_file), "AKEEBA") !== false)
			{
				$found = true;
			}
		}

		return $found;

	}

	// Obtiene el estado del segundo factor de autenticaci�n de Joomla (Google y Yubikey)
	function get_two_factor_status($overall=false)
	{
		$enabled = 0;
		$mfa = 0;

		// Si la variable "overall" es false utilizamos el m�todo getTwoFactorMethods para obtener la informaci�n de los plugins; si es true no podemos usar ese m�todo ya que necesitamos que el usuario est� logado

		if (!$overall)
		{
			$methods = AuthenticationHelper::getTwoFactorMethods();
			
			if (empty($methods)) {
				// The 'getTwoFactorMethods' will be deprecated since 4.2.0. Let's use the new method
				$methods = \Joomla\Component\Users\Administrator\Helper\Mfa::getUserMfaRecords(0);	
				foreach ($methods as $user_method)
				{
					if ( ($user_method->method == 'totp') || ($user_method->method == 'yubikey') )
					{
						$mfa = 1;
						break;
					}						
				}
			}
			
			if (count($methods) > 1)
			{
				if ($mfa == 1) {
					return 2;
				}
				$enabled = 1;

				// Chequeamos que al menos un Super usuario tenga el m�todo habilitado
				try
				{
					$db = Factory::getDBO();
					$query = 'SELECT user_id FROM #__user_usergroup_map WHERE group_id="8"';
					$db->setQuery($query);
					$db->execute();
					$super_users_ids = $db->loadColumn();
				}
				catch (Exception $e)
				{
					$this->log_filename = "error.php";
					$message = "Function get_two_factor_status. " . $e->getMessage();
					$this->write_log($message,"ERROR");
					return 1;
				}

				$model = new UserModel(array('ignore_request' => true));
				

				foreach ($super_users_ids as $user_id)
				{
					$otpConfig = $model->getOtpConfig($user_id);

					// Check if the user has enabled two factor authentication
					if (!empty($otpConfig->method) && !($otpConfig->method === 'none'))
					{
						$enabled = 2;
					}
				}
			} 
		}
		else
		{
			if (version_compare(JVERSION, '4.2.0', 'gt')) {
				try
				{
					$db = Factory::getDBO();
					$query = 'SELECT COUNT(*) FROM #__extensions WHERE type="plugin" and folder="multifactorauth" and enabled="1"';
					$db->setQuery($query);
					$db->execute();
					(int) $mfa_plugins_enabled = $db->loadResult();
					if ($mfa_plugins_enabled >= 1){
						return 1;
					} else {
						return 0;
					}
					
				}
				catch (Exception $e)
				{
					$this->log_filename = "error.php";
					$message = "Function get_two_factor_status - J4. " . $e->getMessage();
					$this->write_log($message,"ERROR");
				}
			}		
			
			try
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true)
					->select(array($db->quoteName('enabled')))
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('name') . ' = ' . $db->quote('plg_twofactorauth_totp'));
				$db->setQuery($query);
				$enabled = $db->loadResult();
			}
			catch (Exception $e)
			{
				$this->log_filename = "error.php";
				$message = "Function get_two_factor_status - second else condition. " . $e->getMessage();
				$this->write_log($message,"ERROR");
			}

			if ($enabled == 0)
			{
				try
				{
					$query = $db->getQuery(true)
						->select(array($db->quoteName('enabled')))
						->from($db->quoteName('#__extensions'))
						->where($db->quoteName('name') . ' = ' . $db->quote('plg_twofactorauth_yubikey'));
					$db->setQuery($query);
					$enabled = $db->loadResult();
				}
				catch (Exception $e)
				{
					$this->log_filename = "error.php";
					$message = "Function get_two_factor_status - third condition. " . $e->getMessage();
					$this->write_log($message,"ERROR");
				}
			}
		}

		return $enabled;
	}

		// Obtiene el porcentaje general de cada una de las barras de progreso
	function getOverall($info)
	{
		// Inicializamos variables
		$overall = 0;

		if ($info['kickstart_exists'])
		{
			return 2;
		}

		if (version_compare($info['coreinstalled'], $info['corelatest'], '=='))
		{
			$overall = $overall + 10;
		}
		
		if ($info['logs_pending'] <= 10)
		{
			$overall = $overall + 5;
		}

		if ($info['files_with_incorrect_permissions'] == 0)
		{
			$overall = $overall + 5;
		}

		if ($info['files_with_bad_integrity'] == 0)
		{
			$overall = $overall + 10;
		}

		if ($info['vuln_extensions'] == 0)
		{
			$overall = $overall + 30;
		}

		if ($info['suspicious_files'] == 0)
		{
			$overall = $overall + 15;
		}

		if ($info['backend_protection'])
		{
			$overall = $overall + 10;
		}

		if ($info['forbid_new_admins'] == 1)
		{
			$overall = $overall + 5;
		}

		if ($info['twofactor_enabled'] >= 1)
		{
			$overall = $overall + 10;
		}

		return $overall;
	}

		// Funci�n que comprueba si existen extensiones vulnerables

	private function checkVuln()
	{
		$this->write_log("Launching CHECKVULN task");
		
		$this->write_log("Getting models...");
		// Import Securitycheckpros model		
		$securitycheckpros_model = new SecuritycheckproModel();
		$update_model = new DatabaseupdatesModel();
				
		$this->write_log("Looking for updates...");
		// Comprobamos si existen nuevas actualizaciones
		$result = $update_model->tarea_comprobacion();

		// Comprobamos el estado del plugin Update Database
		$update_database_plugin_installed = $update_model->PluginStatus(4);
		$update_database_plugin_version = $update_model->get_database_version();
		$update_database_plugin_last_check = $update_model->last_check();
		
		$this->write_log("Looking for vulnerable extensions...");
		// Hacemos una nueva comprobaci�n de extensiones vulnerables
		$securitycheckpros_model->chequear_vulnerabilidades();

		// Vulnerable components
		$db = Factory::getDBO();
		$query = 'SELECT COUNT(*) FROM #__securitycheckpro WHERE Vulnerable="Si"';
		$db->setQuery($query);
		$db->execute();
		$vuln_extensions = $db->loadResult();

		$this->data = array(
		'vuln_extensions'        => $vuln_extensions,
		'update_database_plugin_installed'    => $update_database_plugin_installed,
		'update_database_plugin_version'    => $update_database_plugin_version,
		'update_database_plugin_last_check'    => $update_database_plugin_last_check
		);
		
		$this->write_log("CHECKVULN task finished");
	}

		// Funci�n que comprueba si existen logs por leer

	private function checkLogs()
	{
		$this->write_log("Launching CHECKLOGS task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$cpanel_model = new CpanelModel();;
		
		$this->write_log("Checking unread logs...");
		// Check for unread logs
		(int) $logs_pending = $cpanel_model->LogsPending();

		$this->data = array(
		'logs_pending'    => $logs_pending
		);
		
		$this->write_log("CHECKLOGS task finished");

	}

	// Funci�n que lanza un chequeo de permisos
	private function checkPermissions()
	{
		$this->write_log("Launching CHECKPERMISSIONS task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$filemanager_model = new FilemanagerModel();
		
		$this->write_log("Launching permissions scan...");
		
		$filemanager_model->set_campo_filemanager('files_scanned', 0);
		$timestamp = $this->get_Joomla_timestamp();
		$filemanager_model->set_campo_filemanager('last_check', $timestamp);
		$filemanager_model->set_campo_filemanager('estado', 'IN_PROGRESS');
		$filemanager_model->scan("permissions");
		
		$this->write_log("Retrieving status...");
		
		// Get files with incorrect permissions from database
		$files_with_incorrect_permissions = $filemanager_model->loadStack("filemanager_resume", "files_with_incorrect_permissions");

		// If permissions task has not been launched, we set a '0' value.
		if (is_null($files_with_incorrect_permissions))
		{
			$files_with_incorrect_permissions = 0;
		}

		// FileManager last check
		$last_check = $filemanager_model->loadStack("filemanager_resume", "last_check");

		$this->data = array(
		'files_with_incorrect_permissions'        => $files_with_incorrect_permissions,
		'last_check' => $last_check
		);
		
		$this->write_log("CHECKPERMISSIONS task finished");

	}

		// Funci�n que lanza un chequeo de integridad

	private function checkIntegrity()
	{
		$this->write_log("Launching CHECKINTEGRITY task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$filemanager_model = new FilemanagerModel();
				
		$this->write_log("Launching integrity scan...");

		$filemanager_model->set_campo_filemanager('files_scanned_integrity', 0);
		$timestamp = $this->get_Joomla_timestamp();
		$filemanager_model->set_campo_filemanager('last_check_integrity', $timestamp);
		$filemanager_model->set_campo_filemanager('estado_integrity', 'IN_PROGRESS');
		$filemanager_model->scan("integrity");
		
		$this->write_log("Retrieving status...");

		// Get files with incorrect permissions from database
		$files_with_bad_integrity = $filemanager_model->loadStack("fileintegrity_resume", "files_with_bad_integrity");

		// If permissions task has not been launched, we set a '0' value.
		if (is_null($files_with_bad_integrity))
		{
			$files_with_bad_integrity = 0;
		}

		// FileIntegrity last check
		$last_check_integrity = $filemanager_model->loadStack("fileintegrity_resume", "last_check_integrity");

		$this->data = array(
		'files_with_bad_integrity'        => $files_with_bad_integrity,
		'last_check_integrity' => $last_check_integrity
		);
		
		$this->write_log("CHECKINTEGRITY task finished");

	}

	// Borra los logs pertenecientes a intentos de acceso bloqueados
	private function deleteBlocked()
	{
		$this->write_log("Launching DELETEBLOCKED task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$cpanel_model = new CpanelModel();
		
		// Vulnerable components
		$db = Factory::getDBO();
		$query = 'DELETE FROM #__securitycheckpro_logs';
		$db->setQuery($query);
		$db->execute();

		// Check for unread logs
		(int) $logs_pending = $cpanel_model->LogsPending();

		$this->data = array(
			'logs_pending'    => $logs_pending
		);
		
		$this->write_log("DELETEBLOCKED task finished");
	}

	

	// Obtiene informaci�n de los requisitos necesarios para clonar una web
	private function CheckPrereq()
	{

		// Inicializamos las variables
		$server_type = 0;  // Sistema operativo 'Linux'
		$safe_mode = 0;
		$mysqldump = null;
		$tar = null;

		/*
         Chequeamos los requisitos */
		// Tipo de servidor
		$os = php_uname("s");

		if (strstr($os, 'Windows'))
		{
			$server_type = 1;
		}
		elseif (strstr($os, 'Mac'))
		{
			$server_type = 2;
		}

		// 'Safe_mode'
		if (ini_get('safe_mode'))
		{
			$safe_mode = 1;
		}

		$this->data = array(
			'server_type'    => $server_type,
			'safe_mode'    => $safe_mode,
			'mysqldump'    => $mysqldump,
			'tar'    => $tar
		);

	}

	// Funci�n que actualiza el Core de Joomla a la �ltima versi�n disponible. Basado en /libraries/src/Console/UpdateCoreCommand.php
	private function UpdateCore()
	{
		$this->write_log("Updating CORE...");
		
		$old_version = JVERSION;
		$this->write_log("Old core version: " . $old_version);	
			
		// Cargamos el lenguaje del componente 'com_installer'
		$lang = Factory::getLanguage();
		$lang->load('com_installer', JPATH_ADMINISTRATOR);

		// Inicializamos la variable $result, que ser� un array con el resultado y el mensaje devuelto en el proceso
		$result = array();

		// Instanciamos el modelo
		$model = new UpdateModel;
		
		// Refrescamos la informaci�n de las actualizaciones ignorando la cach�
		$model->refreshUpdates(true);
		$this->write_log("Refreshing info...");

		// Extraemos la url de descarga
		$coreInformation = $model->getUpdateInformation();
		$this->write_log("Getting url to download the file...");		
								
		try
		{
			// Descargamos el archivo
			$file = $this->download_core($coreInformation['object']->downloadurl->_data);				
			
			// Extract the downloaded package file
			$config   = Factory::getConfig();
			$tmp_dest = $config->get('tmp_path');

			// Basado en /components/com_installer/src/Model/UpdateModel.php
			$package = InstallerHelper::unpack($tmp_dest . '/' . $file,true);
			
			if ( empty($package) ) {
				$this->log_filename = "error.php";
				$message = "Function UpdateCore. InstallerHelper.";
				$this->write_log($message,"ERROR");				
				$result[0][1] = $message;
				$result[0][0] = 2;
			} else {				
				// Uncompress and install the package
				$this->write_log("Uncompressing and installing the new version...");
				Folder::copy($package['extractdir'], JPATH_BASE, '', true);
				$install_result = $model->finaliseUpgrade();						
				if (!$install_result)
				{
					$msg = Text::_('COM_INSTALLER_MSG_UPDATE_ERROR');
					$result[0][1] = $msg;
					$result[0][0] = 2;
				}
				else
				{
					$result[0][1] = 'Core updated';
					$result[0][0] = 1;
					$this->write_log("CORE UPDATED successfully!");
					InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
					// Trigger event after joomla update.
					$app = Factory::getApplication();
					$app->triggerEvent('onJoomlaAfterUpdate',[$old_version]);										
					// Cargamos las librer�as necesarias					
					/*\JLoader::register('JNamespacePsr4Map', PATH_LIBRARIES . '/namespacemap.php');
					// Re-create namespace map. It is needed when updating to a Joomla! version has new extension added
					(new \JNamespacePsr4Map)->create();	*/
				}				
			}			
		}
		catch (Exception $e)
		{
			$this->log_filename = "error.php";
			$message = "Function UpdateCore. " . $e->getMessage();
			$this->write_log($message,"ERROR");
			$result[0][1] = $e->getMessage();
			$result[0][0] = 2;
		}
		
		// Devolvemos el resultado
		return $result;
	}
	
	

	/**
	 * Install an extension from either folder, url or upload.
	 *
	 * @return boolean result of install
	 *
	 * @since 1.5
	 */
	public function install($url)
	{
		$this->setState('action', 'install');

		// Set FTP credentials, if given.
		JClientHelper::setCredentialsFromRequest('ftp');
		$app = Factory::getApplication();

		// Load installer plugins for assistance if required:
		PluginHelper::importPlugin('installer');
		$dispatcher = \Factory::getApplication();

		$package = null;

		// This event allows an input pre-treatment, a custom pre-packing or custom installation (e.g. from a JSON�description)
		$results = $dispatcher->triggerEvent('onInstallerBeforeInstallation', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}
		elseif (in_array(false, $results, true))
		{
			return false;
		}

		$installType = 'url';

		if ($package === null)
		{
			switch ($installType)
			{
				case 'folder':
					// Remember the 'Install from Directory' path.
					$app->getUserStateFromRequest($this->_context . '.install_directory', 'install_directory');
					$package = $this->_getPackageFromFolder();
				break;

				case 'upload':
					$package = $this->_getPackageFromUpload();
				break;

				case 'url':
					$package = $this->_getPackageFromUrl($url);
				break;

				default:
					$app->setUserState('com_installer.message', Text::_('COM_INSTALLER_NO_INSTALL_TYPE_FOUND'));

				return false;
					break;
			}
		}

		// This event allows a custom installation of the package or a customization of the package:
		$results = $dispatcher->triggerEvent('onInstallerBeforeInstaller', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}
		elseif (in_array(false, $results, true))
		{
			if (in_array($installType, array('upload', 'url')))
			{
				//InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			}

			return false;
		}

		// Was the package unpacked?
		if (!$package || !$package['type'])
		{
			if (in_array($installType, array('upload', 'url')))
			{
				//InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			}

			$app->setUserState('com_installer.message', Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'));

			return false;
		}

		// Get an installer instance
		$installer = Installer::getInstance();

		// Install the package
		if (!$installer->install($package['dir']))
		{
			// There was an error installing the package
			$msg = Text::sprintf('COM_INSTALLER_INSTALL_ERROR', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result = false;
		}
		else
		{
			// Package installed sucessfully
			$msg = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result = true;
		}

		// This event allows a custom a post-flight:
		$dispatcher->triggerEvent('onInstallerAfterInstaller', array($this, &$package, $installer, &$result, &$msg));

		// Set some model state values
		$app    = Factory::getApplication();
		$app->enqueueMessage($msg);
		$this->setState('name', $installer->get('name'));
		$this->setState('result', $result);
		$app->setUserState('com_installer.message', $installer->message);
		$app->setUserState('com_installer.extension_message', $installer->get('extension_message'));
		$app->setUserState('com_installer.redirect_url', $installer->get('redirect_url'));

		// Cleanup the install files
		/*if (!is_file($package['packagefile']))
		{
			$config = Factory::getConfig();
			$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
		}

		InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);*/

		return $result;
	}


		/**
		 * Install an extension from a URL
		 *
		 * @return Package details or false on failure
		 *
		 * @since 1.5
		 */
	protected function _getPackageFromUrl($url)
	{
		$input = Factory::getApplication()->input;

		// Get the URL of the package to install
		// $url = $input->getString('install_url');

		// Did you give us a URL?
		if (!$url)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), 'warning');

			return false;
		}

		// Handle updater XML file case:
		if (preg_match('/\.xml\s*$/', $url))
		{
			$update = new Update;
			$update->loadFromXML($url);
			$package_url = trim($update->get('downloadurl', false)->_data);

			if ($package_url)
			{
				$url = $package_url;
			}

			unset($update);
		}

		// Download the package at the URL given
		$p_file = InstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), 'warning');

			return false;
		}

		$config   = Factory::getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file
		$package = InstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		return $package;
	}

		// Funci�n que lanza un chequeo en busca de malware

	private function checkMalware()
	{
		$this->write_log("Launching CHECKMALWARE task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$filemanager_model = new FilemanagerModel();
		
		$this->write_log("Launching malware scan...");
		
		$filemanager_model->set_campo_filemanager('files_scanned_malwarescan', 0);
		$timestamp = $this->get_Joomla_timestamp();
		$filemanager_model->set_campo_filemanager('last_check_malwarescan', $timestamp);
		$filemanager_model->set_campo_filemanager('estado_malwarescan', 'IN_PROGRESS');
		$filemanager_model->scan("malwarescan");
		
		$this->write_log("Retrieving info...");

		// Get suspicious files
		$suspicious_files = $filemanager_model->loadStack("malwarescan_resume", "suspicious_files");

		// If malwarescan task has not been launched, we set a '0' value.
		if (is_null($suspicious_files))
		{
			$suspicious_files = 0;
		}

		// Malwarescan last check
		$last_check_malwarescan = $filemanager_model->loadStack("malwarescan_resume", "last_check_malwarescan");

		$this->data = array(
			'suspicious_files'        => $suspicious_files,
			'last_check_malwarescan' => $last_check_malwarescan
		);
		
		$this->write_log("CHECKMALWARE task finished");

	}

		// Funci�n que obtiene informaci�n del estado del backup

	private function getBackupInfo()
	{

		// Instanciamos la consulta
		$db = Factory::getDBO();
		
		$joomla_version = "3";
		$query = "SELECT COUNT(*) FROM #__extensions WHERE element='com_akeeba'";		
		if (version_compare(JVERSION, '4.0', 'gt'))
		{
			$joomla_version = "4";
			$query = "SELECT COUNT(*) FROM #__extensions WHERE element='com_akeebabackup'";
		}		
		
		try {
			// Consultamos si Akeeba Backup est� instalado
			$db->setQuery($query);
			$db->execute();
			$akeeba_installed = $db->loadResult();			
		} catch (Exception $e)
        {    			
            $akeeba_installed = 0;
        }     
		

		if ($akeeba_installed == 1)
		{
			$this->backupinfo['product'] = 'Akeeba Backup';
			$this->AkeebaBackupInfo($joomla_version);
		}
		else
		{
			try {
				// Consultamos si Xcloner Backup and Restore est� instalado
				$query = 'SELECT COUNT(*) FROM #__extensions WHERE element="com_xcloner-backupandrestore"';
				$db->setQuery($query);
				$db->execute();
				$xcloner_installed = $db->loadResult();
			} catch (Exception $e)
			{    			
				$xcloner_installed = 0;
			} 			

			if ($xcloner_installed == 1)
			{
				$this->backupinfo['product'] = 'Xcloner - Backup and Restore';
				$this->XclonerbackupInfo();
			}
			else
			{
				// Consultamos si Easy Joomla Backup est� instalado
				$query = "SELECT COUNT(*) FROM #__extensions WHERE element='com_easyjoomlabackup'";
				$db->setQuery($query);
				$db->execute();
				$ejb_installed = $db->loadResult();

				if ($ejb_installed == 1)
				{
					$this->backupinfo['product'] = 'Easy Joomla Backup';
					$this->EjbInfo();
				}
			}
		}

	}

	// Funci�n que obtiene informaci�n del estado del �ltimo backup creado por Akeeba Backup
	private function AkeebaBackupInfo($joomla_version)
	{
		if ($joomla_version == "3") {
			$akeeba_database = "#__ak_stats";
		} else {
			$akeeba_database = "#__akeebabackup_backups";
		}
		
		// Instanciamos la consulta
		$db = Factory::getDBO();
		try{
			$query = $db->getQuery(true)
				->select('MAX(' . $db->qn('id') . ')')
				->from($db->qn('' . $akeeba_database . ''))
				->where($db->qn('origin') . ' != ' . $db->q('restorepoint'));
			$db->setQuery($query);
			$id = $db->loadResult();
		} catch (Exception $e)
		{
			$this->write_log("Error trying to get Akeeba database id: " . $e->getMessage(),"ERROR");
		}
			

		// Hay al menos un backup creado
		if (!empty($id))
		{
			try{
				$query = $db->getQuery(true)
					->select(array('*'))
					->from($db->quoteName('' . $akeeba_database .''))
					->where('id = ' . $id);
				$db->setQuery($query);
				$backup_statistics = $db->loadAssocList();
			} catch (Exception $e)
			{
				$this->write_log("Error trying to get Akeeba backup statistics: " . $e->getMessage(),"ERROR");
			}

			// Almacenamos el resultado
			$this->backupinfo['latest'] = $backup_statistics[0]['backupend'];
			$this->backupinfo['latest_status'] = $backup_statistics[0]['status'];
			$this->backupinfo['latest_type'] = $backup_statistics[0]['type'];
		}
		
		
	}

	// Funci�n que obtiene informaci�n del estado del �ltimo backup creado por Xcloner - Backup and Restore
	private function XclonerbackupInfo()
	{

		// Incluimos el fichero de configuraci�n de la extensi�n
		include JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . "components" . DIRECTORY_SEPARATOR . "com_xcloner-backupandrestore" . DIRECTORY_SEPARATOR . "cloner.config.php";

		// Extraemos el directorio donde se encuentran almacenados los backups...
		$backup_dir = $_CONFIG['clonerPath'];

				// ... y buscamos dentro los ficheros existentes, orden�ndolos por fecha
		$files_name = JFolder::files($backup_dir, '.', true, true);
		$files_name = array_combine($files_name, array_map("filemtime", $files_name));
		arsort($files_name);

		// El primer elemento del array ser� el que se ha creado el �ltimo. Formateamos la fecha para guardarlo en la BBDD.
		$latest_backup = date("Y-m-d H:i:s", filemtime(key($files_name)));

		// Almacenamos el resultado
		$this->backupinfo['latest'] = $latest_backup;
		$this->backupinfo['latest_status'] = 'complete';

	}

		// Funci�n que obtiene informaci�n del estado del �ltimo backup creado por Easy Joomla Backup

	private function EjbInfo()
	{

		// Instanciamos la consulta
		$db = Factory::getDBO();
		$query = $db->getQuery(true)
			->select('MAX(' . $db->qn('id') . ')')
			->from($db->qn('#__easyjoomlabackup'));
		$db->setQuery($query);
		$id = $db->loadResult();

		// Hay al menos un backup creado
		if (!empty($id))
		{
			$query = $db->getQuery(true)
				->select(array('*'))
				->from($db->quoteName('#__easyjoomlabackup'))
				->where('id = ' . $id);
			$db->setQuery($query);
			$backup_statistics = $db->loadAssocList();

									// Almacenamos el resultado
			$this->backupinfo['latest'] = $backup_statistics[0]['date'];
			$this->backupinfo['latest_status'] = 'complete';
			$this->backupinfo['latest_type'] = $backup_statistics[0]['type'];
		}

	}

		// Funci�n que indica si el plugin 'Update Database' est� actualizado

	private function checkforUpdate()
	{

		// Inicializmaos las variables
		$needs_update = 0;

		$db = Factory::getDBO();

		// Extraemos el id de la extensi�n..
		$query = 'SELECT extension_id FROM #__extensions WHERE name="System - Securitycheck Pro Update Database"';
		$db->setQuery($query);
		$db->execute();
		(int) $extension_id = $db->loadResult();

				// ... y hacemos una consulta a la tabla 'updates' para ver si el 'extension_id' figura como actualizable
		if (!empty($extension_id))
		{
			$query = "SELECT COUNT(*) FROM #__updates WHERE extension_id={$extension_id}";
			$db->setQuery($query);
			$db->execute();
			$result = $db->loadResult();

			if ($result == '1')
			{
				$needs_update = 1;
			}
		}

		// Devolvemos el resultado
		return $needs_update;

	}

	// Funci�n que actualiza el plugin 'Update Database'
	private function UpdateComponent()
	{
		
		$this->write_log("Launching UPDATECOMPONENT task");
		
		$this->write_log("Getting Securitycheck Pro Update Database update info");
		
		// Inicializamos las variables
		$needs_update = 1;
		
		$db = Factory::getDBO();

		// Extraemos el id de la extensi�n..
		$query = 'SELECT extension_id FROM #__extensions WHERE name="System - Securitycheck Pro Update Database"';
		$db->setQuery($query);
		$db->execute();
		(int) $extension_id = $db->loadResult();

		$query = "SELECT detailsurl FROM #__updates WHERE extension_id={$extension_id}";
		$db->setQuery($query);
		$db->execute();
		$detailsurl = $db->loadResult();

		// Instanciamos el objeto Update y cargamos los detalles de la actualizaci�n
		$update = new Update;
		$update->loadFromXML($detailsurl);
		
		$this->write_log("Passing data to the 'install_update method...");
		
		// Le pasamos a la funci�n de actualizaci�n el objeto con los detalles de la actualizaci�n
		$this->install_update($update);

		// Si la actualizaci�n ha tenido �xito, actualizamos la variable 'needs_update', que indica si el plugin necesita actualizarse.
		if ($this->array_result)
		{
			$needs_update = 0;
		}

		// Devolvemos el resultado
		$this->data = array(
			'update_plugin_needs_update' => $needs_update
		);
	}

	// Funci�n para actualizar los componentes. Extra�da del core de Joomla (administrator/components/com_installer/models/update.php | administrator\components\com_installer\src\Model\UpdateModel.php)
	private function install_update($update,$dlid=false)
	{
		$this->write_log("Installing update...");
		
								
		/* Cargamos el lenguaje del componente 'com_installer' */
		$lang = Factory::getLanguage();
		$lang->load('com_installer',JPATH_ADMINISTRATOR);
				
					
		// Inicializamos la variable $update_result, que ser� un array con el resultado y el mensaje devuelto en el proceso
		$update_result = array();
		$extension_name = '';
		$app = Factory::getApplication();
						
		if (isset($update->get('downloadurl')->_data)) {			
			$url = trim($update->downloadurl->_data);
			$extension_name = $update->get('name')->_data;
					
			if (!empty($dlid))
			{
				if ( is_array($dlid) ) {
					$this->write_log("Dlid is an array. Extracting values...");
					foreach($dlid as $key => $value) {
						$url .= (strpos($url, '?') === false) ? '?' : '&amp;';
						$url .= $key . '=' . $value;
						$this->write_log("Url: " . $url);
					}
				} else {
					$url .= (strpos($url, '?') === false) ? '?' : '&amp;';
					$url .= 'dlid=' . $dlid;
					$this->write_log("Url: " . $url);
				}
								
			}
			
				
		} else {
			$this->write_log(Text::_('COM_INSTALLER_INVALID_EXTENSION_UPDATE'));
			$update_result[0][1] = $extension_name . ' ' .  Text::_('COM_INSTALLER_INVALID_EXTENSION_UPDATE');
			$update_result[0][0] = 2;
			return $update_result;
		}
		
		try{			
			$p_file = InstallerHelper::downloadPackage($url);
		} catch (Exception $e)
		{
			$this->write_log("Error downloading package: " . $e->getMessage(),"ERROR");
		}
		
		// Was the package downloaded?
		if (!$p_file)
		{
			$this->write_log(Text::sprintf('COM_INSTALLER_PACKAGE_DOWNLOAD_FAILED', $url),"ERROR");
			$update_result[0][1] = $extension_name . ' ' . Text::sprintf('COM_INSTALLER_PACKAGE_DOWNLOAD_FAILED', $url);
			$update_result[0][0] = 2;
						
			return $update_result;
		} 
						
		$config        = Factory::getConfig();
		$tmp_dest    = $config->get('tmp_path');
		
		// Unpack the downloaded package file
		$package    = InstallerHelper::unpack($tmp_dest . '/' . $p_file);
		
		// Get an installer instance
		$installer    = Installer::getInstance();
		$update->set('type', $package['type']);
		
		// TODO: Checksum validation
								
		try {
			$install_result = $installer->update($package['dir']);
			
		} catch (Exception $e)
		{
			$this->write_log("Error installing package: " . $e->getMessage(),"ERROR");
		}
						
		// Install the package
		if (!$install_result)
		{
			// There was an error updating the package
			if (is_null($package['type']))
			{
				$package['type'] = "COMPONENT";
			}
			
			$msg = $extension_name . ' ' . Text::sprintf('COM_INSTALLER_MSG_UPDATE_ERROR', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$this->write_log($msg,"ERROR");
			$update_result = $msg;
			$update_result = 2;
			
			return $update_result;
		}
		else
		{
			// Package updated successfully
			if (is_null($package['type']))
			{
				$package['type'] = "COMPONENT";
			}

			$msg = $extension_name . ' ' . Text::sprintf('COM_INSTALLER_MSG_UPDATE_SUCCESS', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$this->write_log($msg);
			$update_result[0][1] = $msg;
			$update_result[0][0] = 1;			
		}
		
		// Quick change
		$this->type = $package['type'];
		
		if (array_key_exists('packagefile', $package))
		{
			// Cleanup the install files
			if (!is_file($package['packagefile']))
			{
				$config = Factory::getConfig();
				$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
			}

			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
		}
		
		return $update_result;
	}

	// Funci�n que obtiene informaci�n del sistema (extra�da del core)
	private function getInfo()
	{
		if (is_null($this->info))
		{
			$this->info = array();
			$version = new \Joomla\CMS\Version();
			$db = Factory::getDbo();

			if (isset($_SERVER['SERVER_SOFTWARE']))
			{
				$sf = $_SERVER['SERVER_SOFTWARE'];
			}
			else
			{
				$sf = getenv('SERVER_SOFTWARE');
			}

			$this->info['php']            = php_uname();
			$this->info['dbversion']    = $db->getVersion();
			$this->info['dbcollation']    = $db->getCollation();
			$this->info['phpversion']    = phpversion();
			$this->info['server']        = $sf;
			$this->info['sapi_name']    = php_sapi_name();
			$this->info['version']        = $version->getLongVersion();

			// $this->info['platform']        = $platform->getLongVersion();
			$this->info['platform']        = "Not defined";
			$this->info['useragent']    = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
		}
	}

	// Funci�n que devuelve informaci�n sobre las extensiones no actualizadas
	private function getNotUpdatedExtensions()
	{

		// Habilitamos los sitios deshabilitados
		//$enable = $this->enableSites();

		// Purgamos la cach� y lanzamos la tarea
		$find = $this->findUpdates();
		
		$db = Factory::getDBO();

		// Grab updates ignoring new installs (extraido de \administrator\components\com_installer\models\update.php)
		$query = $db->getQuery(true)
			->select('u.update_id,u.extension_id,u.name,u.type,u.version')
			->select($db->quoteName('e.manifest_cache'))
			->from($db->quoteName('#__updates', 'u'))
			->join('LEFT', $db->quoteName('#__extensions', 'e') . ' ON ' . $db->quoteName('e.extension_id') . ' = ' . $db->quoteName('u.extension_id'))
			->where($db->quoteName('u.extension_id') . ' != ' . $db->quote(0));
		$db->setQuery($query);
		$result = $db->loadObjectList();

		// Creamos un nuevo array que contendr� arrays con a informaci�n requerida
		$extensions = array();

		foreach ($result as $i => $item)
		{
			$value = array();
			$manifest        = json_decode($item->manifest_cache);
			$current_version = isset($manifest->version) ? $manifest->version : Text::_('JLIB_UNKNOWN');
			$value[0] = $item->update_id;
			$value[1] = $item->extension_id;
			$value[2] = $item->name;
			$value[3] = $item->type;
			$value[4] = $item->version;
			$value[5] = $current_version;
			array_push($extensions, $value);
		}

		// Devolvemos el resultado en formato JSON
		return json_encode($extensions);

	}

	/**
     * Finds updates for an extension.
     *
     * @param   int  $eid               Extension identifier to look for
     * @param   int  $cacheTimeout      Cache timeout
     * @param   int  $minimumStability  Minimum stability for updates {@see Updater} (0=dev, 1=alpha, 2=beta, 3=rc, 4=stable)
     *
     * @return  boolean Result
     *
     * @since   1.6
	 * original en /components/com_installer/src/Model/UpdateModel.php
     */
    public function findUpdates($eid = 0, $cacheTimeout = 0, $minimumStability = Updater::STABILITY_STABLE)
    {
		try{
			 Updater::getInstance()->findUpdates($eid, $cacheTimeout, $minimumStability);
		} catch (\Throwable $e) {  			            
        }
       
        return true;
    }

		/**
		 * Removes all of the updates from the table.
		 *
		 * @return boolean result of operation
		 *
		 * @since 1.6
		 *
		 * Original en /administrator/components/com_installer/models/update.php
		 */
	public function purge()
	{
		$db = Factory::getDbo();

		// Note: TRUNCATE is a DDL operation
		// This may or may not mean depending on your database
		$db->setQuery('TRUNCATE TABLE #__updates');

		if ($db->execute())
		{
			// Reset the last update check timestamp
			$query = $db->getQuery(true)
				->update($db->quoteName('#__update_sites'))
				->set($db->quoteName('last_check_timestamp') . ' = ' . $db->quote(0));
			$db->setQuery($query);
			$db->execute();
		}
	}

		/**
		 * Enables any disabled rows in #__update_sites table
		 *
		 * @return boolean result of operation
		 *
		 * @since 1.6
		 *
		 * Original en /administrator/components/com_installer/models/update.php
		 */
	public function enableSites()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->update('#__update_sites')
			->set('enabled = 1')
			->where('enabled = 0');
		$db->setQuery($query);
		$db->execute();
	}

	// Funci�n que busca si una extensi�n pasada como argumento utiliza el mecanismo de actualizaci�n de Akeeba LiveUpdate
	private function LookForPro($extension_id,$extension_name,$update) {
				
		// Inicializamos las variables
		$dlid = '';
		
		// Seg�n el campo buscamos el campo 'dlid'
		switch($extension_name)
		{
			case "pkg_akeeba":
				$params = ComponentHelper::getParams('com_akeeba');
				if (!empty($params)) {
					$dlid = $params->get('update_dlid','');		
				}
				break;
			case "pkg_admintools":
				$params = ComponentHelper::getParams('com_admintools');
				if (!empty($params)) {
					$dlid = $params->get('downloadid','');
				}
				break;
			case "com_rstbox":
				$plugin = PluginHelper::getPlugin('system', 'nrframework');
				if (!empty($plugin)) {
					$params = new JRegistry($plugin->params);
					$dlid = $params->get('key','');
				}
				break;
			case "com_jch_optimize":
				$plugin = PluginHelper::getPlugin('system', 'jch_optimize');
							
				if (!empty($plugin)) {					
					$params = new JRegistry($plugin->params);
					$dlid = $params->get('pro_downloadid','');
				}
				break;
			// Version 7 of Jch optimize
			case "pkg_jchoptimize":
				$params = ComponentHelper::getParams('com_jchoptimize');
							
				if (!empty($params)) {
					$dlid = $params->get('pro_downloadid','');
				}
				break;			
			case "com_sppagebuilder":
				$params = ComponentHelper::getParams('com_sppagebuilder');
							
				if (!empty($params)) {
					$dlid = array();
					$dlid['joomshaper_email'] = $params->get('joomshaper_email','');
					$dlid['joomshaper_license_key'] = $params->get('joomshaper_license_key','');
				}
				break;
		}		
				
		if (!empty($dlid))
		{
			$msg = "Found Pro version of " . $extension_name . " with a valid dlid.";
			$this->write_log($msg);
			$update_result = $this->install_update($update,$dlid);
			// Guardamos el id de la extensi�n junto con el resultado
			array_push($this->array_result, array($extension_id,$extension_name,$update_result));
		} else {
			$msg = "Found Pro version of " . $extension_name . " but not a valid dlid. Is the extension/plugin enabled and have a valid download id?";
			$this->write_log($msg);
			$update_result = array();
			$update_result[0][1] = $msg;
			$update_result[0][0] = 2;
			// Guardamos el id de la extensi�n junto con el resultado
			array_push($this->array_result, array($extension_id,$extension_name,$update_result));			
		}
		
	
	}	

	// Funci�n que actualiza un array de extensiones (en formato json) pasado como argumento
	private function UpdateExtension($extension_id_array)
	{
		$this->write_log("Launching UPDATEEXTENSIONS task");
				
		// Inicializamos las variables
		
		$db = Factory::getDBO();
		
		// Si las tablas est�n bloqueadas abortamos la instalaci�n
		$locked_tables = $this->check_locked_tables();

		if ($locked_tables)
		{
			$msg = Text::_('COM_SECURITYCHECKPRO_LOCKED_MESSAGE');

			array_push($this->array_result, array($msg,$msg));

			// Devolvemos el resultado
			$this->data = array(
				'update_result'        => $this->array_result
			);
		}
		else
		{
			// Para cada extensi�n, realizamos su actualizaci�n
			foreach ($extension_id_array as $extension_id)
			{
				// Extraemos los datos la extensi�n, que contendr�n la informaci�n de actualizaci�n
				try{		
					$query = "SELECT name,detailsurl,element,extra_query FROM #__updates WHERE extension_id={$extension_id}";
					$db->setQuery($query);
					$db->execute();
					$extension_data = $db->loadAssoc();					
				} catch (Exception $e)
				{
					
				}			
														
				if ( is_array($extension_data) ) {					
					$extension_name = $extension_data['name'];
					$detailsurl = $extension_data['detailsurl'];
					$extension_element = $extension_data['element'];
					$extra_query = $extension_data['extra_query'];
					
									
					if (strtolower($extension_element) == "joomla")
					{
						
						// Core de Joomla. Lo tratamos de forma diferente.
						$result_core = $this->UpdateCore();
						array_push($this->array_result, array($extension_id,'Core',$result_core));
					}else
					{	
						// Instanciamos el objeto Update y cargamos los detalles de la actualizaci�n
						$update = new Update;
						$update->loadFromXML($detailsurl);					

						// Le pasamos a la funci�n de actualizaci�n el objeto con los detalles de la actualizaci�n
						if (!empty($extra_query)) {
							// Quitamos el texto "dlid="
							$extra_query = str_replace("dlid=", "",$extra_query);
							$update_result = $this->install_update($update,$extra_query);
						} else {
							$update_result = $this->install_update($update);
						}
						
						// Update failed
						if ( (!$update_result) || ($update_result[0][0] == 2) )
						{
							$pro_versions_to_look_for = array('pkg_akeeba','pkg_admintools','com_rstbox','com_jch_optimize','pkg_jchoptimize','com_sppagebuilder');
							
							if (in_array($extension_element, $pro_versions_to_look_for)) {
								// Se ha producido un error y la extensi�n puede ser de pago. Intentamos actualizarla buscando su dlid
								$this->LookForPro($extension_id,$extension_element,$update);
							}												
						}
						else
						{
							// Guardamos el id de la extensi�n junto con el resultado
							array_push($this->array_result, array($extension_id,$extension_name,$update_result));
						}
					}
				} else {
					// Guardamos el id de la extensi�n junto con el resultado
					array_push($this->array_result, array($extension_id,"","Error retrieving extension data"));
				}				
			}
			
			// Devolvemos el resultado
			$this->data = array(
				'update_result'        => $this->array_result
			);
		}

	}

		// Funci�n que realiza una copia de seguridad usando Akeeba y su funci�n de copias de seguridad v�a frontend. La clave usada se pasa como argumento

	private function Backup($data)
	{
		$this->write_log("Launching BACKUP task");
		
		// URI del sitio
		$uri = Uri::root();
		
		$this->write_log("Decrypting Akeeba public key...");
		
		// Desencriptamos los datos recibidos, que vendr�n como un array (v�ase data[0]) y en formato json
		$response = $this->decrypt($data, $this->password);
		$response = json_decode($response, true);

		// Extraemos la clave p�blica de Akeeba, que vendr� en el elemento 'akeeba_key' del array
		$akeeba_key = $response['frontend_key'];

		// Extraemos el perfil, que por defecto ser� 1
		$akeeba_profile = $response['akeeba_profile'];
		
		// Componente (com_akeeba para J3 y com_akeebackup para J4)
		$akeeba_component = "com_akeeba";
		
		if (version_compare(JVERSION, '4.0', 'gt'))
		{
			$akeeba_component = "com_akeebabackup";
		}
		
		$this->write_log("Launching curl: " . $uri . "?option=" . $akeeba_component . "&view=backup&key=removed_for_security&profile=" . $akeeba_profile);
		
		// Inicializamos la tarea
		$ch = curl_init($uri . "?option=" . $akeeba_component . "&view=backup&key=" . $akeeba_key . "&profile=" . $akeeba_profile);
		
		// Configuraci�n extra�da de https://www.akeebabackup.com/documentation/akeeba-backup-documentation/automating-your-backup.html
		curl_setopt($ch, CURLOPT_HEADER, false);  // Este valor es false para que no incluya en la respuesta la cabecera HTTP
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10000); // Fix by Nicholas
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);
		curl_close($ch);
		
		$this->write_log("Akeeba response: " . $response);

		// Devolvemos el resultado
		$this->data = array(
		'backup'        => $response
		);
	}

	// Funci�n que instala una extensi�n desde una url. La url se pasa como argumento
	private function Upload_install($data)
	{
		$this->write_log("Launching UPLOADINSTALL task");
		
		// Inicialiamos las variables
		$result = true;
		$enqueued_messages = "";

		// Cargamos el lenguaje del componente 'com_installer'

		$lang = Factory::getLanguage();
		$lang->load('com_installer', JPATH_ADMINISTRATOR);
		
		$this->write_log("Decrypting data...");

		// Desencriptamos los datos recibidos, que vendr�n como un array (v�ase data[0]) y en formato json
		$response = $this->decrypt($data[0], $this->password);
		$response = json_decode($response, true);

		// Url del paquete a instalar
		$url = $response['path_to_file'];
		
		$this->write_log("Url: " . $url);
		
		$package = null;
		
		// Si las tablas est�n bloqueadas abortamos la instalaci�n
		$locked_tables = $this->check_locked_tables();

		if ($locked_tables)
		{
			$this->write_log("Tables are blocked. Can't install the extension.");
			$msg = Text::_('COM_SECURITYCHECKPRO_LOCKED_MESSAGE');
			$result = false;
		}
		else
		{
			// Extraemos el paquete desde la url pasada
			$package = $this->getPackageFromUrl($url);

			// Was the package unpacked?
			if (!$package || !$package['type'])
			{				
				if (in_array($installType, array('upload', 'url')))
				{
					//InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
				}
				
				$msg = Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE');
				$this->write_log($msg);

				return false;
			}

			// Get an installer instance
			$installer = Installer::getInstance();

			// Install the package
			if (!$installer->install($package['dir']))
			{
				// There was an error installing the package
				$msg = Text::sprintf('COM_INSTALLER_INSTALL_ERROR', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
				$this->write_log($msg);
				$result = false;				
			}
			else
			{
				// Package installed sucessfully
				$msg = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
				$this->write_log($msg);
			}

			// Cleanup the install files
			/*if (!is_file($package['packagefile']))
			{
				$config = Factory::getConfig();
				$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
			}

			$cleanup_resume = InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

			// Si el borrado de los ficheros de instalaci�n falla, lo hacemos 'artesanalmente'
			if (!$cleanup_resume)
			{
				$config = Factory::getConfig();
				$root = $config->get('tmp_path');
				$files_name = JFolder::files($root, '.', true, true);

				foreach ($files_name as $file)
				{
					try{		
					File::delete($root . DIRECTORY_SEPARATOR . $file);
					} catch (Exception $e)
					{
					}					
				}
			}*/

			// Recogemos los mensajes encolados para mostrar m�s informaci�n
			$enqueued_messages = Factory::getApplication()->getMessageQueue();
		}
		
		
		// Devolvemos el resultado
		$this->data = array(
			'upload_install'        => $result,
			'message'    => $msg,
			'enqueued_messages'    => $enqueued_messages
		);
	}

		/**
		 * Install an extension from a URL
		 *
		 * @return Package details or false on failure
		 *
		 * @since 1.5
		 */
	protected function getPackageFromUrl($url)
	{

		// Did you give us a URL?
		if (!$url)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), 'warning');

			return false;
		}

		// Handle updater XML file case:
		if (preg_match('/\.xml\s*$/', $url))
		{
			$update = new Update;
			$update->loadFromXML($url);
			$package_url = trim($update->get('downloadurl', false)->_data);

			if ($package_url)
			{
				$url = $package_url;
			}

			unset($update);
		}

		// Download the package at the URL given
		$p_file = InstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			Factory::getApplication()->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), 'warning');

			return false;
		}

		$config   = Factory::getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file
		$package = InstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		return $package;
	}

		/**
		 * Downloads the update package to the site.
		 *
		 * @return boolean|string False on failure, basename of the file in any other case.
		 *
		 * @since 2.5.4
		 */
	public function download_core($packageURL)
	{
		$basename = basename($packageURL);
						

		// Find the path to the temp directory and the local package.
		$config = Factory::getConfig();
		$tempdir = $config->get('tmp_path');
		$target = $tempdir . '/' . $basename;

		// Do we have a cached file?
		$exists = file_exists($target);

		if (!$exists)
		{
			// Not there, let's fetch it.
			return $this->downloadPackage($packageURL, $target);
		}
		else
		{
			// Is it a 0-byte file? If so, re-download please.
			$filesize = @filesize($target);

			if (empty($filesize))
			{
				return $this->downloadPackage($packageURL, $target);
			}

			// Yes, it's there, skip downloading.
			return $basename;
		}
	}

	/**
	 * Downloads a package file to a specific directory
	 *
	 * @param   string $url    The URL to download from
	 * @param   string $target The directory to store the file
	 *
	 * @return boolean True on success
	 *
	 * @since 2.5.4
	 */
	protected function downloadPackage($url, $target)
	{	
				
		// Make sure the target does not exist.
		if (file_exists($target)) {
			File::delete($target);
		}
		
		// Download the package
		try
		{
			$result = HttpFactory::getHttp([], ['curl', 'stream'])->get($url);				
		}
		catch (\RuntimeException $e)
		{			
			return false;
		}

		if (!$result || ($result->code != 200 && $result->code != 310))
		{
			return false;
		}

		// Fix Indirect Modification of Overloaded Property
        $body = $result->body;

        // Write the file to disk
        File::write($target, $body);

		return basename($target);
	}		

	// Funci�n que devuelve informaci�n sobre ips a a�adir y ataques detenidos para el plugin "Connect"
	public function Connect($url=null)
	{
		$cpanel_model = new CpanelModel;

		$attacks_today = $cpanel_model->LogsByDate('today');
		$attacks_yesterday = $cpanel_model->LogsByDate('yesterday');
		$attacks_last_7_days = $cpanel_model->LogsByDate('last_7_days');
		$attacks_last_month = $cpanel_model->LogsByDate('last_month');
		$attacks_this_month = $cpanel_model->LogsByDate('this_month');
		$attacks_last_year = $cpanel_model->LogsByDate('last_year');
		$attacks_this_year = $cpanel_model->LogsByDate('this_year');

		$attacks = array(
			'today'    => $attacks_today,
			'yesterday'        => $attacks_yesterday,
			'last_7_days'        => $attacks_last_7_days,
			'this_month'        => $attacks_this_month,
			'last_month'        => $attacks_last_month,
			'this_year'        => $attacks_this_year,
			'last_year'        => $attacks_last_year
			);

		// Ruta al fichero de informaci�n
		$file_path = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans' . DIRECTORY_SEPARATOR . 'cc_info.php';

		// Hay informaci�n que consumir
		if (file_exists($file_path))
		{
			$str = file_get_contents($file_path);

			// Eliminamos la parte del fichero que evita su lectura al acceder directamente
			$ips = str_replace("#<?php die('Forbidden.'); ?>", '', $str);

			// Una vez extraida la informaci�n eliminamos el fichero
			unlink($file_path);
		}
		else
		{
			$ips = null;
		}

		$this->data = array(
			'ips'        => $ips,
			'attacks'    => $attacks
			);
		
		if (!empty($url)) {
			$this->sendResponse($url);
		}		
		
	}
	

	// Funci�n que a�ade una IP a la lista negra din�mica
	function actualizar_lista_dinamica($attack_ip)
	{

		// Creamos el nuevo objeto query
		$db = Factory::getDBO();
		$query = $db->getQuery(true);

		// Chequeamos si la IP tiene un formato v�lido
		$ip_valid = filter_var($attack_ip, FILTER_VALIDATE_IP);

		// Sanitizamos la entrada
		$attack_ip = $db->escape($attack_ip);

		// Validamos si el valor devuelto es una direcci�n IP v�lida
		if ((!empty($attack_ip)) && ($ip_valid))
		{
			try
			{
				$query = "INSERT INTO `#__securitycheckpro_dynamic_blacklist` (`ip`, `timeattempt`) VALUES ('{$attack_ip}', NOW()) ON DUPLICATE KEY UPDATE `timeattempt` = NOW(), `counter` = `counter` + 1;";

				$db->setQuery($query);
				$result = $db->execute();
			}
			catch (Exception $e)
			{
			}
		}
		else
		{
			return Text::_('COM_SECURITYCHECKPRO_INVALID_FORMAT');			
		}
	}

	// Funci�n que a�ade ips a la lista negra pasados por el plugin "Connect"
	private function UpdateConnect($data)
	{
		// Desencriptamos los datos recibidos, que vendr�n en formato json
		$response = $this->decrypt($data, $this->password);				
		$ips_passed = json_decode($response, true);	
				
		$message = "";
		
		$firewall_config_model = new FirewallconfigModel();
		
		try {
			if ( is_array($ips_passed) )
			{
				if ( array_key_exists('whitelist', $ips_passed) ) {
					if (count($ips_passed['whitelist']))
					{
						$message .= Text::_('COM_SECURITYCHECKPRO_WHITELIST') . " ";
					}

					foreach ($ips_passed['whitelist'] as $whitelist)
					{
						$returned_message = $firewall_config_model->manage_list('whitelist', 'add', $whitelist, true, true);

						if (!empty($returned_message))
						{
							$message .= $whitelist . ": " . $returned_message . " ";
						}
						else
						{
							$message .= $whitelist . ": OK ";
						}
					}
				}
				
				
				if ( array_key_exists('blacklist', $ips_passed) ) {
					if (count($ips_passed['blacklist']))
					{
						$message .= Text::_('COM_SECURITYCHECKPRO_BLACKLIST') . " ";
					}

					foreach ($ips_passed['blacklist'] as $blacklist)
					{
						$returned_message = $firewall_config_model->manage_list('blacklist', 'add', $blacklist, true, true);

						if (!empty($returned_message))
						{
							$message .= $blacklist . ": " . $returned_message . " ";
						}
						else
						{
							$message .= $blacklist . ": OK ";
						}
					}
				}
				
				if ( array_key_exists('dynamic_blacklist', $ips_passed) ) {
					if (count($ips_passed['dynamic_blacklist']))
					{
						$message .= Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST') . " ";
					}

					foreach ($ips_passed['dynamic_blacklist'] as $dynamic_blacklist)
					{
						$returned_message = $this->actualizar_lista_dinamica($dynamic_blacklist);

						if (!empty($returned_message))
						{
							$message .= $dynamic_blacklist . ": " . $returned_message . " ";
						}
						else
						{
							$message .= $dynamic_blacklist . ": OK ";
						}
					}
				}
			}
			
		} catch (Exception $e) {					
			$message = $e->getMessage();
		} 
		
		// Devolvemos el resultado
		$this->data = array(
			'UpdateConnect'        => $message
			);
	}		

	// Funci�n para desbloquear las tablas (Lock tables feature)
	private function unlocktables()
	{		
		$this->write_log("Launching UNLOCKTABLES task");
		
		$cpanel_model = new CpanelModel();

		$cpanel_model->unlock_tables();

		$this->data = array(
			'tables_blocked'        => 0
		);
		$this->write_log("UNLOCKTABLES task finished");

	}

	// Funci�n para desbloquear las tablas (Lock tables feature)
	private function locktables()
	{
		$this->write_log("Launching LOCKTABLES task");
		
		$cpanel_model = new CpanelModel();

		$cpanel_model->lock_tables();

		$this->data = array(
			'tables_blocked'        => 1
		);
		
		$this->write_log("LOCKTABLES task finished");

	}
	
	/* Funci�n para formatear un entero en unidades de almacenamiento */
	function formatBytes($size, $precision = 2)
	{
		$base = log($size, 1024);
		$suffixes = array('', 'K', 'M', 'G', 'T');   

		return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	}
	
	function percent_to_color($p){
		if($p < 30) return 'success';
		if($p < 45) return 'info';
		if($p < 60) return 'primary';
		if($p < 75) return 'warning';
		return 'danger';
	}
	
	/* Get memory usage - https://www.php.net/manual/es/function.memory-get-usage.php */
	private function server_statistics()
    {
        $memoryTotal = null;
        $memoryFree = null;
		$memory_array = array();
		$uptime = null;
		// Inicializamos la variable $result, que ser� un array con el resultado y el mensaje devuelto en el proceso
		$result = array();
		
		// Memory usage
        if (stristr(PHP_OS, "win")) {
            // Get total physical memory (this is in bytes)
            $cmd = "wmic ComputerSystem get TotalPhysicalMemory";
            @exec($cmd, $outputTotalPhysicalMemory);

            // Get free physical memory (this is in kibibytes!)
            $cmd = "wmic OS get FreePhysicalMemory";
            @exec($cmd, $outputFreePhysicalMemory);

            if ($outputTotalPhysicalMemory && $outputFreePhysicalMemory) {
                // Find total value
                foreach ($outputTotalPhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryTotal = $line;
                        break;
                    }
                }

                // Find free value
                foreach ($outputFreePhysicalMemory as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        $memoryFree = $line;
                        $memoryFree *= 1024;  // convert from kibibytes to bytes
                        break;
                    }
                }
            }
        }
        else
        {
            if (is_readable("/proc/meminfo"))
            {
                $stats = @file_get_contents("/proc/meminfo");

                if ($stats !== false) {
                    // Separate lines
                    $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                    $stats = explode("\n", $stats);

                    // Separate values and find correct lines for total and free mem
                    foreach ($stats as $statLine) {
                        $statLineData = explode(":", trim($statLine));

                        //
                        // Extract size (TODO: It seems that (at least) the two values for total and free memory have the unit "kB" always. Is this correct?
                        //

                        // Total memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemTotal") {
                            $memoryTotal = trim($statLineData[1]);
                            $memoryTotal = explode(" ", $memoryTotal);
                            $memoryTotal = $memoryTotal[0];
                            $memoryTotal *= 1024;  // convert from kibibytes to bytes
                        }

                        // Free memory
                        if (count($statLineData) == 2 && trim($statLineData[0]) == "MemFree") {
                            $memoryFree = trim($statLineData[1]);
                            $memoryFree = explode(" ", $memoryFree);
                            $memoryFree = $memoryFree[0];
                            $memoryFree *= 1024;  // convert from kibibytes to bytes
                        }
                    }
                }
            }
        }

        if (is_null($memoryTotal) || is_null($memoryFree)) {
            $memory_array = null;
        } else {
			$used = $this->formatBytes( $memoryTotal - $memoryFree);
			$used_raw = $memoryTotal - $memoryFree;
			$total = $this->formatBytes($memoryTotal);
			$memory_percentage = round(($used_raw/$memoryTotal)*100,2);
			$memory_color = $this->percent_to_color($memory_percentage);
			
			$memory_array = array(
				"memory_total" => $total,
				"memory_used" => $used,
				"memory_percentage" => $memory_percentage,
				"memory_color" => $memory_color,
			);			
        }
		
		if ( (empty($memory_array["memory_total"])) && (empty($memory_array["memory_used"])) )
		{
			$memory_array = null;
		}
		
		$result['memory_array'] = $memory_array;
		
		// Uptime
		if (function_exists('system')) {
			try
			{
				$uptime = @system('uptime');
				$uptime_array = explode(",",$uptime);
				if ( (empty($uptime_array[0])) && (empty($uptime_array[1])) )
				{
					$result['uptime'] = null;
				}else
				{
					$result['uptime'] = $uptime_array[0] . "," . $uptime_array[1];	
				}		
						
				$pos = strpos($uptime_array[2],":");
				$load_average = substr($uptime_array[2],$pos+1,strlen($uptime_array[2])-$pos);
				if ( (empty($load_average)) && (empty($uptime_array[3])) && (empty($uptime_array[4])) )
				{
					$result['server_load'] = null;
				}else
				{
					$result['server_load'] = $load_average . "," . $uptime_array[3] . "," . $uptime_array[4];
				}
				
			}catch (Exception $e)	
			{
				$result['uptime'] = null;
				$result['server_load'] = null;			
			}
		} else
		{
			$result['uptime'] = null;
			$result['server_load'] = null;
		}
		
		// Devolvemos el resultado
		$this->data = $result;
    }
	
	// Funci�n para habilitar las estad�sticas
	private function enable_analytics($data)
	{		
		$this->write_log("Launching ENABLE_ANALYTICS task");
		
		if (!file_exists(JPATH_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'securitycheckproanalytics'))
		{
			$this->write_log("Analytics is not installed.");
			$this->data = 'Analytics is not installed';
			$this->status = self::STATUS_ERROR;
			$this->cipher = self::CIPHER_RAW;
		} else {
			// Desencriptamos los datos recibidos, que vendr�n en formato json
			$response = $this->decrypt($data, $this->password);				
			$response = json_decode($response, true);
			
			// Extraemos el c�digo de la web
			$website_code = $response['website_code'];			
			$cpanel_model = new CpanelModel();

			$success = $cpanel_model->enable_analytics($website_code,$this->site);

			$this->data = array(
				'analytics_enabled'        => $success
			);
			$this->write_log("ENABLE_ANALYTICS task finished");
		}
	}
	
	// Funci�n para deshabilitar las estad�sticas
	private function disable_analytics($data)
	{		
		$this->write_log("Launching DISABLE_ANALYTICS task");
		
		// Desencriptamos los datos recibidos, que vendr�n en formato json
		$response = $this->decrypt($data, $this->password);				
		$response = json_decode($response, true);
		
		// Extraemos el c�digo de la web
		$website_code = $response['website_code'];
		$cpanel_model = new CpanelModel();

		$success = $cpanel_model->disable_analytics($website_code,$this->site);

		$this->data = array(
			'analytics_disabled'        => $success
		);
		$this->write_log("ENABLE_ANALYTICS task finished");

	}

}
