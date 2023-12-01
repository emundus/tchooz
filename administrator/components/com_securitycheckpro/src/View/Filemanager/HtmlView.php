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

        parent::display($tpl); 
    }


}