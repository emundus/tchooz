<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\View\Firewallconfig;

defined('_JEXEC') or die;

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
		  
		ToolBarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_WAF_CONFIG'), 'securitycheckpro');
		ToolBarHelper::save();
        ToolBarHelper::apply();
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		 
		  ->useScript('com_securitycheckpro.Firewallconfig');
		
		// Obtenemos el modelo
        $model = $this->getModel();
		
		// Filtro
        $this->state= $model->getState();
        $lists = $this->state->get('filter.lists_search');       

        //  Parámetros del plugin
        $items= $model->getConfig();
				
		// Lista negra
		$blacklist_elements = $model->getTableData("blacklist");        
        $pagination_blacklist = null;
		
        if ( !is_null($blacklist_elements) ) {            
            $blacklist_elements = $model->filter_data($blacklist_elements, $pagination_blacklist);
        }
		
		// Lista negra dinámica
		$dynamic_blacklist_elements = $model->getTableData("dynamic_blacklist");        
        $pagination_dynamic_blacklist = null;
		
        if ( !is_null($dynamic_blacklist_elements) ) {            
            $dynamic_blacklist_elements = $model->filter_data($dynamic_blacklist_elements, $pagination_dynamic_blacklist);
        }

        // Lista blanca
		$whitelist_elements = $model->getTableData("whitelist");		
		$pagination_whitelist = null;

        if ( !is_null($whitelist_elements) ) {                
            $whitelist_elements = $model->filter_data($whitelist_elements, $pagination_whitelist);
        }

        // Información para la barra de navegación
        $logs_pending = $model->LogsPending();
        $trackactions_plugin_exists = $model->PluginStatus(8);
        $this->logs_pending = $logs_pending;
        $this->trackactions_plugin_exists = $trackactions_plugin_exists;
		
		$current_ip = "";
		$range_example = "";
		// Contribution of George Acu - thanks!
		if (isset($_SERVER['HTTP_TRUE_CLIENT_IP']))
		{
			# CloudFlare specific header for enterprise paid plan, compatible with other vendors
			$current_ip = $_SERVER['HTTP_TRUE_CLIENT_IP']; 
		} elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
		{
			# another CloudFlare specific header available in all plans, including the free one
			$current_ip = $_SERVER['HTTP_CF_CONNECTING_IP']; 
		} elseif (isset($_SERVER['HTTP_INCAP_CLIENT_IP'])) 
		{
			// Users of Incapsula CDN
			$current_ip = $_SERVER['HTTP_INCAP_CLIENT_IP']; 
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) 
		{
			# specific header for proxies
			$current_ip = $_SERVER['HTTP_X_FORWARDED_FOR']; 
			$result_ip_address = explode(', ', $clientIpAddress);
			$current_ip = $result_ip_address[0];
		} elseif (isset($_SERVER['REMOTE_ADDR']))
		{
			# this one would be used, if no header of the above is present
			$current_ip = $_SERVER['REMOTE_ADDR']; 
		}

		$range_example = explode('.', $current_ip);
		$range_example[2] = "*";
		$range_example[3] = "*";
		$range_example = implode('.', $range_example);
		$cidr_v4_example = $current_ip . "/20";

        $this->blacklist_elements = $blacklist_elements;
        $this->dynamic_blacklist_elements = $dynamic_blacklist_elements;
        $this->whitelist_elements = $whitelist_elements; 
		$this->dynamic_blacklist = $items['dynamic_blacklist'];
        $this->dynamic_blacklist_time = $items['dynamic_blacklist_time'];
        $this->dynamic_blacklist_counter = $items['dynamic_blacklist_counter'];
        $this->blacklist_email = $items['blacklist_email'];
        $this->priority1 = $items['priority1'];
        $this->priority2 = $items['priority2'];
        $this->priority3 = $items['priority3'];
		$this->current_ip = $current_ip;
		$this->range_example = $range_example;
		$this->cidr_v4_example = $cidr_v4_example;
        
        // Pestaña methods
        $methods= null;
        if (!is_null($items['methods'])) {
            $this->methods = $items['methods'];
        }

        // Pestaña Mode
        $mode= null;
        if (!is_null($items['mode'])) {
            $this->mode = $items['mode'];
        }

        // Pestaña Logs
        $logs_attacks= 1;
        $scp_delete_period= 60;
        $log_limits_per_ip_and_day = null;
        $add_geoblock_logs = null;
        $add_access_attempts_logs = null;

        if (!is_null($items['scp_delete_period'])) {
            $scp_delete_period = $items['scp_delete_period'];    
        }

        if (!is_null($items['logs_attacks'])) {
            $logs_attacks = $items['logs_attacks'];    
        }

        if (!is_null($items['log_limits_per_ip_and_day'])) {
            $log_limits_per_ip_and_day = $items['log_limits_per_ip_and_day'];    
        }

        if (!is_null($items['add_geoblock_logs'])) {
            $add_geoblock_logs = $items['add_geoblock_logs'];    
        }

        if (!is_null($items['add_access_attempts_logs'])) {
            $add_access_attempts_logs = $items['add_access_attempts_logs'];    
        }

        $this->logs_attacks = $logs_attacks;
        $this->scp_delete_period = $scp_delete_period;
        $this->log_limits_per_ip_and_day = $log_limits_per_ip_and_day;
        $this->add_geoblock_logs = $add_geoblock_logs;
        $this->add_access_attempts_logs = $add_access_attempts_logs;

        // Pestaña Redirection
        $redirect_after_attack= null;
        $redirect_options = null;

        if (!is_null($items['redirect_after_attack'])) {
            $redirect_after_attack = $items['redirect_after_attack'];    
        }

        if (!is_null($items['redirect_options'])) {
            $redirect_options = $items['redirect_options'];    
        }

        $redirect_url = $items['redirect_url'];    
        $custom_code = $items['custom_code'];

        if (is_null($custom_code)) {
            $custom_code = "<h1 style=\"text-align:center;\">" . Text::_('COM_SECURITYCHECKPRO_403_ERROR') . "</h1>";
        }

        $this->redirect_after_attack = $redirect_after_attack;
        $this->redirect_options = $redirect_options;
        $this->redirect_url = $redirect_url;
        $this->custom_code = $custom_code;

        // Pestaña Second level
        $second_level= null;
        $second_level_redirect = null;
        $second_level_limit_words = null;
        $second_level_words = null;

        if (!is_null($items['second_level'])) {
            $second_level = $items['second_level'];    
        }

        if (!is_null($items['second_level_redirect'])) {
            $second_level_redirect = $items['second_level_redirect'];    
        }

        if (!is_null($items['second_level_limit_words'])) {
            $second_level_limit_words = $items['second_level_limit_words'];    
        }

        if (!is_null($items['second_level_words'])) {
            $second_level_words = $items['second_level_words'];    
        }

        $this->second_level = $second_level;
        $this->second_level_redirect = $second_level_redirect;
        $this->second_level_limit_words = $second_level_limit_words;

        // Si el string "second_level_words" contiene comas significa que no está codificado en base64. Desde la versión 3.1.6 se codifica así para evitar problemas con una regla de mod_security.
        if (substr_count($second_level_words, ",") > 2) {    
            $second_level_words = base64_encode($second_level_words);
        }

        $this->second_level_words = $second_level_words;

        // Pestaña Email notifications
        $email_active= null;
        $email_subject = null;
        $email_body = null;
        $email_add_applied_rule = null;
        $email_to = null;
        $email_from_domain = null;
        $email_from_name = null;
        $email_max_number = null;

        if (!is_null($items['email_active'])) {
            $email_active = $items['email_active'];    
        }

        if (!is_null($items['email_subject'])) {
            $email_subject = $items['email_subject'];    
        }

        if (!is_null($items['email_body'])) {
            $email_body = $items['email_body'];    
        }

        if (!is_null($items['email_add_applied_rule'])) {
            $email_add_applied_rule = $items['email_add_applied_rule'];    
        }

        if (!is_null($items['email_to'])) {
            $email_to = $items['email_to'];    
        }

        if (!is_null($items['email_from_domain'])) {
            $email_from_domain = $items['email_from_domain'];    
        }

        if (!is_null($items['email_from_name'])) {
            $email_from_name = $items['email_from_name'];    
        }

        if (!is_null($items['email_max_number'])) {
            $email_max_number = $items['email_max_number'];    
        }

        $this->email_active = $email_active;
        $this->email_subject = $email_subject;
        $this->email_body = $email_body;
        $this->email_add_applied_rule = $email_add_applied_rule;
        $this->email_to = $email_to;
        $this->email_from_domain = $email_from_domain;
        $this->email_from_name = $email_from_name;
        $this->email_max_number = $email_max_number;

        // Pestaña filter exceptions
        $check_header_referer= null;
        $check_base_64 = null;
        $base64_exceptions = null;
        $strip_tags_exceptions = null;
        $duplicate_backslashes_exceptions = null;
        $line_comments_exceptions = null;
        $sql_pattern_exceptions = null;
        $if_statement_exceptions = null;
        $using_integers_exceptions = null;
        $escape_strings_exceptions = null;
        $lfi_exceptions = null;
        $second_level_exceptions = null;
        $exclude_exceptions_if_vulnerable = 1;
        $strip_all_tags = null;
        $tags_to_filter = null;

        if (!is_null($items['check_header_referer'])) {
            $check_header_referer = $items['check_header_referer'];    
        }

        if (!is_null($items['check_base_64'])) {
            $check_base_64 = $items['check_base_64'];    
        }

        if (!is_null($items['base64_exceptions'])) {
            $base64_exceptions = $items['base64_exceptions'];    
        }

        if (!is_null($items['strip_tags_exceptions'])) {
            $strip_tags_exceptions = $items['strip_tags_exceptions'];    
        }

        if (!is_null($items['duplicate_backslashes_exceptions'])) {
            $duplicate_backslashes_exceptions = $items['duplicate_backslashes_exceptions'];    
        }

        if (!is_null($items['line_comments_exceptions'])) {
            $line_comments_exceptions = $items['line_comments_exceptions'];    
        }

        if (!is_null($items['sql_pattern_exceptions'])) {
            $sql_pattern_exceptions = $items['sql_pattern_exceptions'];    
        }

        if (!is_null($items['if_statement_exceptions'])) {
            $if_statement_exceptions = $items['if_statement_exceptions'];    
        }

        if (!is_null($items['using_integers_exceptions'])) {
            $using_integers_exceptions = $items['using_integers_exceptions'];    
        }

        if (!is_null($items['lfi_exceptions'])) {
            $lfi_exceptions = $items['lfi_exceptions'];    
        }

        if (!is_null($items['escape_strings_exceptions'])) {
            $escape_strings_exceptions = $items['escape_strings_exceptions'];    
        }

        if (!is_null($items['second_level_exceptions'])) {
            $second_level_exceptions = $items['second_level_exceptions'];    
        }

        if (!is_null($items['strip_all_tags'])) {
            $strip_all_tags = $items['strip_all_tags'];    
        }

        if (!is_null($items['tags_to_filter'])) {
            $tags_to_filter = $items['tags_to_filter'];    
        }
		
		if (!is_null($items['exclude_exceptions_if_vulnerable'])) {
            $exclude_exceptions_if_vulnerable = $items['exclude_exceptions_if_vulnerable'];    
        }
                
        $this->check_header_referer = $check_header_referer;
        $this->check_base_64 = $check_base_64;
        $this->base64_exceptions = $base64_exceptions;
        $this->strip_tags_exceptions = $strip_tags_exceptions;
        $this->duplicate_backslashes_exceptions = $duplicate_backslashes_exceptions;
        $this->line_comments_exceptions = $line_comments_exceptions;
        $this->sql_pattern_exceptions = $sql_pattern_exceptions;
        $this->if_statement_exceptions = $if_statement_exceptions;
        $this->using_integers_exceptions = $using_integers_exceptions;
        $this->lfi_exceptions = $lfi_exceptions;
        $this->escape_strings_exceptions = $escape_strings_exceptions;
        $this->second_level_exceptions = $second_level_exceptions;
        $this->exclude_exceptions_if_vulnerable = $exclude_exceptions_if_vulnerable;
        $this->strip_all_tags = $strip_all_tags;
        $this->tags_to_filter = $tags_to_filter;

        // Pestaña user session protection
        $session_protection_active= null;
        $session_hijack_protection = null;
		$session_hijack_protection_what_to_check = null;
        $track_failed_logins = null;
        $write_log = null;
        $logins_to_monitorize = null;
        $actions_failed_login = null;
        $email_on_admin_login = null;
        $forbid_admin_frontend_login = null;
        $forbid_new_admins = null;

        if (!is_null($items['session_protection_active'])) {
            $session_protection_active = $items['session_protection_active'];    
        }

        if (!is_null($items['session_hijack_protection'])) {
            $session_hijack_protection = $items['session_hijack_protection'];    
        }
		
		if (!is_null($items['session_hijack_protection_what_to_check'])) {
            $session_hijack_protection_what_to_check = $items['session_hijack_protection_what_to_check'];    
        } else 
		{
			$session_hijack_protection_what_to_check = 0;
		}

        if (!is_null($items['track_failed_logins'])) {
            $track_failed_logins = $items['track_failed_logins'];    
        }

        if (!is_null($items['write_log'])) {
            $write_log = $items['write_log'];    
        }

        if (!is_null($items['logins_to_monitorize'])) {
            $logins_to_monitorize = $items['logins_to_monitorize'];    
        }

        if (!is_null($items['actions_failed_login'])) {
            $actions_failed_login = $items['actions_failed_login'];    
        }

        if (!is_null($items['email_on_admin_login'])) {
            $email_on_admin_login = $items['email_on_admin_login'];    
        }

        if (!is_null($items['forbid_admin_frontend_login'])) {
            $forbid_admin_frontend_login = $items['forbid_admin_frontend_login'];    
        }

        if (!is_null($items['forbid_new_admins'])) {
            $forbid_new_admins = $items['forbid_new_admins'];    
        }

        $this->session_protection_active = $session_protection_active;
        $this->session_hijack_protection = $session_hijack_protection;
		$this->session_hijack_protection_what_to_check = $session_hijack_protection_what_to_check;
        $this->track_failed_logins = $track_failed_logins;
        $this->write_log = $write_log;
        $this->logins_to_monitorize = $logins_to_monitorize;
        $this->actions_failed_login = $actions_failed_login;
        $this->session_protection_groups = $items['session_protection_groups'];
        $this->email_on_admin_login = $email_on_admin_login;
        $this->forbid_admin_frontend_login = $forbid_admin_frontend_login;
        $this->forbid_new_admins = $forbid_new_admins;

        
        // Pestaña upload scanner
        $upload_scanner_enabled = 0;
        $check_multiple_extensions = 0;
        $extensions_blacklist  = "php,js,exe,xml";
		$mimetype_blacklist  = "application/x-dosexec,application/x-msdownload ,text/x-php,application/x-php,application/x-httpd-php,application/x-httpd-php-source,application/javascript,application/xml";
        $delete_files = 0;
        $actions_upload_scanner = 0;

        $upload_scanner_enabled = $items['upload_scanner_enabled'];    
        $check_multiple_extensions = $items['check_multiple_extensions'];    
        $extensions_blacklist = $items['extensions_blacklist'];
		$mimetypes_blacklist = $items['mimetypes_blacklist'];
        $delete_files = $items['delete_files'];
        $actions_upload_scanner = $items['actions_upload_scanner'];

        $this->upload_scanner_enabled = $upload_scanner_enabled;
        $this->check_multiple_extensions = $check_multiple_extensions;
		$this->extensions_blacklist = $extensions_blacklist;
        $this->mimetypes_blacklist = $mimetypes_blacklist;
        $this->delete_files = $delete_files;
        $this->actions_upload_scanner = $actions_upload_scanner;

        // Pestaña spam protection
        $check_if_user_is_spammer= null;
        $spammer_action=null;
        $spammer_write_log=null;
        $spammer_limit=3;
        $plugin_installed=false;
		$detect_arbitrary_strings = 1;

        // Chequeamos si el plugin 'Spam protection' está instalado
        $plugin_installed = $model->is_plugin_installed('system', 'securitycheck_spam_protection');

        if (!is_null($items['check_if_user_is_spammer'])) {
            $check_if_user_is_spammer = $items['check_if_user_is_spammer'];    
        }
        if (!is_null($items['spammer_action'])) {
            $spammer_action = $items['spammer_action'];    
        }
        if (!is_null($items['spammer_write_log'])) {
            $spammer_write_log = $items['spammer_write_log'];    
        }
        if (!is_null($items['spammer_limit'])) {
            $spammer_limit = $items['spammer_limit'];    
        }
        if (!is_null($items['spammer_what_to_check'])) {
            $spammer_what_to_check = $items['spammer_what_to_check'];    
        }
		 if (!is_null($items['detect_arbitrary_strings'])) {
            $detect_arbitrary_strings = $items['detect_arbitrary_strings'];    
        }

        $this->check_if_user_is_spammer = $check_if_user_is_spammer;
        $this->spammer_action = $spammer_action;
        $this->spammer_write_log = $spammer_write_log;
        $this->spammer_limit = $spammer_limit;
        $this->plugin_installed = $plugin_installed;
        $this->spammer_what_to_check = $spammer_what_to_check;
		$this->detect_arbitrary_strings = $detect_arbitrary_strings;

        // Pestaña url inspector
        // Esta el plugin habilitado?
        $url_inspector_enabled= $model->PluginStatus(7);

        // Extraemos los elementos que nos interesan...
        $inspector_forbidden_words= null;
        $write_log_inspector= null;
        $action_inspector= 2;
        $send_email_inspector = 0;
		$forms_to_include_honeypot_in = null;
		$include_urls_spam_protection = null;

        if (!is_null($items['inspector_forbidden_words'])) {
            $inspector_forbidden_words = $items['inspector_forbidden_words'];    
        }
		
		if (!is_null($items['forms_to_include_honeypot_in'])) {
            $forms_to_include_honeypot_in = $items['forms_to_include_honeypot_in'];    
        }
		
		if (!is_null($items['include_urls_spam_protection'])) {
            $include_urls_spam_protection = $items['include_urls_spam_protection'];    
        }

        if (!is_null($items['write_log_inspector'])) {
            $write_log_inspector = $items['write_log_inspector'];    
        }

        if (!is_null($items['action_inspector'])) {
            $action_inspector = $items['action_inspector'];    
        }

        if (!is_null($items['send_email_inspector'])) {
            $send_email_inspector = $items['send_email_inspector'];    
        }

        $this->inspector_forbidden_words = $inspector_forbidden_words;
		$this->forms_to_include_honeypot_in = $forms_to_include_honeypot_in;
		$this->include_urls_spam_protection = $include_urls_spam_protection;
        $this->write_log_inspector = $write_log_inspector;
        $this->action_inspector = $action_inspector;
        $this->send_email_inspector = $send_email_inspector;
        $this->url_inspector_enabled = $url_inspector_enabled;

        // Pestaña track actions
        $delete_period= 0;
        $ip_logging=null;
        $plugin_trackactions_installed=false;
        $loggable_extensions=null;

        // Chequeamos si el plugin 'Track actions' está instalado
        $plugin_trackactions_installed = $model->is_plugin_installed('system', 'trackactions');

        if (!is_null($items['delete_period'])) {
            $delete_period = $items['delete_period'];
        }
        if (!is_null($items['ip_logging'])) {
            $ip_logging = $items['ip_logging'];    
        }
        if (!is_null($items['loggable_extensions'])) {
            $loggable_extensions = $items['loggable_extensions'];    
        }

        $this->delete_period = $delete_period;
        $this->ip_logging = $ip_logging;
        $this->plugin_trackactions_installed = $plugin_trackactions_installed;
        $this->loggable_extensions = $loggable_extensions;
        
        // Añadimos también la paginación (comparamos las dos paginaciones y asignamos la mayor)
        if ((!is_null($pagination_blacklist)) && (!is_null($pagination_whitelist))) {
            if (($blacklist_elements) > ($whitelist_elements)) {
                $this->pagination = $pagination_blacklist;
            } else
            {
                $this->pagination = $pagination_whitelist;                
            }
        } else if (!is_null($pagination_blacklist)) {
            $this->pagination = $pagination_blacklist;    
        } else if (!is_null($pagination_whitelist)) {
            $this->pagination = $pagination_whitelist;    
        }
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Firewallconfig.currentip', $this->current_ip);
		$this->document->addScriptOptions('securitycheckpro.Firewallconfig.dynamicblacklistText', addslashes(Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_MESSAGE')));
			
        
        parent::display($tpl);  
    }


}