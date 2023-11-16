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
		
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_CONTROLPANEL'), 'securitycheckpro');
        
		$mainframe = Factory::getApplication();		
		$subscription_status_checked = $mainframe->getUserState("subscription_status_checked", 0);
				
		if (!$subscription_status_checked) {
			// Chequeamos el estado de las subscripciones
			$scp_model = new BaseModel();
			$scp_model->get_subscriptions_status();
			$mainframe->setUserState("subscription_status_checked", 1);
		}
		
		// Obtenemos los datos del modelo...
		$model = $this->getModel();		
        
        //  Parámetros del plugin
        $items= $model->getConfig();
		
		// Lista negra
		$blacklist_elements = $model->getTableData("blacklist");        
        $pagination_blacklist = null;

        $dynamic_blacklist_elements= $model->get_dynamic_blacklist_ips();

        // Lista blanca
		$whitelist_elements = $model->getTableData("whitelist");		
		$pagination_whitelist = null;

        
        $firewall_plugin_enabled = $model->PluginStatus(1);
        $cron_plugin_enabled = $model->PluginStatus(2);
        $update_database_plugin_enabled = $model->PluginStatus(3);
        $update_database_plugin_exists = $model->PluginStatus(4);
        $spam_protection_plugin_enabled = $model->PluginStatus(5);
        $spam_protection_plugin_exists = $model->PluginStatus(6);
        $trackactions_plugin_exists = $model->PluginStatus(8);
        $logs_pending = $model->LogsPending();
        $scpro_plugin_id = $model->get_plugin_id(1);
        $scprocron_plugin_id = $model->get_plugin_id(2);
        $params = ComponentHelper::getParams('com_securitycheckpro');
        // ... y el tipo de servidor web
        $server = $mainframe->getUserState("server", 'apache');
        // ... y las estadísticas de los logs
        $last_year_logs = $model->LogsByDate('last_year');
        $this_year_logs = $model->LogsByDate('this_year');
        $last_month_logs = $model->LogsByDate('last_month');
        $this_month_logs = $model->LogsByDate('this_month');
        $last_7_days = $model->LogsByDate('last_7_days');
        $yesterday = $model->LogsByDate('yesterday');
        $today = $model->LogsByDate('today');
        $total_firewall_rules = $model->LogsByType('total_firewall_rules');
        $total_blocked_access = $model->LogsByType('total_blocked_access');
        $total_user_session_protection = $model->LogsByType('total_user_session_protection');
        $easy_config_applied = $model->Get_Easy_Config();
        // Versiones de los componentes instalados
        $version_scp = $model->get_version('securitycheckpro');
        if ($update_database_plugin_exists) {
            $version_update_database = $model->get_version('databaseupdate');
            $this->version_update_database =  $version_update_database;
        }
        if ($trackactions_plugin_exists) {
            $version_trackactions = $model->get_version('trackactions');
            $this->version_trackactions = $version_trackactions;
        }
		
		// Obtenemos el status de la seguridad       
        $overall = new SysinfoModel();
        $overall = $overall->getInfo();        
        $overall = $overall['overall_joomla_configuration'];
		
		// Download id 
		// Get download id stored in component
		$app = ComponentHelper::getParams('com_securitycheckpro');
		$this->downloadid = $app->get('downloadid');
		
		$downloadid_core_data = $model->get_extra_query_update_sites_table('com_securitycheckpro');
				
		if ( !empty($this->downloadid) ) {
			if ( ($downloadid_core_data <> "error") && ($this->downloadid <> $downloadid_core_data->extra_query) ) {				
				// Downloads id are different. Let's update the 'update_sites_table' with the one set into the component
				$model->update_extra_query_update_sites_table((int)$downloadid_core_data->update_site_id,$this->downloadid);
				$this->downloadid = $downloadid_core_data->extra_query;
			}
		} else {
			if ( ($downloadid_core_data <> "error") && (!empty($downloadid_core_data->extra_query)) ) {				
				$this->downloadid = $downloadid_core_data->extra_query;
			}
		}
		
		// Ponemos los datos en el template
        $this->firewall_plugin_enabled =  $firewall_plugin_enabled;
        $this->cron_plugin_enabled =  $cron_plugin_enabled;
        $this->update_database_plugin_enabled =  $update_database_plugin_enabled;
        $this->update_database_plugin_exists =  $update_database_plugin_exists;        
        $this->spam_protection_plugin_enabled =  $spam_protection_plugin_enabled;
        $this->spam_protection_plugin_exists =  $spam_protection_plugin_exists;
        $this->trackactions_plugin_exists =  $trackactions_plugin_exists;
        $this->logs_pending =  $logs_pending;
        $this->scpro_plugin_id =  $scpro_plugin_id;
        $this->scprocron_plugin_id =  $scprocron_plugin_id;
        $this->server =  $server;
        $this->last_year_logs =  $last_year_logs;
        $this->this_year_logs =  $this_year_logs;
        $this->last_month_logs =  $last_month_logs;        
        $this->this_month_logs =  $this_month_logs;
        $this->last_7_days =  $last_7_days;
        $this->yesterday =  $yesterday;
        $this->today =  $today;
        $this->total_firewall_rules =  $total_firewall_rules;
        $this->total_blocked_access =  $total_blocked_access;
        $this->total_user_session_protection =  $total_user_session_protection;
        $this->easy_config_applied =  $easy_config_applied;
        $this->overall =  $overall;
        $this->blacklist_elements =  $blacklist_elements;
        $this->dynamic_blacklist_elements =  $dynamic_blacklist_elements;        
        $this->whitelist_elements =  $whitelist_elements;        
        $this->version_scp =  $version_scp;
        $this->lock_status = $model->lock_status();
        
		
        parent::display($tpl);
    }


}