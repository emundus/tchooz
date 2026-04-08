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
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilesintegrityModel;

class HtmlView extends BaseHtmlView {
    
	/**
	 * The model state
	 *
	 * @since  1.6
	 * @var    Registry
	 */
	public $state;
	
	/**
	 * Indica la fecha del último chequeo de integridad
	 *
	 * @var string
	 */
    public $last_check_integrity = '';
	
	/**
	 * Número de archivos escaneados
	 *
	 * @var int
	 */
    public $files_scanned_integrity = 0;
	
	/**
	 * Número de archivos con integridad incorrecta
	 *
	 * @var int
	 */
    public $files_with_bad_integrity = 0;
	
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
	 * Información del último escaneo
	 *
	 * @var array<string|int>|array<array<string>>|int|string|null
	 */
    public $last_scan_info = [];
	
	/**
	 * Mensaje
	 *
	 * @var string
	 */
    public $message_info = '';
	
	/**
	 * Indica si hemos de incluir las excepciones en la BBDD
	 *
	 * @var int
	 */
    public $file_manager_include_exceptions_in_database = 1;
	
	/**
	 * Los items a mostrar
	 *
	 * @var array<array<string>>
	 */
    public $items = [];
	
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
	 * Extensiones instaladas desde el último escaneo de integridad
	 *
	 * @var array<string,string>|bool|null
	 */
    public $installs = [];
	
	/**
	 * Algoritmo usado
	 *
	 * @var string
	 */
    public $hash_alg = '';
	
	/**
	 * Indica si hemos de escanear sólo los ficheros ejecutables
	 *
	 * @var int
	 */
    public $scan_executables_only = 0;

	/**
	 * Indica la posición de los 'checkbox'
	 *
	 * @var int
	 */
    public $checkbox_position = 0;
	
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
		  
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CPANEL_FILE_INTEGRITY_CONTROL_PANEL_TEXT'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		 
		  ->useScript('com_securitycheckpro.Fileintegrity');
		
		// Obtenemos el modelo de esta vista (Filesintegrity)
		/** @var FilesintegrityModel $model */
       	$model               = $this->getModel();
		
		$component = Factory::getApplication()->bootComponent('com_securitycheckpro');
        /** @var MVCFactoryInterface $factory */
        $factory = $component->getMVCFactory();

        /** @var BaseModel $baseModel */
        $baseModel = $factory->createModel('Base', 'Administrator', ['ignore_request' => true]);
        $this->basemodel = $baseModel;
		
		$this->state         = $model->getState();
				
        $this->last_check_integrity = $model->loadStack("fileintegrity_resume", "last_check_integrity");
        $this->files_scanned_integrity = $model->loadStack("fileintegrity_resume", "files_scanned_integrity");
        $this->files_with_bad_integrity = $model->loadStack("fileintegrity_resume", "files_with_bad_integrity");
		$this->time_taken = $model->loadStack("fileintegrity_resume", "time_taken");
		$this->last_scan_info = $model->loadStack("fileintegrity_resume", "last_scan_info");
        $this->log_filename = $model->get_log_filename("fileintegrity_log", true);
		$this->message_info = Text::sprintf('COM_SECURITYCHECKPRO_SCAN_INFO_MESSAGE',  Text::_('COM_SECURITYCHECKPRO_SCAN_ALL_FILES_INFO_MESSAGE'));
								
        $task_ended = $model->GetCampoFilemanager("estado_integrity");

        // Obtenemos el algoritmo seleccionado para crear el valor hash y si está habilitada la opción para escanear sólo ficheros ejecutables
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $this->hash_alg = $params->get('file_integrity_hash_alg', 'SHA1');
        $this->scan_executables_only = $params->get('scan_executables_only', 0);
		$this->file_manager_include_exceptions_in_database = $params->get('file_manager_include_exceptions_in_database', 1);
		// Consultamos dónde han de ir los 'checkboxes'
		$this->checkbox_position = $params->get('checkbox_position','0');
	
		if ($this->scan_executables_only) {
			$this->message_info = Text::sprintf('COM_SECURITYCHECKPRO_SCAN_INFO_MESSAGE', Text::_('COM_SECURITYCHECKPRO_SCAN_ONLY_EXECUTABLE_FILES_INFO_MESSAGE'));
		}

        // Filtro por tipo de extensión
        $fileintegrity_search = $this->state->get('filter.fileintegrity_search');
        $filter_fileintegrity_status = $this->state->get('filter.fileintegrity_status');

        // Establecemos el valor del filtro 'fileintegrity_status' a cero para que muestre sólo los permisos incorrectos
        if ($filter_fileintegrity_status == '') {
            $this->state->set('filter.fileintegrity_status', 0);
        }

        $this->items = $model->loadStack("integrity", "file_integrity");	
		$this->show_all = $this->state->get('showall', 0);
        $this->database_error = $model->GetCampoFilemanager("estado_integrity");          
        $this->installs = $model->getInstalls();
    
        if (!empty($this->items)) {
            $this->pagination = $model->getPagination();           
            ToolbarHelper::custom('mark_all_unsafe_files_as_safe', 'flag-2', 'flag-2', 'COM_SECURITYCHECKPRO_FILEINTEGRITY_MARK_ALL_UNSAFE_FILES_AS_SAFE', false);
            ToolbarHelper::custom('mark_checked_files_as_safe', 'flag', 'flag', 'COM_SECURITYCHECKPRO_FILEINTEGRITY_MARK_CHECKED_FILES_AS_SAFE');
            ToolbarHelper::custom('export_logs_integrity', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_EXPORT_INFO_CSV', false);
        }
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the fileintegrity.js script using Joomla's script options API
		
		// No es necesario pasar "true" a Text::_ porque addscriptoptions ya json-escapa el contenido
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.repairviewlogheader', '<div class="alert alert-info" role="alert">' . Text::_('COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_HEADER') . '</div>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.end', '<span class="badge bg-success">' . Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED') . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.inprogress', '<span class="badge bg-info">' . Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_IN_PROGRESS',true) . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.error', '<span class="badge bg-danger">' . Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR') . '</span>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.updatingstats', Text::_('COM_SECURITYCHECKPRO_UPDATING_STATS') . '<br/><br/><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>');
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.urltoredirect', Route::_('index.php?option=com_securitycheckpro&controller=filemanager&view=filesintegrity&'. Session::getFormToken() .'=1', false));
		$this->document->addScriptOptions('securitycheckpro.Fileintegrity.errorbutton', '<button class="btn btn-primary" type="button" onclick="window.location.reload();">' . Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_REFRESH_BUTTON') . '</button>');		

        parent::display($tpl); 
    }


}