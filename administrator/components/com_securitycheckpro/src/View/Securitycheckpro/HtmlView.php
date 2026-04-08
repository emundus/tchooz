<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Securitycheckpro;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\SecuritycheckproModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\CpanelModel;

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
	 * @var array<string,mixed>|null
	 */
    public $items = [];
	
	/**
	 * Indica si el plugin update database está instalado
	 *
	 * @var bool
	 */
    public $update_database_plugin_exists = false;
	
	/**
	 * Indica si el plugin update database está habilitado
	 *
	 * @var bool
	 */
    public $update_database_plugin_enabled = false;
	
	/**
	 * Fecha del último chequeo del plugin update database
	 *
	 * @var string
	 */
    public $last_check = '';
	
	/**
	 * Versión de la BBDD de update database
	 *
	 * @var string
	 */
    public $database_version = '';
	
	/**
	 * Mensaje del último chequeo
	 *
	 * @var string|null
	 */
    public $database_message = '';
	
	/**
	 * Id del plugin update database
	 *
	 * @var int
	 */
    public $plugin_id = 0;
	
	/**
	 * Última actualización de la BBDD
	 *
	 * @var string
	 */
    public $last_update = '';
	
	/**
	 * Componentes eliminados
	 *
	 * @var string
	 */
    public $eliminados = '';
	
	/**
	 * Indica si el core se ha actualizado
	 *
	 * @var string
	 */
    public $core_actualizado = '';
	
	/**
	 * Componentes actualizados
	 *
	 * @var string
	 */
    public $comps_actualizados = '';
	
	/**
	 * 
	 *
	 * @var string
	 */
    public $comp_ok = '';
	
	/**
	 * @var BaseModel
	 */
	public $basemodel;	
	
	/**
	 * Devuelve la fecha de creación del manifest del componente formateada.
	 * Intenta varios formatos comunes y, si no encaja, usa strtotime().
	 *
	 * @return string|null  Fecha formateada o null si no se pudo obtener
	 */
	protected function getManifestCreationDateFormatted(): ?string
	{
		$xmlPath = JPATH_ADMINISTRATOR . '/components/com_securitycheckpro/securitycheckpro.xml';
		
		if (!is_file($xmlPath)) {
			return null;
		}

		// Carga el XML de forma segura
		$xml = @simplexml_load_file($xmlPath);
		if ($xml === false) {
			return null;
		}
		
		$raw = isset($xml->creationDate) ? trim((string) $xml->creationDate) : '';
		if ($raw === '') {
			return null;
		}

		// Intenta parsear con formatos habituales del ecosistema Joomla
		$formats = [
			'd-m-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y',
			'd-m-y', 'd/m/y',
			'd M Y', 'M d Y',
		];

		foreach ($formats as $fmt) {
			$dt = \DateTime::createFromFormat($fmt, $raw);
			if ($dt instanceof \DateTime) {
				// Normalizamos a Y-m-d para que HTMLHelper::date lo entienda siempre
				return HTMLHelper::_(
					'date',
					$dt->format('Y-m-d'),
					Text::_('DATE_FORMAT_LC4')
				);
			}
		}

		// Fallback con strtotime (por si viene en otro formato compatible)
		$ts = @strtotime($raw);
		if ($ts !== false && $ts > 0) {
			return HTMLHelper::_('date', $ts, Text::_('DATE_FORMAT_LC4'));
		}

		return null;
	}
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		   
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | '.Text::_('COM_SECURITYCHECKPRO_VULNERABILITIES'), 'securitycheckpro');
        ToolbarHelper::custom('mostrar', 'database', 'database', 'COM_SECURITYCHECKPRO_LIST', false);
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		  
		  ->useScript('com_securitycheckpro.Securitycheckpros');
		  
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app     = Factory::getApplication();

        // Obtenemos el modelo de esta vista (Securitycheckpro)
		/** @var SecuritycheckproModel $model */
       	$model= $this->getModel();
		
		// BaseModel
        $this->basemodel = new BaseModel();
		
        // CpanelModel
        $cpanelmodel = new CpanelModel();
		
        $this->update_database_plugin_enabled = $this->basemodel->PluginStatus(3);
        $this->update_database_plugin_exists = $this->basemodel->PluginStatus(4);
		        
        if ($this->update_database_plugin_exists) {
			$this->plugin_id   = $cpanelmodel->get_plugin_id(3);
			$this->last_update = (string) $model->get_last_update();
			$this->last_check = $this->basemodel->getCampoBbdd('securitycheckpro_update_database', 'last_check');
			$this->database_version = $this->basemodel->getCampoBbdd('securitycheckpro_update_database', 'version');
			$this->database_message = $this->basemodel->getCampoBbdd('securitycheckpro_update_database', 'message');
		}

		// Fallback al manifest si no hay plugin o no devolvió nada útil
		if (empty($this->last_update)) {
			$fromManifest = $this->getManifestCreationDateFormatted();
			if (!empty($fromManifest)) {
				$this->last_update = $fromManifest;
			} else {
				// Último recurso: muestra algo genérico (opcional)
				$this->last_update = Text::_('JUNKNOWN');
			}
		}


        // Filtro por tipo de extensión
        $this->state= $this->get('State');
        $type= $this->state->get('filter.extension_type');
        $vulnerable= $this->state->get('filter.vulnerable');
		
        $this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		
		$jinput = Factory::getApplication();
		
        $vulnerabilies_table_updated = $app->getUserState('show_vulnerabilities_table_updated', false);
		if ($vulnerabilies_table_updated) {			
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_VULN_TABLE_UPDATED'), 'info');
			$app->setUserState('show_vulnerabilities_table_updated', false);			
		}
		

        parent::display($tpl);
    }


}