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
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Application\CMSApplication;

class HtmlView extends BaseHtmlView {
    
	// Propiedades tipadas con valores por defecto claros
    public bool   $exclude_exceptions_if_vulnerable = true;
    public int   $check_header_referer = 1;
    public int   $check_base_64 = 1;
    public string $base64_exceptions = 'com_hikashop';
    public string $strip_tags_exceptions = 'com_jdownloads,com_hikashop,com_phocaguestbook';
    public string $duplicate_backslashes_exceptions = 'com_kunena,com_securitycheckprocontrolcenter';
    public string $line_comments_exceptions = 'com_comprofiler';
    public string $sql_pattern_exceptions = '';
    public string $if_statement_exceptions = '';
    public string $using_integers_exceptions = 'com_dms,com_comprofiler,com_jce,com_contactenhanced,com_securitycheckprocontrolcenter';
    public string $lfi_exceptions = '';
    public string $escape_strings_exceptions = 'com_kunena,com_jce,com_user';
    public string $second_level_exceptions = '';
    public int    $strip_all_tags = 1;
    public string $tags_to_filter = 'applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,id,iframe,ilayer,layer,link,meta,name,object,script,style,title,xml,svg,input,a';
	public string $current_ip = '';
	public string $range_example = '';
	public string $cidr_v4_example = '';
	/**
	 * @var string[]
	 */
	public ?array $whitelist_elements = [];
	/**
	 * @var string[]
	 */
	public ?array $blacklist_elements = [];
	/**
	 * @var string[]
	 */
	public ?array $dynamic_blacklist_elements = [];	
	/**
     * State data
     *
     * @var    \Joomla\Registry\Registry
     * @since  3.2
     */
    public $state;	
	public int $dynamic_blacklist = 1;
	public int    $dynamic_blacklist_time = 60000;
	public int    $dynamic_blacklist_counter = 2;
	public int    $blacklist_email = 0;
	public string    $priority1 = 'Whitelist';
	public string    $priority2 = 'DynamicBlacklist';
	public string    $priority3 = 'Blacklist';
	public int    $logs_attacks = 1;
	public int	  $scp_delete_period = 60;
	public int    $log_limits_per_ip_and_day = 0;
	public int   $add_access_attempts_logs = 0;
	public string   $methods = 'GET,POST,REQUEST';	
	public int   $mode = 1;
	public int   $email_active = 0;
	public string   $email_subject = 'Securitycheck Pro alert!';
	public string   $email_body = 'Securitycheck Pro has generated a new alert. Please, check your logs.';
	public string   $email_to = 'youremail@yourdomain.com';
	public string   $email_from_domain = 'me@mydomain.com';
	public string   $email_from_name = 'Your name';
	public int   $email_add_applied_rule = 1;
	public int   $email_max_number = 20;
	public int $redirect_after_attack = 0;
	public int   $redirect_options = 1;
	public string   $redirect_url = '';
	public string   $custom_code = 'The webmaster has forbidden your access to this site';
	public int   $second_level = 1;
	public int   $second_level_redirect = 1;
	public int   $second_level_limit_words = 3;
	public string   $second_level_words = 'ZHJvcCx1cGRhdGUsc2V0LGFkbWluLHNlbGVjdCx1c2VyLHBhc3N3b3JkLGNvbmNhdCxsb2dpbixs
	b2FkX2ZpbGUsYXNjaWksY2hhcix1bmlvbixmcm9tLGdyb3VwIGJ5LG9yZGVyIGJ5LGluc2VydCx2
	YWx1ZXMscGFzcyx3aGVyZSxzdWJzdHJpbmcsYmVuY2htYXJrLG1kNSxzaGExLHNjaGVtYSx2ZXJz
	aW9uLHJvd19jb3VudCxjb21wcmVzcyxlbmNvZGUsaW5mb3JtYXRpb25fc2NoZW1hLHNjcmlwdCxq
	YXZhc2NyaXB0LGltZyxzcmMsaW5wdXQsYm9keSxpZnJhbWUsZnJhbWUsJF9QT1NULGV2YWwsJF9S
	RVFVRVNULGJhc2U2NF9kZWNvZGUsZ3ppbmZsYXRlLGd6dW5jb21wcmVzcyxnemluZmxhdGUsc3Ry
	dHJleGVjLHBhc3N0aHJ1LHNoZWxsX2V4ZWMsY3JlYXRlRWxlbWVudA==';
	public int   $session_protection_active = 1;
	public int   $session_hijack_protection = 1;
	public int   $track_failed_logins = 0;
	public int   $logins_to_monitorize = 2;
	public int   $write_log = 1;
	public int   $actions_failed_login = 1;
	public int   $email_on_admin_login = 0;
	public int   $forbid_admin_frontend_login = 0;
	public int   $forbid_new_admins = 0;
	public int   $session_hijack_protection_what_to_check = 0;
	/**
	 * @var string[]
	 */
	public array   $session_protection_groups = ['0' => '8'];
	public int   $detect_arbitrary_strings = 0;
	public int  $check_if_user_is_spammer = 1;
	public int   $spammer_action = 1;
	public int   $spammer_write_log = 1;
	/**
	 * @var string[]
	 */
	public array   $spammer_what_to_check = ['Email','IP','Username'];
	public int   $spammer_limit = 3;
	public string   $include_urls_spam_protection = '';
	public string   $forms_to_include_honeypot_in = '';
	public bool   $plugin_installed = false;
	public int   $delete_period = 0;
	public int   $ip_logging = 0;
	/**
	 * @var string[]
	 */
	public array   $loggable_extensions = ['0' => 'com_banners','1' => 'com_cache','2' => 'com_categories','3' => 'com_config','4' => 'com_contact','5' => 'com_content','6' => 'com_installer','7' => 'com_media','8' => 'com_menus','9' => 'com_messages','10' => 'com_modules','11' => 'com_newsfeeds','12' => 'com_plugins','13' => 'com_redirect','14' => 'com_tags','15' => 'com_templates','16' => 'com_users'];
	public bool   $plugin_trackactions_installed = false;
	public int   $upload_scanner_enabled = 1;
	public int   $check_multiple_extensions = 1;
	public string   $mimetypes_blacklist = 'application/x-dosexec,application/x-msdownload ,text/x-php,application/x-php,application/x-httpd-php,application/x-httpd-php-source,application/javascript,application/xml';
	public string   $extensions_blacklist = 'php,js,exe,xml';
	public int   $delete_files = 1;
	public int   $actions_upload_scanner = 0;
	public ?int   $url_inspector_enabled = 0;
	public int   $write_log_inspector = 1;
	public int   $send_email_inspector = 1;
	public int   $action_inspector = 2;	
	public string   $inspector_forbidden_words = 'wp-login.php,.git,owl.prev,tmp.php,home.php,Guestbook.php,aska.cgi,default.asp,jax_guestbook.php,bbs.cg,gastenboek.php,light.cgi,yybbs.cgi,wsdl.php,wp-content,cache_aqbmkwwx.php,.suspected,seo-joy.cgi,google-assist.php,wp-main.php,sql_dump.php,xmlsrpc.php';
	
	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public ?Pagination $pagination_blacklist = null;
	
	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public ?Pagination $pagination_dynamic_blacklist = null;
	
	/**
	 * Pagination object
	 *
	 * @var Pagination
	 */
	public ?Pagination $pagination_whitelist = null;
	
	/**
	 * @var BaseModel
	 */
	public $basemodel;
	
	/**
	 * @var FirewallconfigModel
	 */
	public $firewallconfigmodel;
	
	/**
	 * @var string
	 */
	public $activeParent;
	
	/**
	 * @var string
	 */
	public $activeChild;
	
	/**
	 * @var string
	 */
	public $activeExceptions;
	
    /**
     * Display the main view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     * @return  void
     */
    function display($tpl = null) {
		  
		ToolbarHelper::title(Text::_('Securitycheck Pro').' | ' .Text::_('COM_SECURITYCHECKPRO_WAF_CONFIG'), 'securitycheckpro');
		ToolbarHelper::apply('firewallconfig.apply');
        ToolbarHelper::save('firewallconfig.save');
		
		// Load css and js
		$this->document->getWebAssetManager()
		  ->usePreset('com_securitycheckpro.common')		 
		  ->useScript('com_securitycheckpro.Firewallconfig');
		
		// Obtenemos el modelo de esta vista (FirewallconfigModel)
		/** @var FirewallconfigModel $model */
        $model = $this->getModel();
		
		// BaseModel
        $this->basemodel = new BaseModel();
		
		$this->firewallconfigmodel = $model;
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		
		// Pane por defecto (ajusta si quieres otro por defecto)
		$defaultParent = 'li_lists_tab';
		$defaultChild  = 'li_blacklist_tab';	
		$defaultExceptions  = 'li_header_referer_tab';	
		
		// Lee del request (si viene del form) o de la sesión (si no)
		$this->activeParent = $app->getUserStateFromRequest(
			'com_securitycheckpro.WafConfigurationTabs.active',
			'activeTab_WafConfigurationTabs',
			$defaultParent
		);
		
		$this->activeChild = $app->getUserStateFromRequest(
			'com_securitycheckpro.ListsTabs.active',
			'activeTab_ListsTabs',
			$defaultChild
		);
		
		$this->activeExceptions = $app->getUserStateFromRequest(
			'com_securitycheckpro.ExceptionsTabs.active',
			'activeTab_ExceptionsTabs',
			$defaultExceptions
		);			
		
		// Filtro
        $this->state= $model->getState();
        
        //  Parámetros del plugin
        $items= $model->getConfig();
		
		// Lista negra
		[$this->blacklist_elements, $this->pagination_blacklist]
			= $model->getListWithPagination('blacklist', 'blacklist');

		// Lista negra dinámica
		[$this->dynamic_blacklist_elements, $this->pagination_dynamic_blacklist]
			= $model->getListWithPagination('dynamic_blacklist', 'dynamic_blacklist');

		// Lista blanca
		[$this->whitelist_elements, $this->pagination_whitelist]
			= $model->getListWithPagination('whitelist', 'whitelist');
						
				
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
			$result_ip_address = explode(', ', $current_ip);
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

      	$this->dynamic_blacklist = (int) ($items['dynamic_blacklist'] ?? $this->dynamic_blacklist);
		$this->dynamic_blacklist_time = (int) ($items['dynamic_blacklist_time'] ?? $this->dynamic_blacklist_time);
		$this->dynamic_blacklist_counter = (int) ($items['dynamic_blacklist_counter'] ?? $this->dynamic_blacklist_counter);
		$this->blacklist_email = (int) ($items['blacklist_email'] ?? $this->blacklist_email);
		$this->priority1 = (string) ($items['priority1'] ?? $this->priority1);
		$this->priority2 = (string) ($items['priority2'] ?? $this->priority2);
		$this->priority3 = (string) ($items['priority3'] ?? $this->priority3);		
		$this->current_ip = $current_ip;
		$this->range_example = $range_example;
		$this->cidr_v4_example = $cidr_v4_example;
        
        // Pestaña methods
		$this->methods = (string) ($items['methods'] ?? $this->methods);
        
        // Pestaña Mode       
		$this->mode = (int) ($items['mode'] ?? $this->mode);

        // Pestaña Logs
       	$this->scp_delete_period = (int) ($items['scp_delete_period'] ?? $this->scp_delete_period);
		$this->logs_attacks = (int) ($items['logs_attacks'] ?? $this->logs_attacks);
        $this->log_limits_per_ip_and_day = (int) ($items['log_limits_per_ip_and_day'] ?? $this->log_limits_per_ip_and_day);
		$this->add_access_attempts_logs = (int) ($items['add_access_attempts_logs'] ?? $this->add_access_attempts_logs);
		
		 // Pestaña Redirection
		$this->redirect_after_attack = (int) ($items['redirect_after_attack'] ?? $this->redirect_after_attack);
        $this->redirect_options = (int) ($items['redirect_options'] ?? $this->redirect_options);
		$this->redirect_url = (string) ($items['redirect_url'] ?? $this->redirect_url);
		$this->custom_code = (string) ($items['custom_code'] ?? $this->custom_code);

        // Pestaña Second level
		$this->second_level = (int) ($items['second_level'] ?? $this->second_level);
		$this->second_level_redirect = (int) ($items['second_level_redirect'] ?? $this->second_level_redirect);
        $this->second_level_limit_words = (int) ($items['second_level_limit_words'] ?? $this->second_level_limit_words);
		$this->second_level_words = (string) ($items['second_level_words'] ?? $this->second_level_words);
       
        // Pestaña Email notifications
		$this->email_active = (int) ($items['email_active'] ?? $this->email_active);
		$this->email_subject = (string) ($items['email_subject'] ?? $this->email_subject);
		$this->email_body = (string) ($items['email_body'] ?? $this->email_body);
        $this->email_add_applied_rule = (string) ($items['email_add_applied_rule'] ?? $this->email_add_applied_rule);
		$this->email_to = (string) ($items['email_to'] ?? $this->email_to);
        $this->email_from_domain = (string) ($items['email_from_domain'] ?? $this->email_from_domain);
		$this->email_from_name = (string) ($items['email_from_name'] ?? $this->email_from_name);
        $this->email_max_number = (int) ($items['email_max_number'] ?? $this->email_max_number);
				
        // Pestaña filter exceptions
        $this->exclude_exceptions_if_vulnerable = (bool) ($items['exclude_exceptions_if_vulnerable'] ?? false);		
        $this->check_header_referer = (int) ($items['check_header_referer'] ?? $this->check_header_referer);
		$this->check_base_64 = (int) ($items['check_base_64'] ?? $this->check_base_64);
        $this->base64_exceptions = (string) ($items['base64_exceptions'] ?? $this->base64_exceptions);
		$this->strip_tags_exceptions = (string) ($items['strip_tags_exceptions'] ?? $this->strip_tags_exceptions);
        $this->duplicate_backslashes_exceptions = (string) ($items['duplicate_backslashes_exceptions'] ?? $this->duplicate_backslashes_exceptions);
	    $this->line_comments_exceptions = (string) ($items['line_comments_exceptions'] ?? $this->line_comments_exceptions);
        $this->sql_pattern_exceptions = (string) ($items['sql_pattern_exceptions'] ?? $this->sql_pattern_exceptions);
		$this->if_statement_exceptions = (string) ($items['if_statement_exceptions'] ?? $this->if_statement_exceptions);
        $this->using_integers_exceptions = (string) ($items['using_integers_exceptions'] ?? $this->using_integers_exceptions);
        $this->lfi_exceptions = (string) ($items['lfi_exceptions'] ?? $this->lfi_exceptions);
		$this->escape_strings_exceptions = (string) ($items['escape_strings_exceptions'] ?? $this->escape_strings_exceptions);
		$this->second_level_exceptions = (string) ($items['second_level_exceptions'] ?? $this->second_level_exceptions);
		$this->strip_all_tags = (int) ($items['strip_all_tags'] ?? $this->strip_all_tags);
		$this->tags_to_filter = (string) ($items['tags_to_filter'] ?? $this->tags_to_filter);
		

        // Pestaña user session protection
        $this->session_protection_active = (int) ($items['session_protection_active'] ?? $this->session_protection_active);
		$this->session_hijack_protection = (int) ($items['session_hijack_protection'] ?? $this->session_hijack_protection);
        $this->session_hijack_protection_what_to_check = (int) ($items['session_hijack_protection_what_to_check'] ?? $this->session_hijack_protection_what_to_check);
		$this->track_failed_logins = (int) ($items['track_failed_logins'] ?? $this->track_failed_logins);
        $this->write_log = (int) ($items['write_log'] ?? $this->write_log);
		$this->logins_to_monitorize = (int) ($items['logins_to_monitorize'] ?? $this->logins_to_monitorize);
		$this->actions_failed_login = (int) ($items['actions_failed_login'] ?? $this->actions_failed_login);
		$this->email_on_admin_login = (int) ($items['email_on_admin_login'] ?? $this->email_on_admin_login);
        $this->forbid_admin_frontend_login = (int) ($items['forbid_admin_frontend_login'] ?? $this->forbid_admin_frontend_login);
		$this->forbid_new_admins = (int) ($items['forbid_new_admins'] ?? $this->forbid_new_admins);
                
        // Pestaña upload scanner
       	$this->upload_scanner_enabled = (int) ($items['upload_scanner_enabled'] ?? $this->upload_scanner_enabled);
		$this->check_multiple_extensions = (int) ($items['check_multiple_extensions'] ?? $this->check_multiple_extensions);
		$this->extensions_blacklist = (string) ($items['extensions_blacklist'] ?? $this->extensions_blacklist);
		$this->mimetypes_blacklist = (string) ($items['mimetypes_blacklist'] ?? $this->mimetypes_blacklist);
	    $this->delete_files = (int) ($items['delete_files'] ?? $this->delete_files);
	    $this->actions_upload_scanner = (int) ($items['actions_upload_scanner'] ?? $this->actions_upload_scanner);
		
        // Pestaña spam protection
        // Chequeamos si el plugin 'Spam protection' está instalado
        $this->plugin_installed = $model->is_plugin_installed('system', 'securitycheck_spam_protection');		
		$this->check_if_user_is_spammer = (int) ($items['check_if_user_is_spammer'] ?? $this->check_if_user_is_spammer);
		$this->spammer_action = (int) ($items['spammer_action'] ?? $this->spammer_action);
		$this->spammer_write_log = (int) ($items['spammer_write_log'] ?? $this->spammer_write_log);
        $this->spammer_limit = (int) ($items['spammer_limit'] ?? $this->spammer_limit);
        $this->spammer_what_to_check = (array) ($items['spammer_what_to_check'] ?? $this->spammer_what_to_check);
		$this->forms_to_include_honeypot_in = (string) ($items['forms_to_include_honeypot_in'] ?? $this->forms_to_include_honeypot_in);
        $this->include_urls_spam_protection = (string) ($items['include_urls_spam_protection'] ?? $this->include_urls_spam_protection);
		$this->detect_arbitrary_strings = (int) ($items['detect_arbitrary_strings'] ?? $this->detect_arbitrary_strings);
		$this->session_protection_groups = (array) ($items['session_protection_groups'] ?? $this->session_protection_groups);
        
        // Pestaña url inspector
        // Esta el plugin habilitado?
        $this->url_inspector_enabled= $model->PluginStatus(7);
		$this->inspector_forbidden_words = (string) ($items['inspector_forbidden_words'] ?? $this->inspector_forbidden_words);		
		$this->write_log_inspector = (int) ($items['write_log_inspector'] ?? $this->write_log_inspector);
		$this->action_inspector = (int) ($items['action_inspector'] ?? $this->action_inspector);
		$this->send_email_inspector = (int) ($items['send_email_inspector'] ?? $this->send_email_inspector);

        // Pestaña track actions
        // Chequeamos si el plugin 'Track actions' está instalado
        $this->plugin_trackactions_installed = $model->is_plugin_installed('system', 'trackactions');
		$this->delete_period = (int) ($items['delete_period'] ?? $this->delete_period);
		$this->ip_logging = (int) ($items['ip_logging'] ?? $this->ip_logging);
		$this->loggable_extensions = (array) ($items['loggable_extensions'] ?? $this->loggable_extensions);
              
		
		// Also comes common data from SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\DisplayController
		
		// Pass parameters to the cpanel.js script using Joomla's script options API
		$this->document->addScriptOptions('securitycheckpro.Firewallconfig.currentip', $this->current_ip);
		Text::script('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_MESSAGE');
			
        
        parent::display($tpl);  
    }


}