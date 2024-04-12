<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Filemanager;

 // Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

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
		  
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_MANAGER_CONTROL_PANEL_TEXT'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		 
		  ->useScript('com_securitycheckpro.Filemanager');

        // Obtenemos los datos del modelo
        $model = $this->getModel();		
        $last_check = $model->loadStack("filemanager_resume", "last_check");
        $files_scanned = $model->loadStack("filemanager_resume", "files_scanned");
        $incorrect_permissions = $model->loadStack("filemanager_resume", "files_with_incorrect_permissions");
		$time_taken = $model->loadStack("filemanager_resume", "time_taken");
        $this->log_filename = $model->get_log_filename("filepermissions_log", true);

        $task_ended = $model->get_campo_filemanager("estado");

        // Obtenemos si está habilitada la opción para escanear sólo ficheros ejecutables
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $scan_executables_only = $params->get('scan_executables_only', 0);
		$this->file_manager_include_exceptions_in_database = $params->get('file_manager_include_exceptions_in_database', 1);
		// Consultamos dónde han de ir los 'checkboxes'
		$this->checkbox_position = $params->get('checkbox_position','0');

        // Información para la barra de navegación
        $logs_pending = $model->LogsPending();
        $trackactions_plugin_exists = $model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;

        // Ponemos los datos en el template
        $this->last_check = $last_check;
        $this->files_scanned = $files_scanned;
        $this->incorrect_permissions = $incorrect_permissions;
        $this->scan_executables_only = $scan_executables_only;
		$this->time_taken = $time_taken;

        /* Filesstatus */

        /* Cargamos el lenguaje del sitio */
        $lang = Factory::getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

        // Filtro por tipo de extensión
        $this->state= $this->get('State');
        $search = $this->state->get('filter.filemanager_search');
        $filter_kind = $this->state->get('filter.filemanager_kind');
        $filter_permissions_status = $this->state->get('filter.filemanager_permissions_status');

        // Establecemos el valor del filtro 'permissions_status' a cero para que muestre sólo los permisos incorrectos
        if ($filter_permissions_status == '') {
            $this->state->set('filter.filemanager_permissions_status', 0);
        }

        // Establecemos el valor del filtro 'kind' a 'File' para que muestre sólo los ficheros
        if ($filter_kind == '') {
            $this->state->set('filter.filemanager_kind', $lang->_('COM_SECURITYCHECKPRO_FILEMANAGER_FILE'));
        }

        $items_permissions = $model->loadStack("permissions", "file_manager");
        $files_with_incorrect_permissions = $model->loadStack("filemanager_resume", "files_with_incorrect_permissions");
        $show_all = $this->state->get('showall', 0);
        $database_error = $model->get_campo_filemanager("estado");

        // Ponemos los datos en el template
        $this->items_permissions = $items_permissions;
        $this->files_with_incorrect_permissions = $files_with_incorrect_permissions;
        $this->show_all = $show_all;
        $this->database_error = $database_error;

        if (!empty($items_permissions)) {
            $this->pagination = $this->get('Pagination');    
        }

        $mainframe = Factory::getApplication();
        $repair_launched = $mainframe->getUserState("repair_launched", null);
        $this->repair_launched = $repair_launched;

        if (!empty($repair_launched)) {
            $this->repair_log = $model->get_repair_log();    
        }
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Filemanager.activetaskText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ACTIVE_TASK')));
		$this->document->addScriptOptions('securitycheckpro.Filemanager.taskfailureText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_TASK_FAILURE')));
		$this->document->addScriptOptions('securitycheckpro.Filemanager.repairviewlogheader', '<div class="alert alert-info" role="alert">' . addslashes(Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_HEADER')) . '</div>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.repairlaunched', $this->repair_launched);
		$this->document->addScriptOptions('securitycheckpro.Filemanager.processcompletedText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_PROCESS_COMPLETED')));
		$this->document->addScriptOptions('securitycheckpro.Filemanager.launchnewtask', '<div class="alert alert-warning" role="alert">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAUNCH_NEW_TASK')) . '</div>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.divviewlogbutton', '<button class="btn btn-primary" onclick="showLog();">' . addslashes(Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_MESSAGE')) . '</button>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.end', '<span class="badge bg-success">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED')) . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.inprogress', '<span class="badge bg-info">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS')) . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.error', '<span class="badge bg-danger">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR')) . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.updatingstats', addslashes(Text::_('COM_SECURITYCHECKPRO_UPDATING_STATS')) . '<br/><br/><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.urltoredirect', Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1', false));
		$this->document->addScriptOptions('securitycheckpro.Filemanager.failureText', addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_FAILURE')));
		$this->document->addScriptOptions('securitycheckpro.Filemanager.errorbutton', '<button class="btn btn-primary" type="button" onclick="window.location.reload();">' . addslashes(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_REFRESH_BUTTON')) . '</button>');		

        parent::display($tpl); 
    }


}