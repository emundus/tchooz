<?php

// Chequeamos si el archivo está incluído en Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

class installerhelper {
    	
    private $defaultConfig = array(
    'blacklist'            => '',
    'whitelist'        => '',
    'dynamic_blacklist'        => 1,
    'dynamic_blacklist_time'        => 60000,
    'dynamic_blacklist_counter'        => 2,
    'blacklist_email'        => 0,
    'priority1'        => 'Whitelist',
    'priority2'        => 'DynamicBlacklist',
    'priority3'        => 'Blacklist',
    'methods'            => 'GET,POST,REQUEST',
    'mode'            => 1,
    'logs_attacks'            => 1,
    'log_limits_per_ip_and_day'            => 0,
    'redirect_after_attack'            => 1,
    'redirect_options'            => 1,
    'second_level'            => 1,
    'second_level_redirect'            => 1,
    'second_level_limit_words'            => 3,
    'second_level_words'            => 'drop,update,set,admin,select,user,password,concat,login,load_file,ascii,char,union,from,group by,order by,insert,values,pass,where,substring,benchmark,md5,sha1,schema,version,row_count,compress,encode,information_schema,script,javascript,img,src,input,body,iframe,frame,$_POST,eval,$_REQUEST,base64_decode,gzinflate,gzuncompress,gzinflate,strtrexec,passthru,shell_exec,createElement',
    'email_active'            => 0,
    'email_subject'            => 'Securitycheck Pro alert!',
    'email_body'            => 'Securitycheck Pro has generated a new alert. Please, check your logs.',
    'email_add_applied_rule'            => 1,
    'email_to'            => 'youremail@yourdomain.com',
    'email_from_domain'            => 'me@mydomain.com',
    'email_from_name'            => 'Your name',
    'email_max_number'            => 20,
    'check_header_referer'            => 1,
    'check_base_64'            => 1,
    'base64_exceptions'            => 'com_hikashop',
    'strip_tags_exceptions'            => 'com_jdownloads,com_hikashop,com_phocaguestbook',
    'duplicate_backslashes_exceptions'            => 'com_kunena,com_securitycheckprocontrolcenter',
    'line_comments_exceptions'            => 'com_comprofiler',
    'sql_pattern_exceptions'            => '',
    'if_statement_exceptions'            => '',
    'using_integers_exceptions'            => 'com_dms,com_comprofiler,com_jce,com_contactenhanced,com_securitycheckprocontrolcenter',
    'escape_strings_exceptions'            => 'com_kunena,com_jce',
    'lfi_exceptions'            => '',
    'second_level_exceptions'            => '',    
    'session_protection_active'            => 1,
    'session_hijack_protection'            => 1,
    );

    /* Función que modifica los valores del Firewall web para aplicar una configuración básica de los filtros */
    function Set_Easy_Config()
    {
    
        // Inicializamos las variables
        $query = null;
        $applied = true;
    
        $db = Factory::getDBO();
    
        // Obtenemos los valores de las distintas opciones del Firewall Web
        $query = $db->getQuery(true)
            ->select(array($db->quoteName('storage_value')))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote('pro_plugin'));
        $db->setQuery($query);
        $params = $db->loadResult();
		if( !empty($params) ) {
			$params = json_decode($params, true);		
            // Guardamos la configuración anterior
            $previous_params = $params;
        } else
        {
            // Establecemos los parámetros por defecto
            $previous_params = $this->defaultConfig;
        }
        
        // Parámetros que se desactivan o cuyo valor se deja en blanco para evitar falsos positivos
        $params['check_header_referer'] = "0";
        $params['duplicate_backslashes_exceptions'] = "*";
        $params['line_comments_exceptions'] = "*";
        $params['using_integers_exceptions'] = "*";
        $params['escape_strings_exceptions'] = "*";
		$params['session_protection_active'] = 0;
		$params['session_hijack_protection'] = 0;
		$params['session_hijack_protection_what_to_check'] = 0;
        $params['strip_all_tags'] = 0;
		
        // Codificamos de nuevo los parámetros y los introducimos en la BBDD
        $params = mb_convert_encoding(json_encode($params),'UTF-8');
        
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote('pro_plugin'));
        $db->setQuery($query);
        $db->execute();
        
        $object = (object)array(
        'storage_key'        => 'pro_plugin',
        'storage_value'        => $params
        );
        
        try 
        {
            $result = $db->insertObject('#__securitycheckpro_storage', $object);            
        } catch (Exception $e)
        {    
            $applied = false;
        }
                
        // Actualizamos el valor del campo que contendrá si se ha aplicado o no esta configuración
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote('easy_config'));
        $db->setQuery($query);
        $db->execute();
		
		$pr = mb_convert_encoding(json_encode(array('applied'=> true,'previous_config'=> $previous_params)),'UTF-8');
        
        $object = (object)array(
        'storage_key'    => 'easy_config',
        'storage_value'    => $pr
        );
            
        try
        {
            $db->insertObject('#__securitycheckpro_storage', $object);
        } catch (Exception $e)
        {        
            $applied = false;
        }
        
        return $applied;
    }

}
