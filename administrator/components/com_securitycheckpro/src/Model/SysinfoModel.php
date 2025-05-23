<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// Chequeamos si el archivo est� inclu�do en Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\ProtectionModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;

class SysinfoModel extends BaseModel
{

    /* @var array somme system values  */
    protected $info = null;

    /**
     * method to get the system information
     *
     * @return array system information values
     */
    public function getInfo()
    {
        if (is_null($this->info)) {
            $this->info = array();
            $version = new Version;
            $db = Factory::getDBO();
			
			$memory_limit = 0;
            
			try {
				// Obtenemos el tama�o de la variable 'max_allowed_packet' de Mysql
				$db->setQuery('SHOW VARIABLES LIKE \'max_allowed_packet\'');
				$keys = $db->loadObjectList();
				$array_val = get_object_vars($keys[0]);
				$tamanno_max_allowed_packet = (int) ($array_val["Value"]/1024/1024);
			} catch (Exception $e)
			{    
				$tamanno_max_allowed_packet = 0;
			}			            
                
            // Obtenemos el tama�o m�ximo de memoria establecido
			// Based on /administrator/components/com_admin/models/sysinfo.php
            $phpinfoenabled =  !in_array('phpinfo', explode(',', ini_get('disable_functions')));
						
			if ($phpinfoenabled)
			{			
				ob_start();
				phpinfo(INFO_CONFIGURATION);
				$phpInfo = ob_get_contents();
				ob_end_clean();
							
				$subtring_start = strpos($phpInfo, 'memory_limit');
				// Get local memory_limit value (the first param in the string - 512M in our case))
				$substring_with_memory_limit = substr($phpInfo, $subtring_start, 100);
				// memory_limit</td><td class="v">512M</td><td class="v">128M</td></tr>
							
				$first_angle_pos = strpos($substring_with_memory_limit, '>');
				
				$substring_with_memory_limit = substr($substring_with_memory_limit, $first_angle_pos+1, 50);
				//<td class="v">512M</td><td class="v">128M</td></tr>
				
				$second_angle_pos = strpos($substring_with_memory_limit, '>');
				
				$substring_with_memory_limit = substr($substring_with_memory_limit, $second_angle_pos+1, 50);
				//512M</td><td class="v">128M</td></tr><tr><td class="e">open_basedir<
				
				$opening_angle_pos = strpos($substring_with_memory_limit, '<');			
				$memory_limit_local = substr($substring_with_memory_limit, 0, $opening_angle_pos);
				
				// Get master memory_limit value (the second param in the string - 128M in our case))
				$first_angle_pos = strpos($substring_with_memory_limit, '>');
				$substring_with_memory_limit = substr($substring_with_memory_limit, $first_angle_pos+1, 50);
				//<td class="v">512M</td><td class="v">128M</td></tr>
				
				$second_angle_pos = strpos($substring_with_memory_limit, '>');
				
				$substring_with_memory_limit = substr($substring_with_memory_limit, $second_angle_pos+1, 50);
				//512M</td><td class="v">128M</td></tr><tr><td class="e">open_basedir<
				
				$opening_angle_pos = strpos($substring_with_memory_limit, '<');			
				$memory_limit_master = substr($substring_with_memory_limit, 0, $opening_angle_pos);
				
				$memory_limit = Text::_('COM_SECURITYCHECKPRO_LOCAL') . $memory_limit_local . "<br/>" . Text::_('COM_SECURITYCHECKPRO_MASTER') . $memory_limit_master;				
			} else {
				$params = ComponentHelper::getParams('com_securitycheckpro');
				$memory_limit = $params->get('memory_limit', '512M');
				
				$memory_limit = Text::_('COM_SECURITYCHECKPRO_SET_BY_SCP') . $memory_limit;
			}
						
        
            // Obtenemos las opciones de configuraci�n
            $values = new JsonModel();
            $values->getStatus(false);
        
            // Obtenemos las opciones del Cpanel
            $firewall_plugin_enabled = $this->PluginStatus(1);
            $cron_plugin_enabled = $this->PluginStatus(2);
            $spam_protection_plugin_enabled = $this->PluginStatus(5);
        
            // Obtenemos los par�metros del Firewall
            $FirewallOptions = $this->getConfig();        
                
            // Obtenemos las opciones de protecci�n .htaccess
            $ConfigApplied = new ProtectionModel();
            $ConfigApplied = $ConfigApplied->GetConfigApplied();
                        
            $this->info['phpversion']    = phpversion();
            $this->info['version']        = $version->getLongVersion();
            //$this->info['platform']        = $platform->getLongVersion();
            $this->info['platform']        = "Not defined";
            $this->info['max_allowed_packet']        = $tamanno_max_allowed_packet;
            $this->info['memory_limit']        = $memory_limit;
            //Security
            $this->info['coreinstalled']        = $values->data['coreinstalled'];
            $this->info['corelatest']        = $values->data['corelatest'];
            $this->info['files_with_incorrect_permissions']        = $values->data['files_with_incorrect_permissions'];
            $this->info['files_with_bad_integrity']        = $values->data['files_with_bad_integrity'];
            $this->info['vuln_extensions']        = $values->data['vuln_extensions'];
            $this->info['suspicious_files']        = $values->data['suspicious_files'];
            $this->info['backend_protection']    = $values->data['backend_protection'];
            // Existe el fichero kickstart.php
            $this->info['kickstart_exists']        = $values->data['kickstart_exists'];
            $this->info['firewall_options']        = $FirewallOptions;
            $this->info['twofactor_enabled']    = $values->data['twofactor_enabled'];
            $this->info['overall_joomla_configuration']        = $values->data['overall'];
            //Extension status
            $this->info['cron_plugin_enabled']        = $cron_plugin_enabled;
            $this->info['firewall_plugin_enabled']        = $firewall_plugin_enabled;
            $this->info['spam_protection_plugin_enabled']        = $spam_protection_plugin_enabled;
			$this->info['unread_logs']        = $this->LogsPending();
            //$this->info['firewall_options']        = $FirewallOptions;
            $this->info['last_check']        = $values->data['last_check'];
            $this->info['last_check_integrity']        = $values->data['last_check_integrity'];        
            //Htaccess protection*/
            $this->info['htaccess_protection']        = $ConfigApplied;
            $this->info['overall_web_firewall']        = $this->getOverall($this->info, 2);   
        
        }
        return $this->info;
    }

    // Obtiene el porcentaje general de cada una de las barras de progreso
    public function getOverall($info,$opcion)
    {
        // Inicializamos variables
        $overall = 0;
    
        switch ($opcion)
        {
        // Porcentaje de progreso de  Joomla Configuration
        case 1:
            if ($info['kickstart_exists']) {
                return 2;
            }
            if (version_compare($info['coreinstalled'], $info['corelatest'], '==')) {
                $overall = $overall + 4;
            }
			if ($info['unread_logs'] <= 10) {
                $overall = $overall + 5;
            }
			
            if ($info['files_with_incorrect_permissions'] == 0) {
                $overall = $overall + 5;
            }
            if ($info['files_with_bad_integrity'] == 0) {
                $overall = $overall + 10;
            }
            if ($info['vuln_extensions'] == 0) {
                $overall = $overall + 30;
            }
            if ($info['suspicious_files'] == 0) {
                $overall = $overall + 15;
            }
            if ($info['backend_protection']) {
                $overall = $overall + 10;
            }
            if ($info['firewall_options']['forbid_new_admins'] == 1) {
                $overall = $overall + 5;
            }            
            if ($info['twofactor_enabled'] >= 1) {
                $overall = $overall + 10;
            }
            if ($info['htaccess_protection']['xframe_options'] == 1) {
                $overall = $overall + 1;
            }
            if ($info['htaccess_protection']['sts_options'] == 1) {
                $overall = $overall + 1;
            }
            if ($info['htaccess_protection']['xss_options'] == 1) {
                $overall = $overall + 1;
            }
            if ($info['htaccess_protection']['csp_policy'] == 1) {
                $overall = $overall + 1;
            }
            if ($info['htaccess_protection']['referrer_policy'] == 1) {
                $overall = $overall + 1;
            }
            if ($info['htaccess_protection']['prevent_mime_attacks'] == 1) {
                $overall = $overall + 1;
            }
            break;
        case 2:
            if ($info['firewall_plugin_enabled']) {
                $overall = $overall + 10;                
                // Configuraci�n del firewall
                if ($info['firewall_options']['dynamic_blacklist']) {
                    $overall = $overall + 10;                    
                }
                if ($info['firewall_options']['logs_attacks']) {
                    $overall = $overall + 2;                    
                }
                if ($info['firewall_options']['second_level']) {
                    $overall = $overall + 2;                    
                }
                if (!(strstr($info['firewall_options']['strip_tags_exceptions'], '*'))) {
                    $overall = $overall + 4;                    
                }
                if (!(strstr($info['firewall_options']['sql_pattern_exceptions'], '*'))) {
                    $overall = $overall + 4;                                        
                }
                if (!(strstr($info['firewall_options']['lfi_exceptions'], '*'))) {
                    $overall = $overall + 4;                                        
                }
                if ($info['firewall_options']['session_protection_active']) {
                    $overall = $overall + 2;                    
                }
                if ($info['firewall_options']['session_hijack_protection']) {
                    $overall = $overall + 2;                    
                }
                if ($info['firewall_options']['upload_scanner_enabled']) {
                    $overall = $overall + 4;                    
                }
                if ($info['spam_protection_plugin_enabled']) {
                    $overall = $overall + 2;                    
                }
                
                // Cron 
                $last_check = $this->info['last_check'];
				$now = $this->get_Joomla_timestamp();
				
				if (empty($last_check)){
					$last_check = $now;
				}
				
				$seconds = strtotime($now) - strtotime($last_check);
				// Extraemos los d�as que han pasado desde el �ltimo chequeo
				$interval = intval($seconds/86400);	
                                   
                if ($interval < 2) {
                    $overall = $overall + 10;                    
                } else
                {
                    
                }
                
                $last_check_integrity = $this->info['last_check_integrity'];
				if (empty($last_check_integrity)){
					$last_check_integrity = $now;
				}
				
				$seconds = strtotime($now) - strtotime($last_check_integrity);
				// Extraemos los d�as que han pasado desde el �ltimo chequeo
				$interval = intval($seconds/86400);
                                                                                        
                if ($interval < 2) {
                    $overall = $overall + 10;                    
                } else
                {
                    
                }
                // Htaccess protection
                if ($info['htaccess_protection']['prevent_access']) {
                    $overall = $overall + 6;                    
                }
                if ($info['htaccess_protection']['prevent_unauthorized_browsing']) {
                    $overall = $overall + 4;
                }
                if ($info['htaccess_protection']['file_injection_protection']) {
                    $overall = $overall + 4;
                }
                if ($info['htaccess_protection']['self_environ']) {
                    $overall = $overall + 4;
                }
                if ($info['htaccess_protection']['xframe_options']) {
                    $overall = $overall + 2;
                }
                if ($info['htaccess_protection']['prevent_mime_attacks']) {
                    $overall = $overall + 2;
                }
                if ($info['htaccess_protection']['default_banned_list']) {
                    $overall = $overall + 3;
                }
                if ($info['htaccess_protection']['disable_server_signature']) {
                    $overall = $overall + 3;
                }
                if ($info['htaccess_protection']['disallow_php_eggs']) {
                    $overall = $overall + 3;                    
                }
                if ($info['htaccess_protection']['disallow_sensible_files_access']) {
                    $overall = $overall + 3;                    
                }                    
            } else 
            {
                return 2;
            }
            break;        
        }
        return $overall;
    }

}
