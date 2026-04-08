<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// No Permission
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;
use Joomla\Input\Input;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\IpHelper;
use Joomla\CMS\Date\Date;

if (!defined('SCP_CACERT_PEM')) define('SCP_CACERT_PEM', __DIR__ . '/cacert.pem');
if (!defined('SCP_USER_AGENT')) define('SCP_USER_AGENT', 'Securitycheck Pro User agent');

class BaseModel extends BaseDatabaseModel
{

    /**
     Array de datos
     *
     @var string[]
     */
    var $_data=[];
    /**
     Total items
     *
     @var integer
     */
    var $_total = null;
    /**
     Objeto Pagination
     *
     @var object
     */
    var $_pagination = null;
    /**
     Columnas de #__securitycheck
     *
     @var integer
     */
    var $_dbrows = null;
	
	/**
     Configuración aplicada
     *
     @var \Joomla\Registry\Registry
     */
    private ?Registry $config = null;
	
	/**
     Configuración por defecto
     *
     @var array<string, int|list<string>|string>
     */
    private $defaultConfig = [
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
		'scp_delete_period'            => 60,    
		'log_limits_per_ip_and_day'            => 0,
		'redirect_after_attack'            => 0,
		'redirect_options'            => 1,
		'redirect_url'            => '',
		'custom_code'            => 'The webmaster has forbidden your access to this site',
		'second_level'            => 1,
		'second_level_redirect'            => 1,
		'second_level_limit_words'            => 3,
		'second_level_words'            => 'ZHJvcCx1cGRhdGUsc2V0LGFkbWluLHNlbGVjdCx1c2VyLHBhc3N3b3JkLGNvbmNhdCxsb2dpbixs
	b2FkX2ZpbGUsYXNjaWksY2hhcix1bmlvbixmcm9tLGdyb3VwIGJ5LG9yZGVyIGJ5LGluc2VydCx2
	YWx1ZXMscGFzcyx3aGVyZSxzdWJzdHJpbmcsYmVuY2htYXJrLG1kNSxzaGExLHNjaGVtYSx2ZXJz
	aW9uLHJvd19jb3VudCxjb21wcmVzcyxlbmNvZGUsaW5mb3JtYXRpb25fc2NoZW1hLHNjcmlwdCxq
	YXZhc2NyaXB0LGltZyxzcmMsaW5wdXQsYm9keSxpZnJhbWUsZnJhbWUsJF9QT1NULGV2YWwsJF9S
	RVFVRVNULGJhc2U2NF9kZWNvZGUsZ3ppbmZsYXRlLGd6dW5jb21wcmVzcyxnemluZmxhdGUsc3Ry
	dHJleGVjLHBhc3N0aHJ1LHNoZWxsX2V4ZWMsY3JlYXRlRWxlbWVudA==',
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
		'session_hijack_protection_what_to_check'            => 0,
		'tasks'            => 'integrity',
		'launch_time'            => 2,
		'periodicity'            => 24,
		'control_center_enabled'    => '0',
		'secret_key'    => '',
		'control_center_url'    => '',
		'token'    => '',
		'add_geoblock_logs'            => 0,
		'upload_scanner_enabled'    =>    1,
		'detect_arbitrary_strings'	=>    0,
		'check_multiple_extensions'    =>    1,
		'extensions_blacklist'            => 'php,js,exe,xml',
		'mimetypes_blacklist'	=>	'application/x-dosexec,application/x-msdownload ,text/x-php,application/x-php,application/x-httpd-php,application/x-httpd-php-source,application/javascript,application/xml',
		'delete_files'            => 1,
		'actions_upload_scanner'    =>    0,
		'exclude_exceptions_if_vulnerable'    =>    1,
		'track_failed_logins'    =>    1,
		'write_log'    =>    1,
		'logins_to_monitorize'    =>    2,    
		'actions_failed_login'    =>    1,
		'session_protection_groups'    => ['0' => '8'],
		'backend_exceptions'    =>    '',
		'email_on_admin_login'    =>    0,
		'forbid_admin_frontend_login'    =>    0,
		'add_access_attempts_logs'    =>    0,
		'check_if_user_is_spammer'    =>    1,
		'spammer_action'    =>    1,
		'spammer_write_log'    =>    1,
		'spammer_limit'    =>    3,
		'forbid_new_admins'    => 0,
		'spammer_what_to_check'    => ['Email','IP','Username'],
		'strip_all_tags'    =>    1,
		'tags_to_filter'            => 'applet,body,bgsound,base,basefont,embed,frame,frameset,head,html,id,iframe,ilayer,layer,link,meta,name,object,script,style,title,xml,svg,input,a',
		'inspector_forbidden_words'    => 'wp-login.php,.git,owl.prev,tmp.php,home.php,Guestbook.php,aska.cgi,default.asp,jax_guestbook.php,bbs.cg,gastenboek.php,light.cgi,yybbs.cgi,wsdl.php,wp-content,cache_aqbmkwwx.php,.suspected,seo-joy.cgi,google-assist.php,wp-main.php,sql_dump.php,xmlsrpc.php',
		'forms_to_include_honeypot_in'	=>	'',
		'include_urls_spam_protection'  =>	'',
		'write_log_inspector'    => 1,
		'action_inspector'    =>    2,
		'send_email_inspector'    =>    0,
		'delete_period'    => 0,
		'ip_logging'    =>    0,
		'loggable_extensions'    => ['0' => 'com_banners','1' => 'com_cache','2' => 'com_categories','3' => 'com_config','4' => 'com_contact','5' => 'com_content','6' => 'com_installer','7' => 'com_media','8' => 'com_menus','9' => 'com_messages','10' => 'com_modules','11' => 'com_newsfeeds','12' => 'com_plugins','13' => 'com_redirect','14' => 'com_tags','15' => 'com_templates','16' => 'com_users']
    ];


    function __construct()
    {
        parent::__construct();

        global $mainframe, $option;
        
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
				
		// This is needed to avoid errors getting the file from cli
		if ( $mainframe instanceof \Joomla\CMS\Application\CMSWebApplicationInterface ) {
			// Obtenemos las variables de paginación de la petición
			$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getConfig()->get('list_limit',20), 'int');
			$limitstart = $mainframe->getInput()->getInt('limitstart', 0);
								
			// En el caso de que los límites hayan cambiado, los volvemos a ajustar
			$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);
		
			$this->setState('limit', $limit);
			$this->setState('limitstart', $limitstart);     
		}
    }
	
	/**
	 * Obtiene los elementos de una tabla pasada como argumento
	 *
	 * @param   string  $table  Nombre corto de la tabla (sin prefijo)
	 *
	 * @return  array<string>   Lista de valores de la columna 'ip'. Vacío si no hay datos o en caso de error.
	 */
	function getTableData(string $table): array
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Nombre de tabla seguro (ej: #__securitycheckpro_blacklist)
		$tableName = $db->quoteName('#__securitycheckpro_' . $table);
		$column    = $db->quoteName('ip');

		try {
			$query = $db->getQuery(true)
				->select($column)
				->from($tableName);

			$db->setQuery($query);
			$db->execute();
			return (array) $db->loadColumn();
		} catch (\Throwable $e) {
			Log::add('BaseModel. Get table data error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			return [];
		}
	}
	
	/**
     * Función que determina el número de logs marcados como "no leido"
     *
     * @return  int
     *     
     */
    function LogsPending()
    {
        
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = "SELECT COUNT(*) FROM #__securitycheckpro_logs WHERE marked='0'";		
        $db->setQuery($query);
        $db->execute();
        $enabled = $db->loadResult();
    
        return $enabled;
    }
	
	/**
     * Devuelve una fecha datetime usando el offset establecido en Joomla
     *
     * @return  string
     *     
     */
	public function get_Joomla_timestamp()
	{
		$tz = Factory::getConfig()->get('offset');
		$date = new Date('now', $tz);

		return $date->format('Y-m-d H:i:s');
	}
	
	/**
     * Función que obtiene el download id de la tabla update_sites
     *
     * @param   string             $element    The name of the element
     *
     * @return object|string|null
	 * @phpstan-return object{extra_query: string|null,update_site_id: int|null}|string|null
     *     
     */
    function get_extra_query_update_sites_table($element)
    {
		$db = Factory::getContainer()->get(DatabaseInterface::class);    
		$query = $db->getQuery(true);	
					
		try {
			// 1) Buscar extension_id
			$query = $db->getQuery(true)
				->select($db->quoteName('extension_id'))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote($element));
			$db->setQuery($query)->execute();
			$extension_id = $db->loadResult();

			if ($extension_id === null) {
				return null; // Elemento inexistente
			}

			// 2) Buscar update_site_id
			$query = $db->getQuery(true)
				->select($db->quoteName('update_site_id'))
				->from($db->quoteName('#__update_sites_extensions'))
				->where($db->quoteName('extension_id') . ' = ' . (int) $extension_id);
			$db->setQuery($query)->execute();
			$update_site_id = $db->loadResult();

			if ($update_site_id === null) {
				return null; // Sin sitio de actualización enlazado
			}

			// 3) Cargar extra_query + update_site_id
			$query = $db->getQuery(true)
				->select($db->quoteName(['extra_query', 'update_site_id']))
				->from($db->quoteName('#__update_sites'))
				->where($db->quoteName('update_site_id') . ' = ' . (int) $update_site_id);
			$db->setQuery($query)->execute();
			$update_site_data = $db->loadObject();

			if (empty($update_site_data)) {
				return null;
			}

			// 4) Limpiar solo si extra_query es string no vacío
			if (isset($update_site_data->extra_query)) {
				if ($update_site_data->extra_query === null) {
					// Mantener NULL tal cual (evita deprecation y el cambio a "")
					// no hacemos nada
				} elseif ($update_site_data->extra_query !== '') {
					$update_site_data->extra_query = str_replace(
						['dlid=', 'key='],
						'',
						$update_site_data->extra_query
					);
					// Opcional: normalizar a null si queda vacío después de limpiar
					if ($update_site_data->extra_query === '') {
						$update_site_data->extra_query = null;
					}
				}
			}

		} catch (\Throwable $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return 'error';
		}

		return $update_site_data;		
	}
	
	/**
     * Obtiene el valor de una opción de configuración
     *
     *
	 * @param   string        		  				 $key   	 The key of the element
	 * @param   string|int|null|array<int, string>   $default    The default value
	 * @param   string          	  				 $key_name   The name of the key to load
	 * 	 
     * @return  array<string>|mixed
     *     
     */
    public function getValue($key, $default = null, $key_name = 'cparams')
    {
		if(is_null($this->config)) {			
			$this->load($key_name);			
        }	      
		    
        return $this->config->get($key, $default);
        
    }
	
	/**
     * Establece el valor de una opción de configuración
     *
     *
	 * @param   string             $key   		The key of the element
	 * @param   string             $value   	The value to set
	 * @param   bool|string        $save     	If the value must be saved
	 * @param   string             $key_name    The name of the key to set the value
	 * 	 
     * @return  array<string>|null
     *     
     */
    public function setValue($key, $value, $save = false, $key_name = 'cparams')
    {
        if(is_null($this->config)) {			
			$this->load($key_name);			
        }	 
        
        $x = $this->config->set($key, $value);			
           
        if($save) { 
			$saved = $this->save($key_name);
        }
        return $x;
    }
	
	/**
     * Obtiene la configuración de los parámetros del Firewall Web
     *
     *
	 * 	 
     * @return  array<string, array<string>>|null
     *     
     */
    function getConfig()
    {            
        $config = [];
        foreach($this->defaultConfig as $k => $v)
        {			
            $config[$k] = $this->getValue($k, $v, 'pro_plugin');
        }		
        return $config;    
    }
	
	/**
     * Hace una consulta a la tabla espacificada como parámetro
     *
     *
	 * @param   string             $key_name    The name of the key to get the data for
	 * 	 
     * @return  void
     *     
     */
    public function load($key_name)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query 
            ->select($db->quoteName('storage_value'))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote($key_name));
        $db->setQuery($query);
        $res = $db->loadResult();
        
        $this->config = new Registry();       
        if (!empty($res)) {
            $res = json_decode($res, true);
            $this->config->loadArray($res);
        }
    }
	
	/**
	 * Guarda la configuración en la tabla #__securitycheckpro_storage
	 *
	 * @param  string  $keyName  Clave de almacenamiento (columna storage_key)
	 * @return bool              true si guarda OK, false si hubo error (y se encola mensaje)
	 */
	public function save(string $keyName): bool
	{
		// Normaliza clave
		$keyName = strtolower(trim($keyName));

		// Carga perezosa
		if ($this->config === null) {
			$this->load($keyName);
		}

		$app = Factory::getApplication();

		// Validación
		if (!preg_match('/^[a-z0-9_.\-]{1,128}$/', $keyName)) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_STORAGE_KEY'), 'error');
			return false;
		}

		// Normaliza config a array
		if ($this->config instanceof \Joomla\Registry\Registry) {
			$data = $this->config->toArray();
		} else {
			$data = (array) $this->config;
		}		

		if ($keyName !== 'inspector') {
			// Defaults
			$defaults = [
				'priority1' => 'Whitelist',
				'priority2' => 'Blacklist',
				'priority3' => 'DynamicBlacklist',
			];
			foreach ($defaults as $k => $v) {
				if (!array_key_exists($k, $data) || $data[$k] === null || $data[$k] === '') {
					$data[$k] = $v;
				}
			}

			// Duplicados ? fallo
			$uniq = array_unique([$data['priority1'] ?? null, $data['priority2'] ?? null, $data['priority3'] ?? null]);
			if (count($uniq) < 3) {
				$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_DUPLICATE_OPTIONS'), 'warning');
				return false;
			}

			// Quita legacy sólo en no-inspector (o hazlo también en inspector si quieres)
			if (array_key_exists('priority', $data)) {
				unset($data['priority']);
			}
		}

		// JSON robusto (lanzará JsonException si falla)
		try {
			$json = json_encode(
				$data,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
			);
		} catch (\JsonException $je) {
			$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_JSON_ENCODE_ERROR', $je->getMessage()), 'error');
			return false;
		}
		
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Transacción: si algo falla, rollback
		$db->transactionStart();

		try {
			// 1) UPDATE (puede devolver 0 si no existe O si el valor es idéntico)
			$update = $db->getQuery(true)
				->update($db->quoteName('#__securitycheckpro_storage'))
				->set($db->quoteName('storage_value') . ' = ' . $db->quote($json))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote($keyName));

			$db->setQuery($update)->execute();

			if ($db->getAffectedRows() === 0) {
				// 2) Diferenciar: ¿no existe fila o simplemente no cambió nada?
				$existsQuery = $db->getQuery(true)
					->select('1')
					->from($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key') . ' = ' . $db->quote($keyName));

				$db->setQuery($existsQuery);
				$exists = (int) $db->loadResult();

				if ($exists === 0) {
					// No existe -> INSERT
					$object = (object) [
						'storage_key'   => $keyName,
						'storage_value' => $json,
					];
					$db->insertObject('#__securitycheckpro_storage', $object);
				}
				// Si existe -> no hacemos nada (ya estaba igual)
			}

			$db->transactionCommit();

			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
			return true;
		} catch (\Throwable $e) {
			$db->transactionRollback();
			$app->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
	}
	
	/**
     * Devuelve el valor de una columna de una tabla (prefijada) con filtros opcionales.
     *
     * - Valida los identificadores (tabla/columnas de WHERE/ORDER BY).
     * - Usa quoteName() para nombres y quote() para valores.
     * - Reemplaza el prefijo con replacePrefix().
     * - LIMIT 1 por defecto (evita ambigüedades si hay múltiples filas).
     *
     * @param string      $table     Nombre de tabla SIN prefijo (ej.: "scptest_cfg")
     * @param string      $column    Nombre de la columna a devolver (ej.: "valor")
     * @param array       $where     Filtros opcionales: ['col' => 'valor', ...]
     * @param string|null $orderBy   Columna para ordenar (ej.: "id DESC" o "id")
     *
     * @return string|null           Valor escalar o null si no existe / error
     */
    public function getCampoBbdd(string $table, string $column, array $where = [], ?string $orderBy = null): ?string
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            // Validaciones de identificadores
            $this->assertIdentifier($table);
            $this->assertIdentifier($column);

            // Tabla con prefijo real
            $tableName = $db->replacePrefix('#__' . $table);

            $query = $db->getQuery(true)
                ->select($db->quoteName($column))
                ->from($db->quoteName($tableName));

            // WHERE (col = valor)
            foreach ($where as $col => $val) {
                $this->assertIdentifier($col);
                $query->where($db->quoteName($col) . ' = ' . $db->quote((string) $val));
            }

            // ORDER BY opcional (permite "id DESC" o solo "id")
            if ($orderBy !== null && $orderBy !== '') {
                // Aceptamos formatos "col" o "col DESC/ASC"
                $parts = preg_split('/\s+/', trim($orderBy));
                $this->assertIdentifier($parts[0]);
                $dir = (isset($parts[1]) && strcasecmp($parts[1], 'DESC') === 0) ? 'DESC' : 'ASC';
                $query->order($db->quoteName($parts[0]) . ' ' . $dir);
            }

            // Limitar a una fila
            $query->setLimit(1);

            $db->setQuery($query);
            return $db->loadResult();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Valida un identificador SQL sencillo: letras, números y '_' y no comenzar por número.
     * Lanza \InvalidArgumentException si no cumple.
     */
    private function assertIdentifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException('Identificador SQL no válido: ' . $identifier);
        }
    }
	
	/**
	 * Determina estados de plugins/tareas de Securitycheck.
	 *
	 * Opciones:
	 *   1 -> ¿Habilitado?  System / securitycheckpro
	 *   2 -> ¿Existe tarea programada activa?  type=securitycheckpro.cron (state=1)
	 *   3 -> ¿Habilitado?  System / securitycheckpro_update_database
	 *   4 -> ¿Instalado?   System / securitycheckpro_update_database
	 *   5 -> ¿Habilitado?  System / securitycheck_spam_protection
	 *   6 -> ¿Instalado?   System / securitycheck_spam_protection
	 *   7 -> ¿Habilitado?  System / url_inspector
	 *   8 -> ¿Instalado?   System / trackactions
	 *   9 -> ¿Habilitado?  System / securitycheckpro_task_checker
	 *
	 * @return int 1/0
	 */
	public function PluginStatus(int $opcion): int
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Mapa por opción -> acción
		// action: enabled|installed|scheduler
		$map = [
			1 => ['action' => 'enabled',   'folder' => 'system', 'element' => 'securitycheckpro'],
			2 => ['action' => 'scheduler', 'type'   => 'securitycheckpro.cron'],
			3 => ['action' => 'enabled',   'folder' => 'system', 'element' => 'securitycheckpro_update_database'],
			4 => ['action' => 'installed', 'folder' => 'system', 'element' => 'securitycheckpro_update_database'],
			5 => ['action' => 'enabled',   'folder' => 'system', 'element' => 'securitycheck_spam_protection'],
			6 => ['action' => 'installed', 'folder' => 'system', 'element' => 'securitycheck_spam_protection'],
			7 => ['action' => 'enabled',   'folder' => 'system', 'element' => 'url_inspector'],
			8 => ['action' => 'installed', 'folder' => 'system', 'element' => 'trackactions'],
			9 => ['action' => 'enabled',   'folder' => 'system', 'element' => 'securitycheckpro_task_checker'],
		];

		$conf = $map[$opcion] ?? null;
		if ($conf === null) {
			return 0;
		}
		
		try {
			switch ($conf['action']) {
				case 'enabled':
					// Usa la API oficial de Joomla para plugins
					$enabled = PluginHelper::isEnabled((string) $conf['folder'], (string) $conf['element']);
					return $enabled ? 1 : 0;

				case 'installed':
					// ¿Existe en #__extensions? type=plugin + folder + element
					$folder = (string) ($conf['folder'] ?? '');
					$element = (string) ($conf['element'] ?? '');
					$q = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->quoteName('#__extensions'))
						->where($db->quoteName('type')   . ' = ' . $db->quote('plugin'))
						->where($db->quoteName('folder') . ' = :folder')
						->where($db->quoteName('element'). ' = :element')
						->setLimit(1)
						->bind(':folder',  $folder,  ParameterType::STRING)
						->bind(':element', $element, ParameterType::STRING);

					$db->setQuery($q);
					
					$count = (int) $db->loadResult();
					return $count > 0 ? 1 : 0;

				case 'scheduler':
					// ¿Hay tarea activa (state=1) del tipo indicado?
					$type = (string) ($conf['type'] ?? '');
					$state = 1; 
					if ($type === '') {
						return 0;
					}

					$q = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->quoteName('#__scheduler_tasks'))
						->where($db->quoteName('type')  . ' = :type')
						->where($db->quoteName('state') . ' = :state')
						->bind(':type',  $type, ParameterType::STRING)
						->bind(':state', $state,     ParameterType::INTEGER)
						->setLimit(1);						

					$db->setQuery($q);
					$count = (int) $db->loadResult();
					return $count > 0 ? 1 : 0;
			}
		} catch (\Throwable $e) {
			Log::add('BaseModel. PluginStatus function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			return 0;
		}

		return 0;
	}
		
		
	/**
     * Función obtener el estado de una subscripción
     *
     * 	 
     * @return  void
     *     
     */
    function GetSubscriptionsStatus()
    {
               
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
        
        // Chequeamos si el plugin 'update database' está instalado
        $updateDbPluginExists    = $this->PluginStatus(4);    
        $trackActionsPluginExists  = $this->PluginStatus(8);
    
        // Buscamos el Download ID 
        $downloadid = '';
		
		// 1) Intentar obtener desde el plugin de sistema (si está habilitado)
		if (PluginHelper::isEnabled('system', 'securitycheckpro_update_database')) {
			$downloadData = $this->get_extra_query_update_sites_table('securitycheckpro_update_database');

			if ($downloadData !== 'error') {
				$remoteDlid = $downloadData?->extra_query ?? null;

				// Si hay un DLID remoto válido, úsalo
				if ($remoteDlid !== null && $remoteDlid !== '') {
					$downloadid = trim((string) $remoteDlid);
				}
			}
		}

		// 2) Fallback al parámetro del componente si sigue vacío
		if ($downloadid === '' || $downloadid === null) {
			$appParams  = ComponentHelper::getParams('com_securitycheckpro');
			$componentDlid = trim((string) $appParams->get('downloadid', ''));
			if ($componentDlid !== '') {
				$downloadid = $componentDlid;
			}
		}	

		// 3) Validación final
		if ($downloadid === '' || $downloadid === null) {
			$mainframe->setUserState("scp_update_database_subscription_status", Text::_('COM_SECURITYCHECKPRO_UPDATE_DATABASE_DOWNLOAD_ID_EMPTY'));        
            $mainframe->setUserState("scp_subscription_status", Text::_('COM_SECURITYCHECKPRO_UPDATE_DATABASE_DOWNLOAD_ID_EMPTY'));
            $mainframe->setUserState("trackactions_subscription_status", Text::_('COM_SECURITYCHECKPRO_UPDATE_DATABASE_DOWNLOAD_ID_EMPTY'));
			return;
		}
		
		if (!function_exists('curl_init')) {
            $mainframe->setUserState("scp_update_database_subscription_status", Text::_('COM_SECURITYCHECKPRO_CURL_NOT_DEFINED'));        
            $mainframe->setUserState("scp_subscription_status", Text::_('COM_SECURITYCHECKPRO_CURL_NOT_DEFINED'));
            $mainframe->setUserState("trackactions_subscription_status", Text::_('COM_SECURITYCHECKPRO_CURL_NOT_DEFINED'));
			return;			
        } 
		
		try {
			// Componente principal
			$this->GetResponse('scp', $downloadid);

			// Update Database (si existe/te interesa consultar)
			if ($updateDbPluginExists) {
				$this->GetResponse('update', $downloadid);
			} else {
				$mainframe->setUserState("scp_update_database_subscription_status", Text::_('COM_SECURITYCHECKPRO_PLUGIN_NOT_INSTALLED'));
			}

			// Track Actions (si existe)
			if ($trackActionsPluginExists) {
				$this->GetResponse('trackactions', $downloadid);
			} else {
				$mainframe->setUserState("trackactions_subscription_status", Text::_('COM_SECURITYCHECKPRO_PLUGIN_NOT_INSTALLED'));
			}
		} catch (\Throwable $e) {
			// Mensaje genérico ante error de red/timeout/etc.
			$errorMsg = Text::_('COM_SECURITYCHECKPRO_SUBSCRIPTION_CHECK_FAILED');
			Log::add("Subscription check failed. Error: " . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			$mainframe->setUserState('scp_update_database_subscription_status', $errorMsg);
			$mainframe->setUserState('scp_subscription_status', $errorMsg);
			$mainframe->setUserState('trackactions_subscription_status', $errorMsg);			
		}
    }
	
	/**
     * Función obtener el estado de una subscripción
     *
     * @param   string             $product    The name of the product
	 * @param   string             $downloadid    The download id
	 * 	 
     * @return  void
     *     
     */
    function GetResponse(string $product, string $downloadid): void
	{
		$app = Factory::getApplication();

		$map = [
			'update'       => ['plan_id' => 14, 'name' => 'Update Database',   'stateKey' => 'scp_update_database_subscription_status'],
			'scp'          => ['plan_id' => 12, 'name' => 'Securitycheck Pro', 'stateKey' => 'scp_subscription_status'],
			'trackactions' => ['plan_id' => 17, 'name' => 'Track Actions',     'stateKey' => 'trackactions_subscription_status'],
		];
		if (!isset($map[$product])) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR'), 'error');
			return;
		}

		$downloadid = trim($downloadid);		

		$endpoint = 'https://securitycheck.protegetuordenador.com/status.php';
		$planId   = $map[$product]['plan_id'];
		$prodName = $map[$product]['name'];
		$stateKey = $map[$product]['stateKey'];

		$ch = curl_init();
		try {
			curl_setopt_array($ch, [
				CURLOPT_URL            => $endpoint,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query(['dlid' => $downloadid, 'plan_id' => $planId], '', '&', PHP_QUERY_RFC3986),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_USERAGENT      => defined('SCP_USER_AGENT') ? SCP_USER_AGENT : 'Securitycheck/1.0',
				CURLOPT_FAILONERROR    => false,                 // Queremos leer el body en 4xx/5xx
				CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_TIMEOUT        => 10,
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_HTTPHEADER     => ['Accept: text/plain'],
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
				CURLOPT_BUFFERSIZE     => 16384,
			]);
			if (defined('SCP_CACERT_PEM') && is_readable(SCP_CACERT_PEM)) {
				curl_setopt($ch, CURLOPT_CAINFO, SCP_CACERT_PEM);
			}

			$response = curl_exec($ch);
			$errNo    = curl_errno($ch);
			$errStr   = curl_error($ch);
			$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

			// Error de transporte/TLS
			if ($response === false) {
				Log::add("Subscription POST error ($prodName): [$errNo] $errStr", Log::ERROR, 'com_securitycheckpro');
				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_UNABLE_TO_RETRIEVE_STATUS', $prodName), 'error');
				$this->setUserStateCompat($app, $stateKey, Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR'));
				return;
			}

			$body = (string) $response;

			// Detección bloque SiteGround (403/406 o 'well-known' en HTML)
			if ($httpCode === 403 || $httpCode === 406 || stripos($body, 'well-known') !== false) {
				$blockedIp = self::extractIpFromHtml($body); // puede devolver null
				if ($blockedIp) {
					$msg = Text::_('COM_SECURITYCHECKPRO_IP_BLOCKED') . ': ' . $blockedIp;
					$app->enqueueMessage('Your server IP seems blocked by SiteGround WAF. ' . $msg, 'error');
					$this->setUserStateCompat($app, $stateKey, $msg);
				} else {
					$app->enqueueMessage('Your server IP seems blocked by SiteGround WAF. Please contact support to whitelist it.', 'error');
					$this->setUserStateCompat($app, $stateKey, Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR'));
				}
				// Log técnico
				Log::add("SiteGround block detected ($prodName) HTTP $httpCode. Snippet: " . substr(trim($body), 0, 256), Log::WARNING, 'com_securitycheckpro');
				return;
			}

			// HTTP distinto de 2xx (otro error)
			if ($httpCode < 200 || $httpCode >= 300) {
				Log::add("Subscription POST HTTP $httpCode ($prodName): " . substr(trim($body), 0, 256), Log::WARNING, 'com_securitycheckpro');
				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_UNABLE_TO_RETRIEVE_STATUS', $prodName), 'error');
				$this->setUserStateCompat($app, $stateKey, Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR'));
				return;
			}

			// Sólo aceptamos '5' (Active) o '4' (Expired)
			$payload = trim($body);

			if ($payload === '5') {
				$this->setUserStateCompat($app, $stateKey, Text::_('COM_SECURITYCHECKPRO_ACTIVE'));
			} elseif ($payload === '4') {
				$this->setUserStateCompat($app, $stateKey, Text::_('COM_SECURITYCHECKPRO_EXPIRED'));
			} else {
				Log::add("Subscription POST unexpected payload ($prodName): '" . substr($payload, 0, 128) . "'", Log::NOTICE, 'com_securitycheckpro');
				$this->setUserStateCompat($app, $stateKey, Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ERROR'));
			}
		} finally {			
		}
	}
	
	/**
	 * Extrae una IP válida (IPv4/IPv6) del HTML bloque de SiteGround, si aparece.
	 * Devuelve string con la IP o null si no se encuentra.
	 */
	public static function extractIpFromHtml(string $html): ?string
	{
		$src = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		// 1) Meta refresh típico de SiteGround: content="0;...y=ipc:IP:TS"
		if (preg_match('/<meta[^>]+http-equiv=["\']?refresh["\']?[^>]*content=["\']?([^"\'>]+)["\']?/i', $src, $m)) {
			$content = $m[1];
			$target  = (strpos($content, ';') !== false) ? substr($content, strpos($content, ';') + 1) : $content;
			if (stripos($target, 'url=') === 0) {
				$target = substr($target, 4);
			}
			$target = html_entity_decode($target, ENT_QUOTES | ENT_HTML5, 'UTF-8');

			// y=ipc:<IP>[:<timestamp>]
			$query = (strpos($target, '?') !== false) ? substr($target, strpos($target, '?') + 1) : $target;
			$query = str_replace('&amp;', '&', $query);
			parse_str($query, $params);
			
			$y = '';

			if (isset($params['y'])) {
				$y = (string) $params['y'];
				
				// Normaliza prefijo específico de SiteGround: y=ipc:<IP>[:<timestamp>]
				if (stripos($y, 'ipc:') === 0) {
					$y = substr($y, 4); // quita "ipc:"
				}
			}

			// IPv4 primero
			$ipv4Pattern = '/(?<!\d)(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)'
			 . '(?:\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}(?!\d)/';
			if (preg_match($ipv4Pattern, $y, $mm)) {
				if (filter_var($mm[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					return $mm[0];
				}
			}

			// IPv6 después (ignorando scope-id)
			if (preg_match_all('/[0-9A-Fa-f:\[\]%]{2,}/', $y, $mm)) { // incluye '%'
				foreach ($mm[0] as $raw) {
					// descarta tokens con scope-id -> ahora sí entra en $raw
					if (strpos($raw, '%') !== false) {
						continue;
					}

					// limpia brackets/puntuación liviana pero NO los ':'
					$cand = trim($raw, "[](){}<>;,\" \t\r\n");

					// si quedó algún prefijo alfabético (p.ej. 'c:' por 'ipc:')
					$cand = preg_replace('/^[A-Za-z]+:/', '', $cand);

					// normaliza posibles ':' iniciales sueltos
					$cand = ltrim($cand, ':');

					// elimina timestamps tipo ':1709363162' (opcionalmente con .xxx)
					$cand = preg_replace('/:(?:\d{9,})(?:\.\d+)?$/', '', $cand);

					if (strpos($cand, ':') !== false
						&& filter_var($cand, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
					) {
						return $cand;
					}
				}
			}			

			// Si no hubo suerte con 'y', escanea el target completo
			if ($ip = self::extractIpFromHtml_scanCandidates($target)) {
				return $ip;
			}
		}

		// 2) Fallback: escanea todo el HTML
		return self::extractIpFromHtml_scanCandidates($src);
	}

	public static function extractIpFromHtml_scanCandidates(string $text): ?string
	{
		// IPv4 — octet-aware, sin lookarounds
		$ipv4Pattern = '/\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b/';
		if (preg_match_all($ipv4Pattern, $text, $m)) {
			foreach ($m[0] as $cand) {
				if (filter_var($cand, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					return $cand; // preferencia IPv4
				}
			}
		}

		// IPv6 — tokens amplios (no recortar ':') y descartar scope-id
		if (preg_match_all('/[0-9A-Fa-f:\.\[\]%]{2,}/', $text, $m)) {
			foreach ($m[0] as $raw) {
				if (strpos($raw, '%') !== false) {
					continue; // descarta direcciones con scope-id (p.ej. fe80::1%lo0)
				}
				$cand = trim($raw, "[](){}<>,;\"' \t\r\n"); // ¡no incluir ':' en el charlist!
				if (strpos($cand, ':') !== false && filter_var($cand, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					return $cand;
				}
			}
		}

		return null;
	}

	/**
	 * Compat: setUserState puede no existir según interfaz en algunas versiones.
	 */
	function setUserStateCompat($app, string $key, $value): void
	{
		if (method_exists($app, 'setUserState')) {
			$app->setUserState($key, $value);
		} else {
			$app->getSession()->set($key, $value);
		}
	}
	
	/**
	 * Actualiza un campo de la tabla #__securitycheckpro_file_manager en el registro con id=1.
	 * - Valida el nombre de columna contra el esquema real.
	 *
	 * @param string $campo  Nombre de columna a actualizar (validado contra el esquema).
	 * @param mixed  $valor  Valor a establecer.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException Si la columna no existe.
	 * @throws \RuntimeException         Si ocurre un error de base de datos.
	 */
	function setCampoFilemanager(string $campo, $valor): void
	{
		/** @var DatabaseInterface $db */
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$table = $db->replacePrefix('#__securitycheckpro_file_manager');

		// 1) Validar el nombre de columna contra el esquema real
		$cols = $db->getTableColumns($table, false);
		if (!isset($cols[$campo])) {
			throw new \InvalidArgumentException('Columna no permitida: ' . $campo);
		}

		// 2) UPDATE seguro
		$query = $db->getQuery(true)
			->update($db->quoteName($table))
			->set($db->quoteName($campo) . ' = ' . $db->quote($valor))
			->where($db->quoteName('id') . ' = 1');

		try {
			$db->setQuery($query);
			$db->execute();
			//return $db->getAffectedRows() > 0;
		} catch (\Throwable $e) {
			throw new \RuntimeException('Error al actualizar file_manager: ' . $e->getMessage(), 0, $e);
		}
	}


    /**
	 * Obtiene el valor de un campo (permitido) de la tabla #__securitycheckpro_file_manager
	 *
	 * @param  string $campo   Nombre del campo a consultar (debe estar en la lista permitida)
	 * @return ?string         Valor del campo o null si no existe / error
	 */
	function GetCampoFilemanager(string $campo): ?string
	{
		// Whitelist (exacta) de columnas permitidas
		$allowedFields = [
			'id',
			'last_check',
			'last_check_integrity',
			'files_scanned',
			'files_scanned_integrity',
			'files_with_incorrect_permissions',
			'files_with_bad_integrity',
			'estado',
			'estado_integrity',
			'hash_alg',
			'estado_cambio_permisos',
			'estado_clear_data',
			'last_task',
			'cron_tasks_launched',
			'last_check_malwarescan',
			'files_scanned_malwarescan',
			'suspicious_files',
			'estado_malwarescan',
			'last_online_check_malwarescan',
			'online_checked_files',
			'online_checked_hashes',
			'last_check_database',
		];

		if (!in_array($campo, $allowedFields, true)) {
            Log::add(
                sprintf('Campo no permitido en GetCampoFilemanager: %s', $campo),
                Log::WARNING,
                'com_securitycheckpro'
            );
            return null;
        }

		try {
			/** @var DatabaseInterface $db */
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);

			$query
				->select($db->quoteName($campo))
				->from($db->quoteName('#__securitycheckpro_file_manager'))
				->where($db->quoteName('id') . ' = 1');

			// limit 1 por eficiencia
			$db->setQuery($query, 0, 1);
			$result = $db->loadResult();

			if ($result === null && $campo === 'estado') {
				return 'ERROR';
			}

			return $result;

		} catch (\Throwable $e) {
			Log::add(
				'Error en GetCampoFilemanager: ' . $e->getMessage(),
				Log::ERROR,
				'com_securitycheckpro'
			);
			return null;
		}
	}
	
	/**
	 * Comprueba si una IP está en una lista (blacklist/whitelist) almacenada en BD.
	 * - Usa IpHelper::IPinList para cubrir IPv4/IPv6, rangos, CIDR, etc.
	 * - Normaliza comodines IPv4 estilo 192.168.*.* -> CIDR equivalente
	 * @param  string $ip    IP a buscar.
	 * @param  string $lista Clave de lista permitida (p.ej. 'blacklist' | 'whitelist').
	 * @return bool
	 */
	function ChequearIpEnLista(string $ip, string $lista): bool
	{
		// 1) Validación de IP
		$isV4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
		$isV6 = !$isV4 && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
		if (!$isV4 && !$isV6) {
			return false;
		}

		// 2) Allow-list de nombres de tabla
		$allowedLists = [
			'blacklist' => '#__securitycheckpro_blacklist',
			'whitelist' => '#__securitycheckpro_whitelist',
		];
		$table = $allowedLists[$lista] ?? null;
		if ($table === null) {
			return false;
		}

		// 3) Cargar solo la columna necesaria
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->select($db->quoteName('ip'))
				->from($db->quoteName($table));
			$db->setQuery($query);

			/** @var array<int,string> $rules */
			$rules = (array) $db->loadColumn();
		} catch (\Throwable) {
			return false;
		}

		if ($rules === []){
			return false;
		}

		// 4) Normalizar/filtrar reglas y compatibilidad con '*'
		$normalizeWildcardV4 = static function (string $pattern): string {
			// Solo tratamos IPv4 con '*' en octetos completos. Ej: 10.*.*.* | 192.168.1.* | 172.16.*.*
			if (strpos($pattern, '*') === false) {
				return $pattern;
			}
			$octs = explode('.', $pattern);
			if (count($octs) !== 4) {
				// Si el formato es raro (p.ej. "192.168.*"), no lo tocamos; IpHelper lo ignorará.
				return $pattern;
			}

			// Contar octetos fijos iniciales para convertir a /CIDR
			$fixed = 0;
			foreach ($octs as $o) {
				if ($o === '*') {
					break;
				}
				if ($o === '' || !ctype_digit($o) || (int)$o < 0 || (int)$o > 255) {
					return $pattern; // basura: lo dejamos tal cual
				}
				$fixed++;
			}

			// Mapear a prefijo CIDR: 1 octeto fijo => /8, 2 => /16, 3 => /24
			if ($fixed === 0) {
				return $pattern; // "*.*.*.*" -> no tiene sentido; lo dejamos
			}

			$base = array_map(static fn($v) => $v === '*' ? '0' : $v, $octs);
			$cidr = 8 * $fixed;
			return implode('.', $base) . '/' . $cidr;
		};

		$cleanRules = [];
		foreach ($rules as $r) {
			$r = trim((string) $r);
			if ($r === '') {
				continue;
			}
			// Normaliza comodines IPv4
			if (strpos($r, '*') !== false) {
				$r = $normalizeWildcardV4($r);
			}
			$cleanRules[] = $r;
		}

		if ($cleanRules === []) {
			return false;
		}

		// 5) Una sola llamada que cubre IPv4/IPv6, rangos, CIDR, etc.
		return IpHelper::IPinList($ip, $cleanRules);
	}
	
	/**
	 * Compatibilidad con versiones antiguas en plugins (i.e. url_inspector)
	 *
	 *
	 * @param  string $ip    IP a buscar.
	 * @param  string $lista Clave de lista permitida (p.ej. 'blacklist' | 'whitelist').
	 * @return bool
	 */
	function chequear_ip_en_lista(string $ip, string $lista): bool
	{
		/*@trigger_error(
			__FUNCTION__ . '() está obsoleto, usa ChequearIpEnLista() en su lugar.',
			E_USER_DEPRECATED
		);*/
		return $this->ChequearIpEnLista($ip, $lista);
	}
	
	/**
     * Obtiene la configuración de los parámetros del Cron
     *
     * 	 
     * @return  array<string, array<string>>|null
     *     
     */
    function getCronConfig()
    {
        $config = array();
        foreach($this->defaultConfig as $k => $v)
        {
            $config[$k] = $this->getValue($k, $v, 'cron_plugin');
        }
        return $config;
    }

    /**
     * Obtiene la configuración de los parámetros del Control Center
     *
     * 	 
     * @return  array<string, int>| array<string, string>|array<string, array<string>>
     *     
     */
    public function getControlCenterConfig()
    {
        
        $config = [];
        foreach($this->defaultConfig as $k => $v)
        {
            $config[$k] = $this->getValue($k, $v, 'controlcenter');
        }
        return $config;
    }
	
	/**
     * Guarda la modificación de los parámetros de la opción 'Mode'
     *
	 * @param    array<string>       $newParams    Array with the values to add
	 * @param    string       		 $key_name     The key of the storage table to insert the data
     * 	 
     * @return  void
     *     
     */
    function saveConfig($newParams, $key_name = 'cparams')
    {
        foreach($newParams as $key => $value)
        {
            // Do not save unnecessary parameters
            if(!array_key_exists($key, $this->defaultConfig)) { continue;
            }        
            $this->setValue($key, $value, '', $key_name);
        }
    
        $saved = $this->save($key_name);    
    }
	
	/**
	 * Limpia un string de caracteres no válidos según la opción especificada
	 *
	 * option=1: trata la entrada como lista separada por comas y aplica
	 *           saneado por token + deduplicado.
	 * option=2: trata la entrada como una cadena plana y elimina espacios/control.
	 *
	 * Seguridad:
	 *  - Quita bytes nulos y controles (ASCII y varios invisibles Unicode)
	 *  - Normaliza a UTF-8 válido; elimina bytes inválidos
	 *  - Aplica listas de permitidos (ajusta el patrón si necesitas otros símbolos)
	 */
	function clearstring(string $string_to_clear, int $option = 1): string
	{
		// --- Parámetros de seguridad (ajusta a tu caso) ---
		$MAX_TOTAL_LEN  = 8192;   // límite duro total
		$MAX_TOKEN_LEN  = 256;    // límite por token en opción 1
		// Caracteres permitidos básicos (Ajusta si necesitas más símbolos):
		// letras, dígitos, punto, coma, guion, guion_bajo, dos puntos, barra y arroba
		$ALLOWED_CHARS_REGEX = '/[^A-Za-z0-9.,_:@\-\/]/u';

		// --- Normaliza UTF-8 y elimina caracteres problemáticos ---
		$s = $this->ensure_valid_utf8($string_to_clear);
		$s = $this->normalize_unicode_nfkc_if_available($s);
		$s = $this->strip_invisible_chars($s);

		if ($option === 1) {
			// Lista separada por comas ? tokens limpios y deduplicados
			$tokens = preg_split('/\s*,\s*/u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];

			$clean = [];
			foreach ($tokens as $t) {
				// Quita controles/invisibles residuales
				$t = $this->strip_invisible_chars($t);

				// Aplica whitelist
				$t = preg_replace($ALLOWED_CHARS_REGEX, '', $t) ?? '';
				if ($t === '') {
					continue;
				}

				// Limita longitud de token
				if (mb_strlen($t, 'UTF-8') > $MAX_TOKEN_LEN) {
					$t = mb_substr($t, 0, $MAX_TOKEN_LEN, 'UTF-8');
				}

				$clean[] = $t;
			}

			// Deduplica preservando orden
			$clean = array_values(array_unique($clean));

			// Recompone; colapsa comas duplicadas si quedase alguna
			$out = implode(',', $clean);
			$out = preg_replace('/,{2,}/', ',', $out) ?? $out;
			$out = trim($out, ',');

		} else {
			// Opción 2: cadena plana ? quita todo tipo de espacio/control
			// (incluye \s y separadores Unicode comunes)
			$s = preg_replace('/[ \t\r\n\0\x0B\x0C\p{Z}]+/u', '', $s) ?? '';
			// Aplica whitelist sobre la cadena resultante
			$out = preg_replace($ALLOWED_CHARS_REGEX, '', $s) ?? '';
		}

		// Límite total defensivo
		if (mb_strlen($out, 'UTF-8') > $MAX_TOTAL_LEN) {
			$out = mb_substr($out, 0, $MAX_TOTAL_LEN, 'UTF-8');
		}

		return $out;
	}

	/** Garantiza UTF-8 válido eliminando bytes inválidos (sin lanzar warnings). */
	function ensure_valid_utf8(string $s): string
	{
		if (function_exists('mb_convert_encoding')) {
			// Convierte a UTF-8 y filtra bytes inválidos
			$s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
		}
		return $s;
	}

	/** Normaliza Unicode a NFKC si hay intl; si no, devuelve tal cual. */
	function normalize_unicode_nfkc_if_available(string $s): string
	{
		if (class_exists('\Normalizer')) {
			return \Normalizer::normalize($s, \Normalizer::FORM_KC) ?? $s;
		}
		return $s;
	}

	/**
	 * Elimina caracteres invisibles / de control:
	 *  - ASCII C0/C1, NULL
	 *  - Separadores de línea no imprimibles
	 *  - ZWSP, ZWNJ, ZWJ, LRM, RLM, LRE/RLE/PDF/LRI/RLI/FSI/PDI
	 */
	function strip_invisible_chars(string $s): string
	{
		// Quita bytes nulos rápido
		$s = str_replace("\0", '', $s);

		// Controles ASCII (excepto \n, \r, \t si quisieras conservarlos)
		$s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s) ?? $s;

		// Invisibles Unicode frecuentes
		$invisibles = [
			"\u{200B}", // ZWSP
			"\u{200C}", // ZWNJ
			"\u{200D}", // ZWJ
			"\u{200E}", // LRM
			"\u{200F}", // RLM
			"\u{202A}", // LRE
			"\u{202B}", // RLE
			"\u{202C}", // PDF
			"\u{202D}", // LRO
			"\u{202E}", // RLO
			"\u{2066}", // LRI
			"\u{2067}", // RLI
			"\u{2068}", // FSI
			"\u{2069}", // PDI
			"\u{FEFF}", // BOM
		];
		$s = str_replace($invisibles, '', $s);

		return $s;
	}
	
	/**
	 * Encrypt data using OpenSSL (AES-256-CBC)
	 * Based on code from: https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt
	 *
	 * @param    string       $plaindata    		The string in plain data format
	 * @param    string       $encryption_key       The encrypton key
     * 	 
     * @return  string|empty
     *     
     */
    function encrypt($plaindata, $encryption_key)
	{
		$method = "AES-256-CBC";
		
		if (empty($encryption_key))
		{
			return;
		}
			
		$iv = openssl_random_pseudo_bytes(16);
			
		$hash_pbkdf2 = hash_pbkdf2("sha512", $encryption_key, "", 5000);
		$key = substr($hash_pbkdf2, 0, 256);
		$hashkey = substr($hash_pbkdf2, 256, 512);
			
		$cipherdata = openssl_encrypt($plaindata, $method, $key, OPENSSL_RAW_DATA, $iv);

		if ($cipherdata === false)
		{
			$cryptokey = "**REMOVED**";
			$hashkey = "**REMOVED**";
			throw new \Exception("Internal error: openssl_encrypt() failed:".openssl_error_string());
		}

		$hash = hash_hmac('sha256', $cipherdata.$iv, $hashkey, true);

		if ($hash === false)
		{
			$cryptokey = "**REMOVED**";
			$hashkey = "**REMOVED**";
			throw new \Exception("Internal error: hash_hmac() failed");
		}

		return base64_encode($iv.$hash.$cipherdata);
	}
	
	/**
	 * Decrypt data using OpenSSL (AES-256-CBC)
	 * Based on code from: https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt
	 *
	 * @param    string       $encrypteddata    	The string encrypted
	 * @param    string       $encryption_key       The encrypton key
     * 	 
     * @return  string|empty
     *     
     */
	function decrypt($encrypteddata, $encryption_key)
	{
		$method = "AES-256-CBC";
			
		$encrypteddata = base64_decode($encrypteddata);
			
		$iv = substr($encrypteddata, 0, 16);
		$hash = substr($encrypteddata, 16, 32);
		$cipherdata = substr($encrypteddata, 48);
							
		$hash_pbkdf2 = hash_pbkdf2("sha512", $encryption_key, "", 5000);
		$key = substr($hash_pbkdf2, 0, 256);
		$hashkey = substr($hash_pbkdf2, 256, 512);
			
		if (!hash_equals(hash_hmac('sha256', $cipherdata.$iv, $hashkey, true), $hash))
		{
			/*$cryptokey = "**REMOVED**";
			$hashkey = "**REMOVED**";
			throw new \Exception("Internal error: Hash verification failed");*/
			return "Internal error: Hash verification failed";
		}

		$plaindata = openssl_decrypt($cipherdata, $method, $key, OPENSSL_RAW_DATA, $iv);

		if ($plaindata === false)
		{
			/*$cryptokey = "**REMOVED**";
			$hashkey = "**REMOVED**";
			throw new \Exception("Internal error: openssl_decrypt() failed:".openssl_error_string());*/
			return "Internal error: openssl_decrypt() failed";
		}

		return $plaindata;
	}
	
	/**
	 * Modifica de forma segura un valor (add/delete) en un parámetro tipo lista del componente.
	 *
	 * Seguridad:
	 *  - Allow-list de nombres de parámetro
	 *  - Normalización/validación de valores
	 *  - Coincidencias exactas (sin strstr)
	 *  - Almacén en array JSON (no CSV)
	 */
	function modifyComponentValue(
		string $paramName,
		string $value,
		string $option,
		array $allowedParams = ['file_manager_path_exceptions','file_integrity_path_exceptions', 'malwarescan_path_exceptions']
	): bool {
		$app = Factory::getApplication(); // NEW

		if (!in_array($paramName, $allowedParams, true)) {
			return false;
		}

		$option = strtolower(trim($option));
		if ($option !== 'add' && $option !== 'delete') {
			return false;
		}

		$norm = static function (string $v): string {
			$v = trim($v);
			$v = preg_replace('/[[:cntrl:]]+/u', '', $v) ?? '';
			$v = preg_replace('/\s{2,}/u', ' ', $v) ?? '';
			if (mb_strlen($v, 'UTF-8') > 1024) {
				$v = mb_substr($v, 0, 1024, 'UTF-8');
			}
			if (str_contains($v, ',')) {
				return '';
			}
			return $v;
		};

		$value = $norm($value);
		if ($value === '') {
			return false;
		}

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$component   = ComponentHelper::getComponent('com_securitycheckpro');
		$componentId = (int) $component->id;

		$table = new \Joomla\CMS\Table\Extension($db);
		if (!$table->load($componentId)) {
			return false;
		}

		$params = new Registry($table->params ?: '{}');

		$current = $params->get($paramName);
		if (is_string($current) && $current !== '') {
			$tmp = array_map('trim', explode(',', $current));
			$tmp = array_values(array_filter($tmp, static fn($x) => $x !== ''));
			$current = $tmp;
		} elseif (!is_array($current)) {
			$current = [];
		}

		$current = array_values(array_unique(array_filter(array_map($norm, $current), static fn($x) => $x !== '')));

		$changed = false;
		$duplicateDetected = false; 

		if ($option === 'add') {
			if (!in_array($value, $current, true)) {
				$current[] = $value;
				$changed = true;
			} else {
				$duplicateDetected = true;
			}
		} else { // delete
			$idx = array_search($value, $current, true);
			if ($idx !== false) {
				unset($current[$idx]);
				$current = array_values($current);
				$changed = true;
			}
		}

		// --- Si no hay cambios: NO persistimos ---
		if (!$changed) { 
			if ($option === 'add' && $duplicateDetected) {
				// único warning (no repetimos por cada duplicado)
				$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ALREADY_EXISTS'), 'warning');
			}
			return false;
		}

		// --- Persistencia (sólo si hubo cambios) ---
		// Guardamos ya normalizado (como lista) y dejamos que Registry lo serialice a JSON
		/** @var string $csv */
		$csv = implode(',', $current);           // "ruta1,ruta2,ruta3"
		$params->set($paramName, $csv);          // se guardará como string en el JSON de params		

		if (!$table->bind(['params' => $params->toString('JSON')])) {
			$app->enqueueMessage($table->getError(), 'error');
			return false;
		}
		if (!$table->check()) {
			$app->enqueueMessage($table->getError(), 'error');
			return false;
		}
		if (!$table->store()) {
			$app->enqueueMessage($table->getError(), 'error');
			return false;
		}

		\Joomla\CMS\Cache\CacheController::getInstance('callback', ['defaultgroup' => 'com_securitycheckpro'])->clean();

		return true; 
	}

	
	/**
	 * Añade o elimina rutas en las listas de excepciones de forma segura.
	 *
	 * @param  string $type    'malwarescan' | 'permissions' | 'integrity'
	 * @param  string $action  'add' | 'delete'
	 * @return void
	 */
	function manageExceptions(string $type, string $action): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app   = Factory::getApplication();
		/** @var Input $jinput */
		$jinput = $app->getInput();

		// --- CSRF (requiere que el formulario envíe el token) ---
		if (!Session::checkToken('post')) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}
		
		// --- Allow-lists de tipo y acción ---
		$allowedTypes  = ['malwarescan', 'permissions', 'integrity'];
		$allowedAction = ['add', 'delete'];
		if (!in_array($type, $allowedTypes, true) || !in_array($action, $allowedAction, true)) {
			throw new \InvalidArgumentException('Invalid type or action.');
		}

		// --- Parámetros del componente ---
		$params = ComponentHelper::getParams('com_securitycheckpro');

		// --- Selección segura de origen de datos e "option" destino ---
		$inputKey = null;
		$option   = 'file_integrity_path_exceptions';

		switch ($type) {
			case 'malwarescan':
				$inputKey = 'malwarescan_status_table';
				$useFileMgr = (int) $params->get('use_filemanager_exceptions', 1) === 1;
				$option = $useFileMgr ? 'file_integrity_path_exceptions' : 'malwarescan_path_exceptions';
				break;

			case 'permissions':
				$inputKey = 'filesstatus_table';
				$option   = 'file_manager_path_exceptions';
				break;

			case 'integrity':
				$inputKey = 'filesintegritystatus_table';
				$option   = 'file_integrity_path_exceptions';
				break;
		}
		
		// --- Recogida como array estrictamente tipado ---
		$paths = (array) $jinput->get($inputKey, [], 'array');
		
		// --- Normalización y saneado defensivo ---
		$normalize = static function ($path): ?string {
			if (!is_string($path)) {
				return null;
			}

			// recorte tamaño máximo y espacios
			$p = trim(mb_substr($path, 0, 2048));

			// elimina bytes nulos y controles
			$p = preg_replace('/[\x00-\x1F\x7F]/u', '', $p) ?? '';

			// unifica separadores y colapsa barras
			$p = str_replace('\\', '/', $p);
			$p = preg_replace('#/+#', '/', $p) ?? $p;

			// evita traversal obvio
			if (preg_match('#(^|/)\.\.(?:/|$)#', $p)) {
				return null;
			}

			// evita valores vacíos o sospechosos
			if ($p === '' || $p === '/' || $p === '.' ) {
				return null;
			}

			return $p;
		};

		$cleanPaths = array_filter(array_map($normalize, $paths));
		$cleanPaths = array_values(array_unique($cleanPaths)); // dedup
		
		if (empty($cleanPaths)) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_ITEMS_SELECTED'), 'warning');
			return;
		}

		$added   = 0;
		$exists  = 0;
		$deleted = 0;

		foreach ($cleanPaths as $p) {
			// Delegamos en un método endurecido que:
			//  - valida $option con allow-list
			//  - normaliza/valida valores
			//  - trabaja con almacenamiento en JSON
			$result = $this->modifyComponentValue($option, $p, $action);

			if ($action === 'add') {
				if ($result === true) {
					$added++;
				} else {
					$exists++;
				}
			} else { // delete
				if ($result === true) {
					$deleted++;
				}
			}
		}

		// --- Mensajes coherentes con la acción ---
		if ($action === 'add') {
			$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_ADDED_TO_LIST', $added));
			if ($added > 0) {
				$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ELEMENTS_LAUNCH_NEW_SCAN'), 'notice');
			}
			if ($exists > 0) {
				$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_ALREADY_EXISTS', $exists), 'warning');
			}
		} else {
			$app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_DELETED_FROM_LIST', $deleted));
			if ($deleted > 0) {
				$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ELEMENTS_LAUNCH_NEW_SCAN'), 'notice');
			}
		}
	}
	
	/**
	 * Genera una clave aleatoria segura usando sólo [a-zA-Z0-9].
	 * @param int    $length   Longitud de la clave (sin extensión).
	 * @param string $alphabet Conjunto de caracteres permitidos.
	 * @param string $suffix   Sufijo a añadir (por defecto ".php").
	 *
     * @return  string
	 */
	function generateKey(int $length = 20, string $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', string $suffix = '.php')
	{
		$alphabetLength = strlen($alphabet) - 1;
		if ($length < 1 || $alphabetLength < 0) {
			throw new \InvalidArgumentException('Parámetros inválidos.');
		}

		$key = '';
		for ($i = 0; $i < $length; $i++) {
			$key .= $alphabet[random_int(0, $alphabetLength)];
		}

		return $key . $suffix;
	}
	
	/**
     * Borra las tablas #_sessions y #_securitycheckpro_sessions
     *
     * @return  void
     *     
     */
    function purgeSessions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
        
        // Tabla 'sessions'
        $query = 'TRUNCATE TABLE #__session' ;
        $db->setQuery($query);
        $db->execute();    
    
        // Tabla 'securitycheckpro_sessions'
        $query = 'TRUNCATE TABLE #__securitycheckpro_sessions' ;
        $db->setQuery($query);
        $db->execute();
    
        // For Joomla 4 we must also close the current session 
        $user = $app->getIdentity();
        $user_id = $user->id;        
        $app->logout($user_id);
    
    }
	
	/**
	 * Renderiza un <select> genérico.
	 *
	 * - $options puede ser:
	 *    - 'boolean' => genera Sí/No
	 *    - array asociativo [value => label]
	 *    - array de items ya traducidos [ ['value' => 'x', 'text' => 'Label'], ... ]
	 *
	 * @param string                       $name
	 * @param 'boolean'|array              $options
	 * @param array<string,string>         $attribs   Atributos HTML (ej. ['class'=>'form-select','onchange'=>'Disable()'])
	 * @param bool|int|string|null         $selected  Valor seleccionado
	 * @param string|false                 $id        ID o false para omitir
	 * @param bool                         $translate Si true, pasa las etiquetas por Text::_()
	 *
	 * @return string HTML del <select>
	 */
	function renderSelect(
		string $name,
		$options,
		array $attribs = ['class' => 'form-select'],
		bool|int|string|null $selected = null,
		string|false $id = false,
		bool $translate = false
	): string {
		// 1) Normaliza opciones a objetos de option
		$opts = [];

		if ($options === 'boolean') {
			$opts = [
				HTMLHelper::_('select.option', '0', Text::_('COM_SECURITYCHECKPRO_NO')),
				HTMLHelper::_('select.option', '1', Text::_('COM_SECURITYCHECKPRO_YES')),
			];
		} elseif (is_array($options)) {
			// Permite formatos: [ ['value'=>'x','text'=>'Y'], ... ] o ['x'=>'Y', ...]
			$isAssoc = array_keys($options) !== range(0, count($options) - 1);
			if ($isAssoc) {
				foreach ($options as $value => $label) {
					$label = (string) $label;
					$opts[] = HTMLHelper::_('select.option', (string)$value, $translate ? Text::_($label) : $label);
				}
			} else {
				foreach ($options as $item) {
					if (is_array($item) && isset($item['value'], $item['text'])) {
						$label = (string) $item['text'];
						$opts[] = HTMLHelper::_('select.option', (string)$item['value'], $translate ? Text::_($label) : $label);
					} else {
						// Si solo viene un valor suelto, úsalo como value y text
						$opts[] = HTMLHelper::_('select.option', (string)$item, (string)$item);
					}
				}
			}
		} else {
			// Fallback por si llega algo raro
			$opts = [];
		}

		// 2) Construye atributos HTML
		$attrs = '';
		foreach ($attribs as $k => $v) {
			$attrs .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES) . '="' . htmlspecialchars((string)$v, ENT_QUOTES) . '"';
		}
		$attrs = ltrim($attrs) ?: 'class="form-select"';

		// 3) Valor seleccionado normalizado
		$selectedNorm = $selected;
		// Para boolean, fuerza 0/1
		if ($options === 'boolean') {
			$selectedNorm = (int)((bool)$selected);
		}

		return HTMLHelper::_('select.genericlist', $opts, $name, $attrs, 'value', 'text', $selectedNorm, $id);
	}
	
}
