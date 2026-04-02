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
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;

class HtmlView extends BaseHtmlView {
    
	/**
	 * Indica si hemos de escanear sólo los ficheros ejecutables
	 *
	 * @var int
	 */
    public $scan_executables_only = 0;
	
	/**
	 * Indica si hemos de incluir las excepciones en la BBDD
	 *
	 * @var int
	 */
    public $file_manager_include_exceptions_in_database = 1;
	
	/**
	 * Indica la posición de los 'checkbox'
	 *
	 * @var int
	 */
    public $checkbox_position = 0;
		
	/**
	 * Indica la fecha del último chequeo de permisos
	 *
	 * @var string
	 */
    public $last_check = '';
	
	/**
	 * Número de archivos escaneados
	 *
	 * @var int
	 */
    public $files_scanned = 0;
	
	/**
	 * Número de archivos con permisos erróneos
	 *
	 * @var int
	 */
    public $incorrect_permissions = 0;
		
	/**
	 * Tiempo empleado en el escaneo
	 *
	 * @var string
	 */
    public $time_taken = '';
	
	/**
	 * El nombre del fichero de logs de escaneo de permisos
	 *
	 * @var string
	 */
    public $log_filename = '';
	
	/**
	 * Los items a mostrar
	 *
	 * @var array<array<string>>
	 */
    public $items_permissions = [];
	
	/**
	 * Número de archivos con permisos erróneos
	 *
	 * @var int
	 */
    public $files_with_incorrect_permissions = 0;
	
	/**
	 * Indica si hemos de mostrar todos los logs
	 *
	 * @var int
	 */
    public $show_all = 0;
	
	/**
	 * Indica si hay error en la BBDD
	 *
	 * @var string
	 */
    public $database_error = '';
	
	/**
	 * Indica si se ha lanzado una reparación
	 *
	 * @var string|null
	 */
    public $repair_launched = '';
	
	/**
	 * Nombre del fichero de reparación
	 *
	 * @var string|null
	 */
    public $repair_log = '';
	
	/**
	 * The model state
	 *
	 * @since  1.6
	 * @var    Registry
	 */
	public $state;
	
	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public $pagination = null;
	
	/**
	 * @var BaseModel
	 */
	public $basemodel;
	
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		  
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_MANAGER_CONTROL_PANEL_TEXT'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		 
		  ->useScript('com_securitycheckpro.Filemanager');

       	// Obtenemos el modelo de esta vista (Filemanager)
		/** @var FilemanagerModel $model */
        $model = $this->getModel();		
		
		// Crea BaseModel con el factory del componente (no lo "registra" en la vista)
        $component = Factory::getApplication()->bootComponent('com_securitycheckpro');
        /** @var MVCFactoryInterface $factory */
        $factory = $component->getMVCFactory();

        /** @var BaseModel $baseModel */
        $baseModel = $factory->createModel('Base', 'Administrator', ['ignore_request' => true]);
        $this->basemodel = $baseModel;
		
        $this->last_check = $model->loadStack("filemanager_resume", "last_check");
        $this->files_scanned = $model->loadStack("filemanager_resume", "files_scanned");
       	$this->time_taken = $model->loadStack("filemanager_resume", "time_taken");
        $this->log_filename = $model->get_log_filename("filepermissions_log", true);

        $task_ended = $model->GetCampoFilemanager("estado");

        // Obtenemos si está habilitada la opción para escanear sólo ficheros ejecutables
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $this->scan_executables_only = $params->get('scan_executables_only', 0);
		$this->file_manager_include_exceptions_in_database = $params->get('file_manager_include_exceptions_in_database', 1);
		// Consultamos dónde han de ir los 'checkboxes'
		$this->checkbox_position = $params->get('checkbox_position',0);

        // Cargamos el lenguaje del sitio
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
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

        $this->items_permissions = $model->loadStack("permissions", "file_manager");
        $this->files_with_incorrect_permissions = $model->loadStack("filemanager_resume", "files_with_incorrect_permissions");
        $this->show_all = $this->state->get('showall', 0);
        $this->database_error = $model->GetCampoFilemanager("estado");

        if (!empty($this->items_permissions)) {
            $this->pagination = $this->get('Pagination');    
        }
		
		$this->repair_launched = $app->getUserState("repair_launched", null);
       
        if (!empty($this->repair_launched)) {
            $this->repair_log = $model->getRepairLog();    
        }
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the filemanager.js script using Joomla's script options API
		// No es necesario pasar "true" a Text::_ porque addscriptoptions ya json-escapa el contenido		
		$this->document->addScriptOptions('securitycheckpro.Filemanager.repairviewlogheader', '<div class="alert alert-info" role="alert">' . Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_HEADER') . '</div>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.repairlaunched', $this->repair_launched);		
		$this->document->addScriptOptions('securitycheckpro.Filemanager.launchnewtask', '<div class="alert alert-warning" role="alert">' . Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_LAUNCH_NEW_TASK') . '</div>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.divviewlogbutton', '<button class="btn btn-primary" onclick="showLog();">' . Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_MESSAGE') . '</button>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.inprogress', '<span class="badge bg-info">' . Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS') . '</span>');		
		$this->document->addScriptOptions('securitycheckpro.Filemanager.updatingstats', Text::_('COM_SECURITYCHECKPRO_UPDATING_STATS') . '<br/><br/><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>');
		$this->document->addScriptOptions('securitycheckpro.Filemanager.urltoredirect', Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filemanager&'. Session::getFormToken() .'=1', false));		
		$this->document->addScriptOptions('securitycheckpro.Filemanager.errorbutton', '<button class="btn btn-primary" type="button" onclick="window.location.reload();">' . Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_REFRESH_BUTTON') . '</button>');		

        parent::display($tpl); 
    }


}