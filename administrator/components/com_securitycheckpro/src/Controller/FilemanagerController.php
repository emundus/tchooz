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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;

class FilemanagerController extends SecuritycheckproBaseController
{      
    /* Redirecciona las peticiones al Panel de Control */
    function redireccion_control_panel()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');
    }

    /* Redirecciona las peticiones al Panel de Control de la Gestión de Archivos  y borra el fichero de logs*/
    function redireccion_control_panel_y_borra_log()
    {
         // Obtenemos la ruta al fichero de logs, que vendrá marcada por la entrada 'log_path' del fichero 'configuration.php'
        $app = Factory::getApplication();
        $logName = $app->getCfg('log_path');
        $filename = $logName . DIRECTORY_SEPARATOR ."change_permissions.log.php";
    
        // ¿ Debemos borrar el archivo de logs?
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $delete_log_file = $params->get('delete_log_file', 1);
        if ($delete_log_file == 1 ) {
            // Si no puede borrar el archivo, Joomla muestra un error indicándolo a través de JERROR
			try{		
				$result = File::delete($filename);
			} catch (Exception $e)
			{
			}
            
        }
    
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');
    }

    /* Mostramos información de la integridad de los archivos analizados */
    public function view_files_integrity()
    {
        $jinput->set('view', 'filesintegritystatus');
    
        parent::display();
    } 

    /* Mostramos los permisos de los archivos analizados */
    public function view_file_permissions()
    {
        $jinput->set('view', 'filesstatus');
        parent::display();
    }

    /* Mostramos información sobre los archivos sospechosos de contener malware */
    public function view_files_malwarescan()
    {
        $jinput->set('view', 'filemanager');
        parent::display();
    } 

    
    /* Acciones al pulsar el escaneo de archivos manual */
    function acciones()
    {
        $model = $this->getModel("filemanager");
    
        /* Instanciamos el mainframe para guardar variables de estado de usuario */
        $mainframe = Factory::getApplication();
        // Ponemos en la sesión de usuario que se ha lanzado una reparación de permisos
        $mainframe->setUserState("repair_launched", null);            
        $model->set_campo_filemanager('files_scanned', 0);
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        $model->set_campo_filemanager('last_check', $timestamp);
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        echo $message; 
        $model->set_campo_filemanager('estado', 'IN_PROGRESS'); 
        $model->scan("permissions");
    }

    /* Acciones al pulsar el chequeo manual de integridad */
    function acciones_integrity()
    {
        $model = $this->getModel("filemanager");
    
        $model->set_campo_filemanager('files_scanned_integrity', 0);
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        $model->set_campo_filemanager('last_check_integrity', $timestamp);
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        echo $message; 
        $model->set_campo_filemanager('estado_integrity', 'IN_PROGRESS'); 
        $model->scan("integrity");
    }

    /* Acciones al pulsar el chequeo manual de malware */
    function acciones_malwarescan()
    {
        $model = $this->getModel("filemanager");
    
        $model->set_campo_filemanager('files_scanned_malwarescan', 0);
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        $model->set_campo_filemanager('last_check_malwarescan', $timestamp);
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS');
        echo $message; 
        $model->set_campo_filemanager('estado_malwarescan', 'IN_PROGRESS'); 
        $model->scan("malwarescan");    
    }

    /* Acciones al pulsar el borrado de la información de la BBDD */
    function acciones_clear_data()
    {
    
        $message = Text::_('COM_SECURITYCHECKPRO_CLEAR_DATA_DELETING_ENTRIES');
        echo $message;  
		$this->initialize_database();		
        $model = $this->getModel("filemanager");
        $model->set_campo_filemanager('estado_clear_data', 'ENDED');
    }

    /* Borra los datos de la tabla '#__securitycheckpro_file_permissions' */
    function initialize_database()
    {
        $model = $this->getModel("filemanager");
        $model->initialize_database();
    
    }

    /* Obtiene el estado del proceso de análisis de permisos de archivos consultando la tabla '#__securitycheckpro_file_manager'*/
    public function getEstado()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('estado');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /* Obtiene el estado del proceso de análisis de la integridad de los archivos consultando la tabla '#__securitycheckpro_file_manager'*/
    public function getEstadoIntegrity()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('estado_integrity');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /* Obtiene el estado del proceso de análisis de bús1queda de malware en los archivos consultando la tabla '#__securitycheckpro_file_manager'*/
    public function getEstadoMalwareScan()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('estado_malwarescan');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /* Obtiene el estado del proceso de hacer un drop y crear de nuevo la tabla '#__securitycheckpro_file_permissions'*/
    public function getEstadoClearData()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('estado_clear_data');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    public function currentDateTime()
    {
		$base_model = new BaseModel();
		$timestamp = $base_model->get_Joomla_timestamp();
        echo $timestamp;
    }

    /* Obtiene el estado del proceso de análisis de la integridad de los archivos consultando los datos de sesión almacenados previamente */
    public function get_percent_integrity()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('files_scanned_integrity');
        echo $message;
    
    }

    /* Obtiene el estado del proceso de análisis de permisos de los archivos consultando los datos de sesión almacenados previamente */
    public function get_percent()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('files_scanned');
        echo $message;
    
    }

    /* Obtiene el estado del proceso de análisis de búsqueda de malware en los archivos consultando los datos de sesión almacenados previamente */
    public function get_percent_malwarescan()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('files_scanned_malwarescan');
        echo $message;
    
    }

    /* Obtiene la diferencia, en horas, entre dos tareas de verificación de integridad. Si la diferencia es mayor de 3 horas, devuelve el valor 20000 */
    public function getEstadoIntegrity_Timediff()
    {
        $model = $this->getModel("filemanager");
        $datos = null;
        
        (int) $timediff = $model->get_timediff("integrity");
        $estado_integrity = $model->get_campo_filemanager('estado_integrity');
        $datos = json_encode(
            array(
            'estado_integrity'    => $estado_integrity,
            'timediff'        => $timediff
            )
        );
            
        echo $datos;        
    }

    /* Obtiene la diferencia, en horas, entre dos tareas de chequeo de permisos. Si la diferencia es mayor de 3 horas, devuelve el valor 20000 */
    public function getEstado_Timediff()
    {
        $model = $this->getModel("filemanager");
        $datos = null;
        
        (int) $timediff = $model->get_timediff("permissions");
        $estado = $model->get_campo_filemanager('estado');
        $datos = json_encode(
            array(
            'estado'    => $estado,
            'timediff'        => $timediff
            )
        );
            
        echo $datos;        
    }

    /* Obtiene la diferencia, en horas, entre dos tareas de búsqueda de malware. Si la diferencia es mayor de 3 horas, devuelve el valor 20000 */
    public function getEstadoMalwarescan_Timediff()
    {
        $model = $this->getModel("filemanager");
        $datos = null;
        
        (int) $timediff = $model->get_timediff("malwarescan");
        $estado_malwarescan = $model->get_campo_filemanager('estado_malwarescan');
        $datos = json_encode(
            array(
            'estado_malwarescan'    => $estado_malwarescan,
            'timediff'        => $timediff
            )
        );
            
        echo $datos;        
    }

    /* Redirecciona a la opción de mostrar las vulnerabilidades */
    function GoToVuln()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&'. Session::getFormToken() .'=1');    
    }

    /* Redirecciona a la opción de mostrar la integridad de archivos */
    function GoToIntegrity()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');        
    }

    /* Redirecciona a la opción de mostrar los permisos de archivos/directorios */
    function GoToPermissions()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1');    
    }

    /* Redirecciona a la opción htaccess protection */
    function GoToHtaccessProtection()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=protection&view=protection&'. Session::getFormToken() .'=1');    
    }

    /* Redirecciona al Cponel */
    function GoToCpanel()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro');    
    }
	
	/* Redirecciona al Cponel */
    function GoToLogs()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=securitycheckpro&view=logs');    
    }
	
    /* Redirecciona a las listas del firewall */
    function GoToFirewallLists()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewalllists&view=firewalllists&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona a las listas del firewall */
    function GoToFirewallLogs()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewalllogs&view=firewalllogs&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona al segundo nivel del firewall */
    function GoToFirewallSecondLevel()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallsecond&view=firewallsecond&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona a las excepciones del firewall */
    function GoToFirewallExceptions()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona al escanér de archivos del firewall */
    function GoToUploadScanner()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=uploadscanner&view=uploadscanner&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona a la opción User session del firewall */
    function GoToUserSessionProtection()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1#session_protection');
    }

    /* Redirecciona a la opción User session del firewall */
    function GoToMalware()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=malwarescan&'. Session::getFormToken() .'=1');
    }

    /* Redirecciona las peticiones a System Info */
    function redireccion_system_info()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=sysinfo&'. Session::getFormToken() .'=1');
    }

    /* Establece correctamente los permisos de archivos y/o carpetas */
    function repair()
    {
        $model = $this->getModel("filemanager");
        $model->repair();
                
        parent::display();
    }

    public function getEstado_cambiopermisos()
    {
        $model = $this->getModel("filemanager");
        $message = $model->get_campo_filemanager('estado_cambio_permisos');
        $message = Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_' .$message);
        echo $message;
    }

    /* Añade ruta(s) a la lista de excepciones */
    function addfile_exception()
    {
        $model = $this->getModel("filemanager");
        // Obtenemos el valor del campo "table" del formulario, que indicará de qué pantalla venimos y qué tabla queremos modificar
        $table = $this->input->post->get("table", null);    
        if (empty($table)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_DATA_TO_EXPORT'), 'warning');
        } else 
        {
            $model->addfile_exception($table);
        }
            
        parent::display();
    }

    /* Borra ruta(s) de la lista de excepciones */
    function deletefile_exception()
    {
        $model = $this->getModel("filemanager");
        // Obtenemos el valor del campo "table" del formulario, que indicará de qué pantalla venimos  y qué tabla queremos modificar
        $table = $this->input->post->get("table", null);
    
        if (empty($table)) {        
            Factory::getApplication()->enqueueMessage(Text::_('SOME_ERROR_OCCURRED'), 'error');
        } else
        {
            $model->deletefile_exception($table);
        }
        
        parent::display();
    }

    /* Marca como seguros todos los archivos de la BBDD que aparecen como inseguros. Esto es útil cuando hay actualizaciones o la primera vez que lanzamos 'File Integrity' */
    function mark_all_unsafe_files_as_safe()
    {
    
        $model = $this->getModel("filemanager");
        $model->mark_all_unsafe_files_as_safe();
            
        parent::display();
    }

    /* Marca como seguros todos los archivos de la BBDD seleccionados */
    function mark_checked_files_as_safe()
    {
    
        $model = $this->getModel("filemanager");
        $model->mark_checked_files_as_safe();
            
        parent::display();
    }

    /* Acciones al pulsar el botón para exportar la información */
    function export_logs_integrity()
    {
        $fileintegrity_name = '';
    
        // Establecemos la ruta donde se almacenaran los escaneos
        $this->folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;
    
        // Obtenemos el nombre del escaneo actual
        $db = Factory::getDBO();
        $query = $db->getQuery(true)
            ->select(array($db->quoteName('storage_value')))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
        $db->setQuery($query);
        $stack_integrity = $db->loadResult();    
        $stack_integrity = json_decode($stack_integrity, true);
        
        if (!empty($stack_integrity)) {
            $fileintegrity_name = $stack_integrity['filename'];
                
            // Leemos el contenido del fichero
            if (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$fileintegrity_name)) {
                  $stack = file_get_contents($this->folder_path.DIRECTORY_SEPARATOR.$fileintegrity_name);
                  // Eliminamos la parte del fichero que evita su lectura al acceder directamente
                  $stack = str_replace("#<?php die('Forbidden.'); ?>", '', $stack);
            }
        
            $stack = json_decode($stack, true);
            $stack = $stack['files_folders'];
        
            $csv_export = "";
        
            // Cabecera del archivo
            $headers = array(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'),Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'), Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'), 'Info');
            $csv_export .= implode(",", $headers);
        
            for ($i = 0 , $n = count($stack); $i < $n ; $i++)
            {
                $csv_export .= "\n" .$stack[$i]['path'];
                $size = filesize($stack[$i]['path']);
                $csv_export .= "," .$size;        
                $last_modified = date('Y-m-d H:i:s', filemtime($stack[$i]['path']));
                $csv_export .= "," .$last_modified;    
                $csv_export .= "," .$stack[$i]['notes'];
            }
                
            // Mandamos el contenido al navegador
            $config = Factory::getConfig();
            $sitename = $config->get('sitename');
            // Remove whitespaces of sitename
            $sitename = str_replace(' ', '', $sitename);
            $timestamp = date('mdy_his');
            $filename = "securitycheckpro_fileintegrity_" . $sitename . "_" . $timestamp . ".csv";
            @ob_end_clean();    
            ob_start();    
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment;filename=' . $filename);
            print $csv_export;
            exit();
        } else
        {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_DATA_TO_EXPORT'), 'warning');
            $this->setRedirect('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1');            
        }    
    }

    function online_check_files()
    {
        $model = $this->getModel("filemanager");
        $error = $model->online_check_files();
    
        $jinput = Factory::getApplication()->input;
    
        if (!$error) {
            $this->setRedirect('index.php?option=com_securitycheckpro&controller=onlinechecks&view=onlinechecks&'. Session::getFormToken() .'=1');
        } else
        {
            $jinput->set('view', 'malwarescan');
    
            parent::display();
        }
    
    }

    /* Chequea hashes contra el servicio OPWAST Metadefender Cloud */
    function online_check_hashes()
    {
        $model = $this->getModel("filemanager");
        $error = $model->online_check_hashes();
    
        $jinput = Factory::getApplication()->input;
    
        if (!$error) {
            $this->setRedirect('index.php?option=com_securitycheckpro&controller=onlinechecks&view=onlinechecks&'. Session::getFormToken() .'=1');
        } else
        {
            $jinput->set('view', 'malwarescan');
            parent::display();
        }
    }

    /* Añade ruta(s) a la lista de excepciones */
    function manage_online_logs()
    {
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=onlinechecks&view=onlinechecks&'. Session::getFormToken() .'=1');
    }

    /* Restaura archivos movidos a la carpeta 'quarantine' */
    function restore_quarantined_file()
    {
        $model = $this->getModel("filemanager");
        $model->quarantined_file('restore');
    
        $jinput = Factory::getApplication()->input;
    
        $jinput->set('view', 'malwarescan');
    
        parent::display();
    }

    /* Borra archivos movidos a la carpeta 'quarantine' */
    function delete_quarantined_file()
    {
        $model = $this->getModel("filemanager");
        $model->quarantined_file('delete');
    
        $jinput = Factory::getApplication()->input;
    
        $jinput->set('view', 'malwarescan');
    
        parent::display();
    }

    /**
     * Exportar logs en formato csv
     */
    function csv_export_malware()
    {
        // Obtenemos los archivos reportados
        $model = $this->getModel("filemanager");
        $items = $model->loadStack("malwarescan", "malwarescan");
        $csv_export = "";
        
        // Cabecera del archivo
        $headers = array(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_RUTA'),Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TAMANNO'),Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAST_MODIFIED'),Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_TYPE'), Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_DESCRIPTION'), Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_CODE_DESCRIPTION'), Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_ALERT_LEVEL'), 'Safe', 'Hash', 'Data_id', 'Rest_ip', Text::_('COM_SECURITYCHECKPRO_MALWARESCAN_ONLINE_CHECK'), Text::_('COM_SECURITYCHECKPRO_MOVED_TO_QUARANTINE'), 'Quarantined file name');
        $csv_export .= implode(";", $headers);

        for ($i = 0 , $n = count($items); $i < $n ; $i++)
        {        
            $csv_export .= "\n" .implode(";", $items[$i]);
        }
    
        // Mandamos el contenido al navegador
        $config = Factory::getConfig();
        $sitename = $config->get('sitename');
        // Remove whitespaces of sitename
        $sitename = str_replace(' ', '', $sitename);
        $timestamp = date('mdy_his');
        $filename = "securitycheckpro_malwarescan_results_" . $sitename . "_" . $timestamp . ".csv";
        @ob_end_clean();    
        ob_start();    
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $filename);
        print $csv_export;
        exit();
    
    }

    /* Función para borrar archivos sospechosos */
    function delete_file()
    {
        $model = $this->getModel("filemanager");
        $model->delete_files();
    
        $mainframe = Factory::getApplication();    
        $mainframe->setUserState('contenido', "vacio");
    
        $jinput = $mainframe->input;    
        $jinput->set('view', 'malwarescan');
        parent::display();    
    }

    /* Función para borrar archivos sospechosos */
    function view_file()
    {
        $model = $this->getModel("filemanager");
        $model->view_file();
    
        parent::display();    
    }

    /* Borra los archivos y directorios de la carpeta temporal */
    function acciones_clean_tmp_dir()
    {
        Factory::getApplication()->setUserState("clean_tmp_dir_result", "");
        $model = $this->getModel("filemanager");
        $model->acciones_clean_tmp_dir();    
    }

    /* Obtiene el estado del proceso de borrado del directorio temporal */
    public function getEstadocleantmpdir()
    {
        error_reporting(0);
    
        $mainframe = Factory::getApplication();
        $message = $mainframe->getUserState("clean_tmp_dir_state", Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED'));
        echo $message;
    }

    /* Obtiene el estado del proceso de borrado del directorio temporal */
    public function getcleantmpdirmessage()
    {
        error_reporting(0);
    
        $mainframe = Factory::getApplication();
        $message = $mainframe->getUserState("clean_tmp_dir_result", "");
        
        echo $message;
        $mainframe->setUserState("clean_tmp_dir_result", "");
    }
	    
}