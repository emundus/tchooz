<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;
use Joomla\CMS\Response\JsonResponse;

class FilemanagerController extends SecuritycheckproBaseController
{     
	public function __construct($config = [], $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);
		$this->registerTask('manageExceptionsAdd', 'manageExceptions');
		$this->registerTask('manageExceptionsDelete', 'manageExceptions');
    }
	
    /* Redirecciona las peticiones al Panel de Control */
    function redireccion_control_panel():void
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }

    /* Redirecciona las peticiones al Panel de Control de la Gestión de Archivos  y borra el fichero de logs*/
    function redireccion_control_panel_y_borra_log():void
    {
         // Obtenemos la ruta al fichero de logs, que vendrá marcada por la entrada 'log_path' del fichero 'configuration.php'
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();
		$config = $app->getConfig();
		$logName = $config->get('log_path');
        $filename = $logName . DIRECTORY_SEPARATOR ."change_permissions.log.php";
    
        // ¿ Debemos borrar el archivo de logs?
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $delete_log_file = $params->get('delete_log_file', 1);
        if ($delete_log_file == 1 ) {
            // Si no puede borrar el archivo, Joomla muestra un error indicándolo a través de JERROR
			try{		
				$result = File::delete($filename);
			} catch (\Exception $e)
			{
			}
            
        }
    
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');
    }

    /* Mostramos información de la integridad de los archivos analizados */
    public function view_files_integrity():void
    {
		$this->input->set('view', 'filesintegritystatus');
    
        parent::display();
    } 

    /* Mostramos los permisos de los archivos analizados */
    public function view_file_permissions():void
    {
		$this->input->set('view', 'filesstatus');
        parent::display();
    }

    /* Mostramos información sobre los archivos sospechosos de contener malware */
    public function view_files_malwarescan():void
    {
		$this->input->set('view', 'filemanager');
        parent::display();
    } 

    
    /* Acciones al pulsar el escaneo de archivos manual */
    function acciones():void
    {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
    
        /* Instanciamos el mainframe para guardar variables de estado de usuario */
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
        // Ponemos en la sesión de usuario que se ha lanzado una reparación de permisos
        $mainframe->setUserState("repair_launched", null);            
        $model->setCampoFilemanager('files_scanned', 0);
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        $model->setCampoFilemanager('last_check', $timestamp);
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        echo $message; 
        $model->setCampoFilemanager('estado', 'IN_PROGRESS'); 
        $model->scan("permissions");
    }

    /* Acciones al pulsar el chequeo manual de integridad */
    function acciones_integrity():void
    {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
    
        $model->setCampoFilemanager('files_scanned_integrity', 0);
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        $model->setCampoFilemanager('last_check_integrity', $timestamp);
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        echo $message; 
        $model->setCampoFilemanager('estado_integrity', 'IN_PROGRESS'); 
        $model->scan("integrity");
    }

    /* Acciones al pulsar el chequeo manual de malware */
    function acciones_malwarescan():void
    {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
    
        $model->setCampoFilemanager('files_scanned_malwarescan', 0);
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        $model->setCampoFilemanager('last_check_malwarescan', $timestamp);
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        echo $message; 
        $model->setCampoFilemanager('estado_malwarescan', 'IN_PROGRESS'); 
        $model->scan("malwarescan");    
    }

    /* Acciones al pulsar el borrado de la información de la BBDD */
    function acciones_clear_data():void
    {
    
        $message = Text::_('COM_SECURITYCHECKPRO_CLEAR_DATA_DELETING_ENTRIES');
        echo $message;  
		$this->initialize_database();		
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->setCampoFilemanager('estado_clear_data', 'ENDED');
    }

    /* Borra los datos de la tabla '#__securitycheckpro_file_permissions' */
    function initialize_database():void
    {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->initialize_database();    
    }

    /* Obtiene el estado del proceso de análisis de permisos de archivos consultando la tabla '#__securitycheckpro_file_manager'*/
    public function getEstado():void
    {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('estado');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /* Obtiene el estado del proceso de análisis de la integridad de los archivos consultando la tabla '#__securitycheckpro_file_manager'*/
    public function getEstadoIntegrity():void
    {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('estado_integrity');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /* Obtiene el estado del proceso de análisis de bús1queda de malware en los archivos consultando la tabla '#__securitycheckpro_file_manager'*/
    public function getEstadoMalwareScan():void
    {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('estado_malwarescan');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /* Obtiene el estado del proceso de hacer un drop y crear de nuevo la tabla '#__securitycheckpro_file_permissions'*/
    public function getEstadoClearData():void {
       $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('estado_clear_data');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    public function currentDateTime():void {
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        echo $timestamp;
    }

    /* Obtiene el estado del proceso de análisis de la integridad de los archivos consultando los datos de sesión almacenados previamente */
    public function get_percent_integrity():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('files_scanned_integrity');
        echo $message;
    
    }

    /* Obtiene el estado del proceso de análisis de permisos de los archivos consultando los datos de sesión almacenados previamente */
    public function get_percent():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('files_scanned');
        echo $message;
    
    }

    /* Obtiene el estado del proceso de análisis de búsqueda de malware en los archivos consultando los datos de sesión almacenados previamente */
    public function get_percent_malwarescan():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('files_scanned_malwarescan');
        echo $message;
    
    }

    /* Obtiene la diferencia, en horas, entre dos tareas de verificación de integridad. Si la diferencia es mayor de 3 horas, devuelve el valor 20000 */
    public function getEstadoIntegrity_Timediff():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $datos = null;
        
        (int) $timediff = $model->get_timediff("integrity");
        $estado_integrity = $model->GetCampoFilemanager('estado_integrity');
        $datos = json_encode(
            array(
            'estado_integrity'    => $estado_integrity,
            'timediff'        => $timediff
            )
        );
            
        echo $datos;        
    }

    /* Obtiene la diferencia, en horas, entre dos tareas de chequeo de permisos. Si la diferencia es mayor de 3 horas, devuelve el valor 20000 */
    public function getEstado_Timediff():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $datos = null;
        
        (int) $timediff = $model->get_timediff("permissions");
        $estado = $model->GetCampoFilemanager('estado');
        $datos = json_encode(
            array(
            'estado'    => $estado,
            'timediff'        => $timediff
            )
        );
            
        echo $datos;        
    }

    /* Obtiene la diferencia, en horas, entre dos tareas de búsqueda de malware. Si la diferencia es mayor de 3 horas, devuelve el valor 20000 */
    public function getEstadoMalwarescan_Timediff():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $datos = null;
        
        (int) $timediff = $model->get_timediff("malwarescan");
        $estado_malwarescan = $model->GetCampoFilemanager('estado_malwarescan');
        $datos = json_encode(
            array(
            'estado_malwarescan'    => $estado_malwarescan,
            'timediff'        => $timediff
            )
        );
            
        echo $datos;        
    }

    /* Redirecciona a la opción de mostrar las vulnerabilidades */
    function GoToVuln():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=securitycheckpro&'. Session::getFormToken() .'=1');    
    }

    /* Redirecciona a la opción de mostrar la integridad de archivos */
    function GoToIntegrity():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');        
    }

    /* Redirecciona a la opción de mostrar los permisos de archivos/directorios */
    function GoToPermissions():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');    
    }

    /* Redirecciona a la opción htaccess protection */
    function GoToHtaccessProtection():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=protection&view=protection&'. Session::getFormToken() .'=1');    
    }

    /* Redirecciona al Cponel */
    function GoToCpanel():void {
        $this->setRedirect('index.php?option=com_securitycheckpro');    
    }
	
	/* Redirecciona al Cponel */
    function GoToLogs():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=logs');    
    }
	
    /* Redirecciona a las listas del firewall */
    function GoToFirewallLists():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewalllists&view=firewalllists&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona a las listas del firewall */
    function GoToFirewallLogs():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewalllogs&view=firewalllogs&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona al segundo nivel del firewall */
    function GoToFirewallSecondLevel():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallsecond&view=firewallsecond&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona a las excepciones del firewall */
    function GoToFirewallExceptions():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona al escanér de archivos del firewall */
    function GoToUploadScanner():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=uploadscanner&view=uploadscanner&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona a la opción User session del firewall */
    function GoToUserSessionProtection():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1#session_protection');
    }

    /* Redirecciona a la opción User session del firewall */
    function GoToMalware():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=malwarescan&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona las peticiones a System Info */
    function redireccion_system_info():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');
    }

    /* Establece correctamente los permisos de archivos y/o carpetas */
    function repair():void {
       $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->repair();
                
        parent::display();
    }

    public function getEstado_cambiopermisos():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $message = $model->GetCampoFilemanager('estado_cambio_permisos');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /**
     * Unifica add/delete leyendo:
     *  - task: manageExceptionsAdd | manageExceptionsDelete
     *  - table: malwarescan | permissions | integrity
     */
    public function manageExceptions(): void
    {
        /** @var FilemanagerModel|null $model */
        $model = $this->getModel('Filemanager');

        if (!$model instanceof FilemanagerModel) {
            Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
            parent::display();
            return;
        }

        // El mismo campo que ya usabas
        $table = (string) $this->input->post->get('table', '');

        if ($table === '') {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_DATA_TO_EXPORT'), 'warning');
            parent::display();
            return;
        }

        // Sufijo del task → acción
        $task   = (string) $this->input->getCmd('task', 'manageExceptionsAdd');
		$action = str_ends_with($task, 'Delete') ? 'delete' : 'add';

        // table → type (igual que antes, pero explícito)
        $type = match ($table) {
            'malwarescan'   => 'malwarescan',
            'permissions'          => 'permissions',
            'integrity' => 'integrity',
            default => null,
        };

        if ($type === null) {
            Factory::getApplication()->enqueueMessage(Text::_('JINVALID_REQUEST'), 'error');
            parent::display();
            return;
        }

        try {
            // Método del modelo (con token/allow-list/normalización)
            $model->manageExceptions($type, $action);
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('JERROR_AN_ERROR_HAS_OCCURRED', $e->getMessage()),
                'error'
            );
        }

        parent::display();
    }

    /* Marca como seguros todos los archivos de la BBDD que aparecen como inseguros. Esto es útil cuando hay actualizaciones o la primera vez que lanzamos 'File Integrity' */
    function mark_all_unsafe_files_as_safe():void {
		$model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->markAllUnsafeFilesAsSafe();
            
        parent::display();
    }

    /* Marca como seguros todos los archivos de la BBDD seleccionados */
    function mark_checked_files_as_safe():void {    
       $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->markCheckedFilesAsSafe();
            
        parent::display();
    }

    /* Acciones al pulsar el botón para exportar la información */
   public function export_logs_integrity(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		// Carpeta donde guardas los escaneos
		$folderPath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans' . DIRECTORY_SEPARATOR;

		// 1) Leemos el nombre del fichero de integridad actual
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote('fileintegrity_resume'));
		$db->setQuery($query);

		$stackIntegrity = $db->loadResult();
		$stackIntegrity = json_decode((string) $stackIntegrity, true);

		if (empty($stackIntegrity) || empty($stackIntegrity['filename'])) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_DATA_TO_EXPORT'), 'warning');
			$this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&' . Session::getFormToken() . '=1');
			return;
		}

		$fileIntegrityName = $stackIntegrity['filename'];
		$resumePath = $folderPath . DIRECTORY_SEPARATOR . $fileIntegrityName;

		if (!is_readable($resumePath)) {
			$app->enqueueMessage(Text::sprintf('JERROR_LOADFILE_FAILED', $fileIntegrityName), 'error');
			$this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&' . Session::getFormToken() . '=1');
			return;
		}

		// 2) Cargamos el JSON del escaneo
		$raw = (string) file_get_contents($resumePath);
		$raw = str_replace("#<?php die('Forbidden.'); ?>", '', $raw); // limpia protector
		$data = json_decode($raw, true);

		if (empty($data['files_folders']) || !is_array($data['files_folders'])) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_DATA_TO_EXPORT'), 'warning');
			$this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&' . Session::getFormToken() . '=1');
			return;
		}

		$rows = $data['files_folders'];

		// 3) Construimos el CSV en memoria (seguro con fputcsv)
		$fh = fopen('php://temp', 'r+');

		// BOM + "sep=," para Excel
		fwrite($fh, "\xEF\xBB\xBF" . "sep=,\n");

		// Cabeceras
		$headers = [
			Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'),
			Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'),
			Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'),
			'Info',
		];
		fputcsv($fh, $headers, ',', '"', "\\");

		foreach ($rows as $row) {
			$path  = isset($row['path'])  ? (string) $row['path']  : '';
			$notes = isset($row['notes']) ? (string) $row['notes'] : '';

			$isFile = is_string($path) && is_file($path);
			$size = $isFile ? (int) @filesize($path) : 0;
			$mtime = $isFile ? @filemtime($path) : false;
			$lastModified = $mtime ? date('Y-m-d H:i:s', $mtime) : '';

			fputcsv($fh, [$path, $size, $lastModified, $notes], ',', '"', "\\");
		}

		rewind($fh);
		$csv = (string) stream_get_contents($fh);
		fclose($fh);

		// 4) Preparamos nombre del archivo
		$config = $app->getConfig();
		$sitename = preg_replace('/\s+/', '', (string) $config->get('sitename'));
		$timestamp = date('Ymd_His');
		$filename = "securitycheckpro_fileintegrity_{$sitename}_{$timestamp}.csv";

		// 5) Limpieza de buffers/sesión/compresión
		while (ob_get_level()) { @ob_end_clean(); }
		if (ini_get('zlib.output_compression')) { @ini_set('zlib.output_compression', 'Off'); }
		if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }

		// Content-Disposition seguro (RFC 5987)
		$fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
		$disposition = 'attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);

		// 6) Emitimos cabeceras manualmente (sin tocar el Response PSR-7)
		header('Content-Type: text/csv; charset=utf-8');
		header('X-Content-Type-Options: nosniff');
		header('Content-Disposition: ' . $disposition);
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: 0');
		// Opcional: Content-Length (comentar si usamos compresión/proxy que lo rompa)
		header('Content-Length: ' . (string) strlen($csv));

		// 7) Salida del CSV y terminamos la ejecución de Joomla
		echo $csv;
		$app->close();
	}

    function online_check_files():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $error = $model->online_check_files();
        
        if (!$error) {
            $this->setRedirect('index.php?option=com_securitycheckpro&controller=onlinechecks&view=onlinechecks&'. Session::getFormToken() .'=1');
        } else
        {
			$this->input->set('view', 'malwarescan');
    
            parent::display();
        }
    
    }

    /* Chequea hashes contra el servicio OPWAST Metadefender Cloud */
    function online_check_hashes():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $error = $model->online_check_hashes();
        
        if (!$error) {
            $this->setRedirect('index.php?option=com_securitycheckpro&controller=onlinechecks&view=onlinechecks&'. Session::getFormToken() .'=1');
        } else
        {
            $this->input->set('view', 'malwarescan');
            parent::display();
        }
    }

    /* Redirige a la página de logs enviados a OPSWAT */
    function manage_online_logs():void {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=onlinechecks&view=onlinechecks&'. Session::getFormToken() .'=1');
    }

    /* Restaura archivos movidos a la carpeta 'quarantine' */
    function restore_quarantined_file():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->quarantinedFile('restore');
    
        $this->input->set('view', 'malwarescan');
    
        parent::display();
    }

    /* Borra archivos movidos a la carpeta 'quarantine' */
    function delete_quarantined_file():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->quarantinedFile('delete');
		
		$this->input->set('view', 'malwarescan');
    
        parent::display();
    }

    /**
     * Exportar logs en formato csv
     */
    function csv_export_malware():void {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		$model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $items = $model->loadStack("malwarescan", "malwarescan");

		// 3) Construimos el CSV en memoria (seguro con fputcsv)
		$fh = fopen('php://temp', 'r+');

		// BOM + "sep=," para Excel
		fwrite($fh, "\xEF\xBB\xBF" . "sep=,\n");

		// Cabeceras
		$headers = [
			Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'),
			Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'),
			Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'),
			Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_TYPE'),
			Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_DESCRIPTION'),
			Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_CODE_DESCRIPTION'),
			Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_ALERT_LEVEL'),
			'Safe',
			'Hash',
			'Data_id',
			'Rest_ip', 
			Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_ONLINE_CHECK'),
			Text::_('COM_SECURITYCHECKPRO_MOVED_TO_QUARANTINE'),
			'Quarantined file name',
		];
		
		fputcsv($fh, $headers, ',', '"', "\\");
		
		foreach ($items as $item) {
			fputcsv($fh, $item, ',', '"', "\\");
		}

		rewind($fh);
		$csv = (string) stream_get_contents($fh);
		fclose($fh);

		// 4) Preparamos nombre del archivo
		$config = $app->getConfig();
		$sitename = preg_replace('/\s+/', '', (string) $config->get('sitename'));
		$timestamp = date('Ymd_His');
		$filename = "securitycheckpro_malwarescan_results_{$sitename}_{$timestamp}.csv";

		// 5) Limpieza de buffers/sesión/compresión
		while (ob_get_level()) { @ob_end_clean(); }
		if (ini_get('zlib.output_compression')) { @ini_set('zlib.output_compression', 'Off'); }
		if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }

		// Content-Disposition seguro (RFC 5987)
		$fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
		$disposition = 'attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);

		// 6) Emitimos cabeceras manualmente (sin tocar el Response PSR-7)
		header('Content-Type: text/csv; charset=utf-8');
		header('X-Content-Type-Options: nosniff');
		header('Content-Disposition: ' . $disposition);
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: 0');
		// Opcional: Content-Length (comentar si usamos compresión/proxy que lo rompa)
		header('Content-Length: ' . (string) strlen($csv));

		// 7) Salida del CSV y terminamos la ejecución de Joomla
		echo $csv;
		$app->close();    
    }

    /* Función para borrar archivos sospechosos */
    function delete_file():void {
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->deleteFiles();
    
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();    
        $mainframe->setUserState('contenido', "vacio");
    
        $this->input->set('view', 'malwarescan');
        parent::display();    
    }    

    /* Borra los archivos y directorios de la carpeta temporal */
    function acciones_clean_tmp_dir():void {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
        $app->setUserState("clean_tmp_dir_result", "");
        $model = $this->getModel('Filemanager');
		if (!$model instanceof FilemanagerModel) {
			Factory::getApplication()->enqueueMessage('Filemanager model not found', 'error');
			return;
		}
        $model->accionesCleanTmpDir();    
    }

    /* Obtiene el estado del proceso de borrado del directorio temporal */
    public function getEstadocleantmpdir():void {
        error_reporting(0);
		
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
        $message = $mainframe->getUserState("clean_tmp_dir_state", Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED'));
        echo $message;
    }

    /* Obtiene el estado del proceso de borrado del directorio temporal */
    public function getcleantmpdirmessage():void {
        error_reporting(0);
		
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
        $message = $mainframe->getUserState("clean_tmp_dir_result", "");
        
        echo $message;
        $mainframe->setUserState("clean_tmp_dir_result", "");
    }
	
	 /* Sanitiza un string para mostrarlo en una ventana modal */
	private function sanitizeHtmlForModal(string $html): string
	{
		// 1) Elimina etiquetas peligrosas completas
		$html = preg_replace(
			'#<\s*(script|style|iframe|object|embed|link|meta|base)[^>]*>.*?<\s*/\s*\1\s*>#is',
			'',
			$html
		);

		// 2) Quita eventos inline on*
		$html = preg_replace('#\son\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#iu', '', $html);

		// 3) Quita javascript: en href/src
		$html = preg_replace('#\b(href|src)\s*=\s*(["\'])\s*javascript\s*:#iu', '$1="#"', $html);

		// 5) Permite solo etiquetas seguras
		$allowed = '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><em><b><i><code><pre><blockquote><span><table><thead><td><th><tr><font>';
		$html = strip_tags($html, $allowed);
		
		return $html;
	}
	
	/** Obtiene el contenido de un fichero de escaneos (integridad, permisos o malware) para mostrar al usuario en la opción "Ver log". También cuando vemos un log de escaneos de malware online contra OPSWAT
	*/
	public function fetchLog(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app   = Factory::getApplication();
		$input = $app->getInput();

		$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);

		if (!Session::checkToken('get')) {
			echo json_encode([
				'success' => false,
				'message' => Text::_('JINVALID_TOKEN'),
				'data'    => null,
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

			$app->close();
		}

		try {
			$requested = $input->getString('logfilename', '');

			$baseDirReal = realpath(JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/scans');
			if ($baseDirReal === false) {
				throw new \RuntimeException('Base dir not found');
			}

			$safeName = basename($requested);
			if ($safeName === '' || $safeName === '.' || $safeName === '..') {
				throw new \RuntimeException('Invalid filename');
			}

			$fullPath = realpath($baseDirReal . DIRECTORY_SEPARATOR . $safeName);
			if ($fullPath === false || strncmp($fullPath, $baseDirReal, strlen($baseDirReal)) !== 0 || !is_file($fullPath)) {
				throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
			}

			$content = file_get_contents($fullPath);
			if ($content === false) {
				throw new \RuntimeException('file_get_contents failed');
			}

			// Si quieres forzar siempre “texto” y evitar sorpresas:
			// Sustituye NULs que a veces vienen en logs y rompen cosas en clientes
			$content = str_replace("\0", "\u{FFFD}", $content);

			$payload = [
				'success' => true,
				'message' => null,
				'data' => [
					'name' => $safeName,
					'content' => $content,
				],
			];

			$json = json_encode(
				$payload,
				JSON_UNESCAPED_UNICODE
				| JSON_UNESCAPED_SLASHES
				| JSON_INVALID_UTF8_SUBSTITUTE
			);

			if ($json === false) {
				throw new \RuntimeException('json_encode failed: ' . json_last_error_msg());
			}

			echo $json;
		} catch (\Throwable $e) {
			$json = json_encode([
				'success' => false,
				'message' => $e->getMessage(),
				'data'    => null,
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

			echo $json !== false ? $json : '{"success":false,"message":"json_encode failed","data":null}';
		}

		$app->close();
	}

    /**
     * Devuelve las últimas N líneas de un fichero grande sin cargarlo entero en memoria.
     */
    private function tailFile(string $file, int $lines = 2000): string
    {
        $f = @fopen($file, 'rb');
        if (!$f) return '';

        $buffer = '';
        $chunkSize = 4096;
        $pos = -1;
        $lineCount = 0;

        fseek($f, 0, SEEK_END);
        $fileSize = ftell($f);

        while ($lineCount <= $lines && -$pos < $fileSize) {
            $step = min($chunkSize, $fileSize + $pos);
            $pos -= $step;
            fseek($f, $pos, SEEK_END);
            $chunk = fread($f, $step);
            $buffer = $chunk . $buffer;
            $lineCount = substr_count($buffer, "\n");
        }
        fclose($f);

        $parts = explode("\n", $buffer);
        $tail  = array_slice($parts, -$lines);
        return implode("\n", $tail);
    }
	    
}