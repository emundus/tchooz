<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\Input\Input;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\CpanelModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Controller\SecuritycheckproBaseController;

/**
 * Securitycheckpros  Controller
 */
class FirewallconfigController extends SecuritycheckproBaseController
{

    /* Borra IPs de la lista negra */
    function deleteip_blacklist():void {
		$model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}
        $model->manage_list('blacklist', 'delete');
		            
        parent::display();    
    }

    /* Añade un IP a la lista negra */
    function addip_blacklist():void {
        $model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}	
		$model->manage_list('blacklist', 'add');
            
        parent::display();    
    }

    /* Borra IPs de la lista blanca */
    function deleteip_whitelist():void {
        $model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}
        $model->manage_list('whitelist', 'delete');
            
        parent::display();    
    }

    /* Añade un IP a la lista blanca */
    function addip_whitelist():void {
        $model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}
		$model->manage_list('whitelist', 'add');
            
        parent::display();    
    }

    /* Borra IPs de la lista negra dinámica */
    function deleteip_dynamic_blacklist():void {
        $model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}
        $model->deleteip_dynamic_blacklist();
            
        parent::display();    
    }

    /* Guarda los cambios y redirige al cPanel */
    public function save():void {
		
        $model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app   = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $app->getInput();       
		
		$parent = $jinput->get('activeTab_WafConfigurationTabs', '');
        $child  = $jinput->get('activeTab_ListsTabs', '');
		$exceptions  = $jinput->get('activeTab_ExceptionsTabs', '');
		
        if ($parent !== '') {
            $app->setUserState('com_securitycheckpro.WafConfigurationTabs.active', $parent);
        }
        if ($child !== '') {
            $app->setUserState('com_securitycheckpro.ListsTabs.active', $child);
        }
		
		if ($exceptions !== '') {
            $app->setUserState('com_securitycheckpro.ExceptionsTabs.active', $exceptions);
        }
		    
        //El campo 'custom_code' tendrá un formato raw
        $custom_code = $jinput->get("custom_code", null, 'raw');
    
        $data = $jinput->post->getArray();
        
        $data['base64_exceptions'] = $model->clearstring($data['base64_exceptions'], 2);
        $data['strip_tags_exceptions'] = $model->clearstring($data['strip_tags_exceptions'], 2);
        $data['duplicate_backslashes_exceptions'] = $model->clearstring($data['duplicate_backslashes_exceptions'], 2);
        $data['line_comments_exceptions'] = $model->clearstring($data['line_comments_exceptions'], 2);
        $data['sql_pattern_exceptions'] = $model->clearstring($data['sql_pattern_exceptions'], 2);
        $data['if_statement_exceptions'] = $model->clearstring($data['if_statement_exceptions'], 2);
        $data['using_integers_exceptions'] = $model->clearstring($data['using_integers_exceptions'], 2);
        $data['escape_strings_exceptions'] = $model->clearstring($data['escape_strings_exceptions'], 2);    
        $data['lfi_exceptions'] = $model->clearstring($data['lfi_exceptions'], 2);
        $data['second_level_exceptions'] = $model->clearstring($data['second_level_exceptions'], 2);
        $data['custom_code'] = $custom_code;
        
        // Look for super users groups
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = "SELECT id from #__usergroups where title='Super Users'" ;            
        $db->setQuery($query);
        $super_user_group = $db->loadResult();
        
        // Establecemos el grupo "Super users" por defecto para aplicar la protección de sesión
        if ((!array_key_exists("session_protection_groups", $data)) || (is_null($data['session_protection_groups']))) {
            $data['session_protection_groups'] = array('0' => $super_user_group);
        }       
    
        /* Variable que indicará si los emails introducidos en el campo 'email to' son válidos */
        $emails_valid = true;
    
        /* Obtenemos un array con todos los emails introducidos (separados con comas) */
        $emails_array = explode(",", $data['email_to']);
    
        /* Chequeamos si los emails introducidos son válidos */
        foreach($emails_array as $email)
        {
            $valid = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
            if (!$valid) {
                $emails_valid = false;
                break;
            }
        }
		    
        $data['inspector_forbidden_words'] = $model->clearstring($data['inspector_forbidden_words'], 1);
		
		if ( array_key_exists("forms_to_include_honeypot_in",$data) ) {
			$data['forms_to_include_honeypot_in'] = $model->clearstring($data['forms_to_include_honeypot_in'], 1);
		} else {
			$data['forms_to_include_honeypot_in'] = "";
		}
		
		if ( array_key_exists('include_urls_spam_protection',$data) ) {
			$data['include_urls_spam_protection'] = $model->clearstring($data['include_urls_spam_protection'], 1);
		} else {
			$data['include_urls_spam_protection'] = "";
		}		
    
        if (!array_key_exists('loggable_extensions', $data)) {
            $data['loggable_extensions'] = explode(',', "com_banners,com_cache,com_categories,com_config,com_contact,com_content,com_installer,com_media,com_menus,com_messages,com_modules,com_newsfeeds,com_plugins,com_redirect,com_tags,com_templates,com_users");
        }
    
        if ((!$emails_valid) || (!filter_var($data['email_from_domain'], FILTER_VALIDATE_EMAIL)) || (!is_numeric($data['email_max_number']))) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_EMAIL_FORMAT'), 'error');
        } else
        {
            if ((array_key_exists('spammer_limit', $data)) && (!is_numeric($data['spammer_limit'])) || (array_key_exists('delete_period', $data) && !is_numeric($data['delete_period']))) {
                Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_VALUE'), 'error');
            } else
            {
                $model->saveConfig($data, 'pro_plugin');
            }         
        }
            
        $this->setRedirect('index.php?option=com_securitycheckpro');    
    }

    /* Guarda los cambios */
    public function apply():void {
        $this->save();
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');
    }    

    /* Importa un fichero de ips a la lista pasada como argumento */
    public function import_list():void {
        $model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}
        $model->import_list();
            
        parent::display();    
    }
	
	/* Acciones al pulsar el botón para exportar las Ips en las listas */
    function export_list(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app    = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $app->getInput();

		// Nombre de lista recibido (por ejemplo: "whitelist", "blacklist", etc.)
		$lista = $jinput->getString('export', '');

		// Validación básica: solo letras, números y guiones bajos.
		if ($lista === '' || !preg_match('/^[a-z0-9_]+$/i', $lista)) {
			$app->enqueueMessage('List to export is empty or invalid', 'error');
			parent::display();
			return;
		}

		// Construimos nombre de tabla con prefijo
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$tableName = $db->replacePrefix('#__securitycheckpro_' . $lista);

		try {
			// Selecciona únicamente la columna que contiene las IPs.
			// Ajusta el nombre de columna si en tu tabla no se llama 'ip'.
			$query = $db->getQuery(true)
				->select($db->quoteName('ip'))
				->from($db->quoteName($tableName));

			$db->setQuery($query);
			$array_ips = $db->loadColumn();  // array de strings (IPs)
		} catch (\Throwable $e) {
			$app->enqueueMessage($e->getMessage(), 'error');
			parent::display();
			return;
		}

		if (empty($array_ips)) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_DATA_TO_EXPORT'), 'error');
			parent::display();
			return;
		}

		// Construimos el contenido: ip,ip,ip
		// Filtramos valores vacíos y duplicados por si acaso.
		$array_ips = array_values(array_unique(array_filter(array_map('trim', $array_ips))));
		$list      = implode(',', $array_ips);

		// Nombre de archivo
		$config   = $app->getConfig();
		$sitename = (string) $config->get('sitename', 'site');
		$sitename = preg_replace('/\s+/', '', $sitename); // sin espacios
		$timestamp = date('Ymd_His');                     // formato estable
		$filename  = "securitycheckpro_{$lista}_{$sitename}_{$timestamp}.txt";

		// Aseguramos que no hay salida previa y limpiamos todos los buffers
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}

		// Enviamos cabeceras y contenido (sin usar métodos obsoletos)
		header('Content-Type: text/plain; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');

		// Opcional: Content-Length (mejor experiencia de descarga)
		$length = strlen($list);
		if ($length > 0) {
			header('Content-Length: ' . (string) $length);
		}

		echo $list;
		// Terminamos la petición aquí para evitar que Joomla agregue HTML extra
		$app->close();
	}  
    
    /* Envía un correo de prueba */
    public function send_email_test():void {
        $model = $this->getModel('Firewallconfig');
		if (!$model instanceof FirewallconfigModel) {
			Factory::getApplication()->enqueueMessage('Firewallconfig model not found', 'error');
			return;
		}
        $model->send_email_test();
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');
    }

    /* Acciones al pulsar el botón 'Enable' en la pestaña url inspector*/
    function enable_url_inspector():void {
        $cpanelmodel = new CpanelModel();
        $cpanelmodel->toggle_plugin('url_inspector', true);
    
        $this->setRedirect('index.php?option=com_securitycheckpro&controller=firewallconfig&view=firewallconfig&'. Session::getFormToken() .'=1');
        
    }

}
