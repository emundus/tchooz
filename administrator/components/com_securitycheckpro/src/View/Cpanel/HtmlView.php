<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Cpanel;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\SysinfoModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\CpanelModel;
use Joomla\CMS\Application\CMSApplication;

class HtmlView extends BaseHtmlView {
	
	/**
	 * Indica si el firewall está habilitado
	 *
	 * @var  bool
	 */
    public $firewall_plugin_enabled = false;
	
	/**
	 * Indica si el plugin cron está habilitado
	 *
	 * @var  bool
	 */
    public $cron_plugin_enabled = false;
	
	/**
	 * Indica si el plugin update database está habilitado
	 *
	 * @var  bool
	 */
    public $update_database_plugin_enabled = false;
	
	/**
	 * Indica si el plugin update database está instalado
	 *
	 * @var  bool
	 */
    public $update_database_plugin_exists = false;
	
	/**
	 * Indica si el plugin spam protection está habilitado
	 *
	 * @var  bool
	 */
    public $spam_protection_plugin_enabled = false;
	
	/**
	 * Indica si el plugin spam protection está instalado
	 *
	 * @var  bool
	 */
    public $spam_protection_plugin_exists = false;
	
	/**
	 * Indica si el plugin track actions está instalado
	 *
	 * @var  bool
	 */
    public $trackactions_plugin_exists = false;
	
	/**
	 * Id del plugin Securitycheck Pro
	 *
	 * @var  int
	 */
    public $scpro_plugin_id = 0;
	
	/**
	 * Id del plugin Securitycheck Pro Cron
	 *
	 * @var  int
	 */
    public $scprocron_plugin_id = 0;
	
	/**
	 * Tipo de servidor
	 *
	 * @var  string
	 */
    public $server = 'apache';
	
	/**
	 * Número de logs del año pasado
	 *
	 * @var  int
	 */
    public $last_year_logs = 0;
	
	/**
	 * Número de logs de este año
	 *
	 * @var  int
	 */
    public $this_year_logs = 0;
	
	/**
	 * Número de logs del mes pasado
	 *
	 * @var  int
	 */
    public $last_month_logs = 0;
	
	/**
	 * Número de logs de este mes
	 *
	 * @var  int
	 */
    public $this_month_logs = 0;
	
	/**
	 * Número de logs de los últimos 7 días
	 *
	 * @var  int
	 */
    public $last_7_days = 0;
	
	/**
	 * Número de logs de ayer
	 *
	 * @var  int
	 */
    public $yesterday = 0;
	
	/**
	 * Número de logs de hoy
	 *
	 * @var  int
	 */
    public $today = 0;
	
	/**
	 * Número de logs de reglas del firewall
	 *
	 * @var  int
	 */
    public $total_firewall_rules = 0;
	
	/**
	 * Número de logs de accesos bloqueados
	 *
	 * @var  int
	 */
    public $total_blocked_access = 0;
	
	/**
	 * Número de logs de protección de sesión de usuario
	 *
	 * @var  int
	 */
    public $total_user_session_protection = 0;
	
	/**
	 * Versión del firewall
	 *
	 * @var  string
	 */
    public $version_scp = '';
	
    /**
	 * Versión del plugin update database
	 *
	 * @var  string
	 */
    public $version_update_database = '';
	
	/**
	 * Versión del plugin track actions
	 *
	 * @var  string
	 */
    public $version_trackactions = '';
	
	/**
	 * Overall de la configuración de Joomla
	 *
	 * @var  int
	 */
    public $overall = 0;
	
	/**
	 * Download id
	 *
	 * @var  string
	 */
    public $downloadid = '';
	
	/**
	 * Elementos de la lista negra
	 *
	 * @var array<string>|null
	 */
    public $blacklist_elements = null;
	
	/**
	 * Elementos de la lista blanca
	 *
	 * @var array<string>|null
	 */
    public $whitelist_elements = null;
	
	/**
	 * Elementos de la lista ngra dinámica
	 *
	 * @var array<string>|null
	 */
    public $dynamic_blacklist_elements = null;
	
	/**
	 * Indica si las tablas están bloqueadas
	 *
	 * @var int
	 */
    public $lock_status = 0;
	
	/**
	 * Indica si la opción 'Easy config' está aplicada
	 *
	 * @var bool
	 */
    public $easy_config_applied = false;
		
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CONTROLPANEL'), 'securitycheckpro');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')
		  ->useScript('bootstrap.tab')
		  ->useStyle('com_securitycheckpro.circle')
		  ->useScript('com_securitycheckpro.chart')
		  ->useScript('com_securitycheckpro.Cpanel');		
        
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();		
		$subscription_status_checked = $mainframe->getUserState("subscription_status_checked", 0);
				
		if (!$subscription_status_checked) {
			// Chequeamos el estado de las subscripciones
			$scp_model = new BaseModel();
			$scp_model->GetSubscriptionsStatus();
			$mainframe->setUserState("subscription_status_checked", 1);
		}
		
		// Obtenemos el modelo de esta vista (Cpanel)
		/** @var CpanelModel $model */
		$model = $this->getModel();		
        
        //  Parámetros del plugin
        $items= $model->getConfig();
		
		// Lista negra
		$this->blacklist_elements = $model->getTableData("blacklist");   
		// Lista negra dinámica	
        $this->dynamic_blacklist_elements= $model->getTableData("dynamic_blacklist");
        // Lista blanca
		$this->whitelist_elements = $model->getTableData("whitelist");		
		

        
        $this->firewall_plugin_enabled = $model->PluginStatus(1);
        $this->cron_plugin_enabled = $model->PluginStatus(2);
        $this->update_database_plugin_enabled = $model->PluginStatus(3);
        $this->update_database_plugin_exists = $model->PluginStatus(4);
        $this->spam_protection_plugin_enabled = $model->PluginStatus(5);
        $this->spam_protection_plugin_exists = $model->PluginStatus(6);
        $this->trackactions_plugin_exists = $model->PluginStatus(8);
        
        $this->scpro_plugin_id = $model->get_plugin_id(1);
        $this->scprocron_plugin_id = $model->get_plugin_id(2);
        $params = ComponentHelper::getParams('com_securitycheckpro');
        // ... y el tipo de servidor web
        $this->server = $mainframe->getUserState("server", 'apache');
        // ... y las estadísticas de los logs
        $this->last_year_logs = $model->LogsByDate('last_year');
        $this->this_year_logs = $model->LogsByDate('this_year');
        $this->last_month_logs = $model->LogsByDate('last_month');
        $this->this_month_logs = $model->LogsByDate('this_month');
        $this->last_7_days = $model->LogsByDate('last_7_days');
        $this->yesterday = $model->LogsByDate('yesterday');
        $this->today = $model->LogsByDate('today');
        $this->total_firewall_rules = $model->LogsByType('total_firewall_rules');
        $this->total_blocked_access = $model->LogsByType('total_blocked_access');
        $this->total_user_session_protection = $model->LogsByType('total_user_session_protection');
        $this->easy_config_applied = $model->Get_Easy_Config();
        // Versiones de los componentes instalados
        $this->version_scp = $model->get_version('securitycheckpro');
        if ($this->update_database_plugin_exists) {
            $this->version_update_database = $model->get_version('databaseupdate');           
        }
        if ($this->trackactions_plugin_exists) {
            $this->version_trackactions = $model->get_version('trackactions');           
        }
		
		// Obtenemos el status de la seguridad       
        $overall_model = new SysinfoModel();
        $overall_info = $overall_model->getInfo(); 
        $this->overall = $overall_model->getOverall($overall_info,1);
		
		// Download id 
		// Get download id stored in component
		$app = ComponentHelper::getParams('com_securitycheckpro');
		$this->downloadid = $app->get('downloadid');
		
		$downloadData = $model->get_extra_query_update_sites_table('com_securitycheckpro');

		if ($downloadData === 'error') {			
			return;
		}

		// Extra_query remoto (puede ser null)
		$remoteDlid = $downloadData?->extra_query ?? null;
		$siteId     = isset($downloadData->update_site_id) ? (int) $downloadData->update_site_id : 0;

		if (!empty($this->downloadid)) {
			// Tengo DLID en el componente
			if ($siteId > 0 && $this->downloadid !== $remoteDlid) {
				// Si el remoto está vacío o es distinto, subimos el DLID del componente a la tabla update_sites
				$model->update_extra_query_update_sites_table($siteId, $this->downloadid);
			}
		} else {
			// No tengo DLID en el componente: si hay uno remoto, lo importo
			if ($remoteDlid !== null && $remoteDlid !== '') {
				$this->downloadid = $remoteDlid;
			}
		}
		
        $this->lock_status = $model->lockStatus();
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Cpanel.blockedaccessText', Text::_('COM_SECURITYCHECKPRO_BLOCKED_ACCESS',true));
		$this->document->addScriptOptions('securitycheckpro.Cpanel.userandsessionprotectionText', Text::_('COM_SECURITYCHECKPRO_USER_AND_SESSION_PROTECTION',true));
		$this->document->addScriptOptions('securitycheckpro.Cpanel.firewallrulesappliedText', Text::_('COM_SECURITYCHECKPRO_FIREWALL_RULES_APLIED',true));
		$this->document->addScriptOptions('securitycheckpro.Cpanel.setdefaultconfigconfirmText', Text::_('COM_SECURITYCHECKPRO_SET_DEFAULT_CONFIG_CONFIRM',true));
		$this->document->addScriptOptions('securitycheckpro.Cpanel.totalblockedaccess', (int) $this->total_blocked_access);
		$this->document->addScriptOptions('securitycheckpro.Cpanel.totalusersessionprotection', (int) $this->total_user_session_protection);
		$this->document->addScriptOptions('securitycheckpro.Cpanel.totalfirewallrules', (int) $this->total_firewall_rules);        
		
        parent::display($tpl);
    }


}