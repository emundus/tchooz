<?php

// Chequeamos si el archivo está incluído en Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

class installerhelper {
    
	/**
	 * Configuración por defecto del firewall.
	 *
	 * @var array{
	 *   blacklist: string,
	 *   whitelist: string,
	 *   dynamic_blacklist: int,
	 *   dynamic_blacklist_time: int,
	 *   dynamic_blacklist_counter: int,
	 *   blacklist_email: int,
	 *   priority1: string,
	 *   priority2: string,
	 *   priority3: string,
	 *   methods: string,
	 *   mode: int,
	 *   logs_attacks: int,
	 *   log_limits_per_ip_and_day: int,
	 *   redirect_after_attack: int,
	 *   redirect_options: int,
	 *   second_level: int,
	 *   second_level_redirect: int,
	 *   second_level_limit_words: int,
	 *   second_level_words: string,
	 *   email_active: int,
	 *   email_subject: string,
	 *   email_body: string,
	 *   email_add_applied_rule: int,
	 *   email_to: string,
	 *   email_from_domain: string,
	 *   email_from_name: string,
	 *   email_max_number: int,
	 *   check_header_referer: int,
	 *   check_base_64: int,
	 *   base64_exceptions: string,
	 *   strip_tags_exceptions: string,
	 *   duplicate_backslashes_exceptions: string,
	 *   line_comments_exceptions: string,
	 *   sql_pattern_exceptions: string,
	 *   if_statement_exceptions: string,
	 *   using_integers_exceptions: string,
	 *   escape_strings_exceptions: string,
	 *   lfi_exceptions: string,
	 *   second_level_exceptions: string,
	 *   session_protection_active: int,
	 *   session_hijack_protection: int
	 * }
	 */
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
    'redirect_after_attack'            => 0,
    'redirect_options'            => 1,
    'second_level'            => 1,
    'second_level_redirect'            => 1,
    'second_level_limit_words'            => 3,
    'second_level_words'            => 'ZHJvcCx1cGRhdGUsc2V0LGFkbWluLHNlbGVjdCx1c2VyLHBhc3N3b3JkLGNvbmNhdCxsb2dpbixsb2FkX2ZpbGUsYXNjaWksY2hhcix1bmlvbixmcm9tLGdyb3VwIGJ5LG9yZGVyIGJ5LGluc2VydCx2YWx1ZXMscGFzcyx3aGVyZSxzdWJzdHJpbmcsYmVuY2htYXJrLG1kNSxzaGExLHNjaGVtYSx2ZXJzaW9uLHJvd19jb3VudCxjb21wcmVzcyxlbmNvZGUsaW5mb3JtYXRpb25fc2NoZW1hLHNjcmlwdCxqYXZhc2NyaXB0LGltZyxzcmMsaW5wdXQsYm9keSxpZnJhbWUsZnJhbWUsJF9QT1NULGV2YWwsJF9SRVFVRVNULGJhc2U2NF9kZWNvZGUsZ3ppbmZsYXRlLGd6dW5jb21wcmVzcyxnemluZmxhdGUsc3RydHJleGVjLHBhc3N0aHJ1LHNoZWxsX2V4ZWMsY3JlYXRlRWxlbWVudA==',
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
    'escape_strings_exceptions'            => 'com_kunena,com_jce,com_user',
    'lfi_exceptions'            => '',
    'second_level_exceptions'            => '',    
    'session_protection_active'            => 1,
    'session_hijack_protection'            => 1,
    );

    /* Función que modifica los valores del Firewall web para aplicar una configuración básica de los filtros */
    function Set_Easy_Config(): bool
    {
    
        // Inicializamos las variables
        $query = null;
        $applied = true;
		/** @var array<string,mixed> $params */
		$params = $this->defaultConfig;

		/** @var array<string,mixed> $previous_params */
		$previous_params = $this->defaultConfig;

		/** @var \Joomla\Database\DatabaseInterface&\Joomla\Database\DatabaseDriver $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Cargar JSON
		/** @var QueryInterface $query */
		$query = $db->getQuery(true);

		$query->select($db->quoteName('storage_value'));
		$query->from($db->quoteName('#__securitycheckpro_storage'));
		$query->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));

		$db->setQuery($query);
		
		/** @var string|null $json */
		$json = $db->loadResult();

		/** @var mixed $decoded */
		$decoded = (is_string($json) && $json !== '') ? json_decode($json, true) : null;

		if (is_array($decoded)) {
			/** @var array<string,mixed> $decoded */
			$previous_params = $decoded;
			$params = array_replace($this->defaultConfig, $decoded);
		} else {
			// previous_params ya está en defaultConfig
			$params = $this->defaultConfig;
		}
			        
        // Parámetros que se desactivan o cuyo valor se deja en blanco para evitar falsos positivos
        $params['check_header_referer'] = 0;
        $params['duplicate_backslashes_exceptions'] = "*";
        $params['line_comments_exceptions'] = "*";
        $params['using_integers_exceptions'] = "*";
        $params['escape_strings_exceptions'] = "*";
		$params['session_protection_active'] = 0;
		$params['session_hijack_protection'] = 0;
		$params['session_hijack_protection_what_to_check'] = 0;
        $params['strip_all_tags'] = 0;
		
        // Codificamos de nuevo los parámetros y los introducimos en la BBDD
        $json = json_encode($params, JSON_UNESCAPED_UNICODE);

		if ($json === false) {
			$json = '[]'; // fallback seguro
		}

		$params = mb_convert_encoding($json, 'UTF-8');
		
		/** @var QueryInterface $query */
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__securitycheckpro_storage'));
		$query->where($db->quoteName('storage_key') . ' = ' . $db->quote('pro_plugin'));
		$db->setQuery($query);
		$db->execute();

		$object = (object) [
			'storage_key'   => 'pro_plugin',			
			'storage_value' => $params, 
		];
        
        try 
        {
            $result = $db->insertObject('#__securitycheckpro_storage', $object);            
        } catch (\Throwable $e) {
            $applied = false;
        }
                
        // Actualizamos el valor del campo que contendrá si se ha aplicado o no esta configuración
		/** @var QueryInterface $query */
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__securitycheckpro_storage'));
		$query->where($db->quoteName('storage_key') . ' = ' . $db->quote('easy_config'));
		$db->setQuery($query);
		$db->execute();
		
		$prJson = json_encode(
			['applied' => true, 'previous_config' => $previous_params],
			JSON_UNESCAPED_UNICODE
		);

		if ($prJson === false) {
			$prJson = '[]';
		}

		$pr = mb_convert_encoding($prJson, 'UTF-8');
        
		$object = (object) [
			'storage_key'   => 'easy_config',
			'storage_value' => $pr,
		];
            
        try
        {
            $db->insertObject('#__securitycheckpro_storage', $object);
        } catch (\Throwable $e) {        
            $applied = false;
        }
        
        return $applied;
    }

}
