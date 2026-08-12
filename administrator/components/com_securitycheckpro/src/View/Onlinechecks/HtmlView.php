<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Onlinechecks;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\OnlinechecksModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

class HtmlView extends BaseHtmlView {

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
	 * Los items a mostrar
	 *
	 * @var list<\stdClass>
	 */
    public $items = [];

	/**
	 * Array de rutas
	 *
	 * @var array<it,string>
	 */
    public $logPaths = [];

	/**
	 * @var BaseModel
	 */
	public $basemodel;

	/**
	 * Clave OPSWAT
	 *
	 * @var string
	 */
	public string $opswat_key = '';

	/**
	 * Tipo de envío configurado ('Hashes' | 'Files')
	 *
	 * @var string
	 */
	public string $online_submission_type = '';

	/**
	 * Número total de envíos registrados (sin filtrar por búsqueda)
	 *
	 * @var int
	 */
	public int $submissions_total = 0;

	/**
	 * Número total de archivos comprobados en todos los envíos (sin filtrar por búsqueda)
	 *
	 * @var int
	 */
	public int $files_total = 0;

	/**
	 * Número total de amenazas encontradas en todos los envíos (sin filtrar por búsqueda)
	 *
	 * @var int
	 */
	public int $threats_total = 0;

	/**
	 * Fecha del envío más reciente ('' si no hay ninguno)
	 *
	 * @var string
	 */
	public string $last_submission = '';

    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app     = Factory::getApplication();
		  
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_ONLINE_CHECK_LOGS'), 'securitycheckpro');
		if ($app->getIdentity()->authorise('logs.export', 'com_securitycheckpro')) {
			ToolbarHelper::custom('download_log_file', 'out-2', 'out-2', 'COM_SECURITYCHECKPRO_DOWNLOAD_LOG');
		}
		if ($app->getIdentity()->authorise('logs.deleteall', 'com_securitycheckpro')) {
			ToolbarHelper::custom('delete_files', 'remove', 'remove', 'COM_SECURITYCHECKPRO_DELETE_FILE');
		}
        ToolbarHelper::custom('view_log', 'eye', 'eye', 'COM_SECURITYCHECKPRO_REPAIR_VIEW_LOG_MESSAGE');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		 
		  ->useScript('com_securitycheckpro.Onlinechecks')
		  ->useScript('list-view');
                        
        // Obtenemos el modelo de esta vista (Onlinechecks)
		/** @var OnlinechecksModel $model */
       	$model= $this->getModel();
		
		$this->items       = $this->get('Items');
		$this->pagination    = $model->getPagination();
		$this->state         = $model->getState();

		// BaseModel (helpers compartidos: relativeTime, renderSelect...)
		$this->basemodel = new BaseModel();

		// Configuración relevante (igual que en la vista de Malware scan)
		$params = ComponentHelper::getParams('com_securitycheckpro');
		$this->opswat_key = (string) $params->get('opswat_key', '');
		$this->online_submission_type = (string) $params->get('online_submission_type', 'Hashes');

		// Métricas globales (no afectadas por el filtro de búsqueda de la tabla)
		$stats = $model->getSubmissionStats();
		$this->submissions_total = $stats['total'];
		$this->files_total       = $stats['files'];
		$this->threats_total     = $stats['threats'];
		$this->last_submission   = !empty($this->items) ? (string) ($this->items[0]->scan_date ?? '') : '';

		// Filtro
        $managedevices_search = $this->state->get('filter.onlinechecks_search');

		$this->logPaths = [];
		foreach ($this->items as $row) {
			$id = $row->id; // id del escaneo
			$this->logPaths[$id] = $row->filename; // nombre del fichero de escaneo online
		}

		Text::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION');
		Text::script('JLIB_HTML_PLEASE_SELECT_ONLY_ONE_ITEM');
		Text::script('JLOADING');
		Text::script('COM_SECURITYCHECKPRO_SELECT_ONLY_ONE_FILE');

		$this->document->addScriptOptions('securitycheckpro.Onlinechecks.logspath', $this->logPaths); 
        
        parent::display($tpl);  
    }


}