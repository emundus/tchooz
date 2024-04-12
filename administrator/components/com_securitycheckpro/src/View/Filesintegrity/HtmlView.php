<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Filesintegrity;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

/**
 * Main Admin View
 */
class HtmlView extends BaseHtmlView {
    
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		  
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_INTEGRITY_CONTROL_PANEL_TEXT'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		 
		  ->useScript('com_securitycheckpro.Fileintegrity');
		
		// Obtenemos los datos del modelo
       	$model               = $this->getModel();
		$this->state         = $model->getState();
				
        $last_check_integrity = $model->loadStack("fileintegrity_resume", "last_check_integrity");
        $files_scanned_integrity = $model->loadStack("fileintegrity_resume", "files_scanned_integrity");
        $files_with_bad_integrity = $model->loadStack("fileintegrity_resume", "files_with_bad_integrity");
		$time_taken = $model->loadStack("fileintegrity_resume", "time_taken");
		$last_scan_info = $model->loadStack("fileintegrity_resume", "last_scan_info");
        $this->log_filename = $model->get_log_filename("fileintegrity_log", true);
		$message_info = Text::sprintf('COM_SECURITYCHECKPRO_SCAN_INFO_MESSAGE',  Text::_('COM_SECURITYCHECKPRO_SCAN_ALL_FILES_INFO_MESSAGE'));
								
        $task_ended = $model->get_campo_filemanager("estado_integrity");

        // Obtenemos el algoritmo seleccionado para crear el valor hash y si está habilitada la opción para escanear sólo ficheros ejecutables
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $hash_alg = $params->get('file_integrity_hash_alg', 'SHA1');
        $scan_executables_only = $params->get('scan_executables_only', 0);
		$this->file_manager_include_exceptions_in_database = $params->get('file_manager_include_exceptions_in_database', 0);
		// Consultamos dónde han de ir los 'checkboxes'
		$this->checkbox_position = $params->get('checkbox_position','0');

        // Información para la barra de navegación
        $logs_pending = $model->LogsPending();
        $trackactions_plugin_exists = $model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;

        // Ponemos los datos en el template
        $this->last_check_integrity = $last_check_integrity;
        $this->files_scanned_integrity = $files_scanned_integrity;
        $this->hash_alg = $hash_alg; 
        $this->files_with_bad_integrity = $files_with_bad_integrity; 
        $this->scan_executables_only = $scan_executables_only;
		$this->time_taken = $time_taken;
		$this->last_scan_info = $last_scan_info;
	

		if ($scan_executables_only) {
			$message_info = Text::sprintf('COM_SECURITYCHECKPRO_SCAN_INFO_MESSAGE', Text::_('COM_SECURITYCHECKPRO_SCAN_ONLY_EXECUTABLE_FILES_INFO_MESSAGE'));
		}
		$this->message_info = $message_info;

        // Filesstatus

        // Filtro por tipo de extensión
        $fileintegrity_search = $this->state->get('filter.fileintegrity_search');
        $filter_fileintegrity_status = $this->state->get('filter.fileintegrity_status');

        // Establecemos el valor del filtro 'fileintegrity_status' a cero para que muestre sólo los permisos incorrectos
        if ($filter_fileintegrity_status == '') {
            $this->state->set('filter.fileintegrity_status', 0);
        }

        $this->items = $model->loadStack("integrity", "file_integrity");	
		$this->show_all = $this->state->get('showall', 0);
        $this->database_error = $model->get_campo_filemanager("estado_integrity");          
        $this->installs = $model->get_installs();
    
        if (!empty($this->items)) {
            $this->pagination = $model->getPagination();           
            ToolBarHelper::custom('mark_all_unsafe_files_as_safe', 'flag-2', 'flag-2', 'COM_SECURITYCHECKPRO_FILEINTEGRITY_MARK_ALL_UNSAFE_FILES_AS_SAFE', false);
            ToolBarHelper::custom('mark_checked_files_as_safe', 'flag', 'flag', 'COM_SECURITYCHECKPRO_FILEINTEGRITY_MARK_CHECKED_FILES_AS_SAFE');
            ToolBarHelper::custom('export_logs_integrity', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_EXPORT_INFO_CSV', false);
        }
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.activetaskText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ACTIVE_TASK')));
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.taskfailureText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TASK_FAILURE')));
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.repairviewlogheader', '<div class="alert alert-info" role="alert">' . addslashes(Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_HEADER')) . '</div>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.processcompletedText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PROCESS_COMPLETED')));		
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.end', '<span class="badge bg-success">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED')) . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.inprogress', '<span class="badge bg-info">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS')) . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.error', '<span class="badge bg-danger">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR')) . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.updatingstats', addslashes(Text::_('COM_SECURITYCHECKPRO_UPDATING_STATS')) . '<br/><br/><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.urltoredirect', Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1', false));
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.failureText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FAILURE')));
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.errorbutton', '<button class="btn btn-primary" type="button" onclick="window.location.reload();">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_REFRESH_BUTTON')) . '</button>');		

        parent::display($tpl); 
    }


}