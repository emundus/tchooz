<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

// Chequeamos si el archivo está incluído en Joomla!
defined('_JEXEC') or die();

use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Filesystem\File;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\IpModel;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Application\CMSApplication;

class FirewallconfigModel extends BaseModel
{
	/**
     * @var int 
     */
    var $total = 0;
	
	// Ajusta a tus tablas reales permitidas:
    private const ALLOWED_LISTS = ['whitelist', 'blacklist'];

    // Límite de subida (2 MB)
    private const MAX_UPLOAD_BYTES = 2 * 1024 * 1024;
	
    protected function populateState()
    {
        // Inicializamos las variables
		/** @var \Joomla\CMS\Application\CMSApplicationInterface $app */
        $app = Factory::getApplication();
		
		if ( $app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface ) {
			$search = $app->getUserStateFromRequest('filter.lists_search', 'filter_lists_search');
			$this->setState('filter.lists_search', $search);        
				   
			parent::populateState();
		}
    }
	
	/**
	 * Limitbox genérico para una pestaña (blacklist|dynamic_blacklist|whitelist)
	 * @param array<string, mixed>|null $options 
	 */
	public function getLimitBox(string $contextKey, ?\Joomla\CMS\Pagination\Pagination $pagination = null, ?array $options = null): string
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		$stateKey = "com_securitycheckpro.{$contextKey}";
		$limitKey = "limit_{$contextKey}";

		// Valor actual: Request/UserState -> Pagination->limit -> config
		$current = $app->getUserStateFromRequest("$stateKey.limit", $limitKey, null, 'uint');

		if ($current === null && $pagination) {
			$current = $pagination->limit;
		}
		if ($current === null) {
			$current = (int) $app->get('list_limit');
		}
		$current = (int) $current;

		if ($options === null) {
			$options = [5, 10, 20, 50, 100, 0]; // 0 = All
		}
		if (!in_array($current, $options, true)) {
			$options[] = $current;
		}

		$nameEsc   = htmlspecialchars($limitKey, ENT_QUOTES, 'UTF-8');
		$labelText = \Joomla\CMS\Language\Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');
		$labelId   = 'lbl_' . $nameEsc;

		$html = [];
		$html[] = '<label for="' . $nameEsc . '" id="' . $labelId . '" class="visually-hidden">' . htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8') . '</label>';
		$html[] = '<select name="' . $nameEsc . '" id="' . $nameEsc . '" class="form-select" aria-labelledby="' . $labelId . '"';
		// Reset de start_<contexto> y submit al cambiar
		$html[] = ' onchange="(function(sel){var f=sel.form;if(!f)return;var s=f.elements[\'start_' . htmlspecialchars($contextKey, ENT_QUOTES, 'UTF-8') . '\'];if(s){s.value=0;}f.submit();})(this)">';
		foreach ($options as $opt) {
			$opt = (int) $opt;
			$label = ($opt === 0) ? \Joomla\CMS\Language\Text::_('JALL') : (string) $opt;
			$selected = ($opt === $current) ? ' selected' : '';
			$html[] = '<option value="' . $opt . '"' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
		}
		$html[] = '</select>';

		return implode("\n", $html);
	}


	/**
	 * Listado + paginación con filtro global "filter.lists_search"
	 * @param string   $contextKey      blacklist|dynamic_blacklist|whitelist
	 * @param string   $tableName       base de tabla (sin prefijo)
	 * @param string[] $searchColumns   columnas donde aplicar el LIKE (por defecto ['ip'])
	 * @return array{0:array<int,object>,1:Pagination}
	 */
	public function getListWithPagination(string $contextKey, string $tableName, array $searchColumns = ['ip']): array
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app    = Factory::getApplication();
		$input  = $app->getInput();
		$active = $input->getCmd('active_tab', 'blacklist');

		$limitKey = "limit_{$contextKey}";
		$startKey = "start_{$contextKey}";
		$stateKey = "com_securitycheckpro.{$contextKey}";
		$prefix   = $contextKey . '_'; // para {$prefix}limitstart en los enlaces

		// ---- Inicializaciones para evitar "Undefined variable"
		$rows   = [];
		$total  = 0;
		$search = '';

		// ---- LIMIT
		$defaultLimit = (int) $app->get('list_limit');
		$limit = $input->get($limitKey, null);
		// Solo si viene el limit global en request (limitbox de Joomla), lo usamos
		if ($limit === null && $active === $contextKey && $input->exists('limit')) {
			$limit = $input->getInt('limit', $defaultLimit);
		}
		if ($limit === null) {
			$limit = (int) $app->getUserState($stateKey . '.limit', $defaultLimit);
		}
		$limit = max(0, (int) $limit);

		// ---- START: primero {$prefix}limitstart (propio de la paginación con prefix)
		$start = $input->get($prefix . 'limitstart', null);
		if ($start === null) {
			$start = $input->get($startKey, null); // por si haces submit manual (hidden)
		}
		if ($start === null && $active === $contextKey) {
			$start = $input->getInt('limitstart', $input->getInt('start', null)); // fallback global
		}
		if ($start === null) {
			$start = (int) $app->getUserState($stateKey . '.start', 0);
		}
		$start = ($limit > 0) ? (int) (floor($start / $limit) * $limit) : 0;

		// ---- Filtro
		$search = trim((string) $app->getUserStateFromRequest(
			'com_securitycheckpro.filter.lists_search',
			'filter_lists_search',
			'',
			'string'
		));

		/** @var DatabaseInterface $db */
		$db      = $this->getDatabase();
		$qnTable = $db->quoteName("#__securitycheckpro_{$tableName}");

		// WHERE (OR sobre columnas indicadas)
		$conditions = [];
		if ($search !== '' && !empty($searchColumns)) {
			$like = '%' . $db->escape($search, true) . '%';
			$ors  = [];
			foreach ($searchColumns as $col) {
				$ors[] = $db->quoteName($col) . ' LIKE ' . $db->quote($like, false);
			}
			$conditions[] = '(' . implode(' OR ', $ors) . ')';
		}

		// ---- TOTAL
		$queryTotal = $db->getQuery(true)
			->select('COUNT(*)')
			->from($qnTable);

		if ($conditions) {
			$queryTotal->where(implode(' AND ', $conditions));
		}

		$db->setQuery($queryTotal);
		$total = (int) $db->loadResult();

		if ($limit > 0 && $start >= $total) {
			$start = 0;
		}

		// ---- DATA
		$query = $db->getQuery(true)
			->select('*')
			->from($qnTable);

		if ($conditions) {
			$query->where(implode(' AND ', $conditions));
		}

		$orderCol = !empty($searchColumns) ? $db->quoteName($searchColumns[0]) : $db->quoteName('id');
		$query->order($orderCol . ' ASC');

		if ($limit > 0) {
			$query->setLimit($limit, $start);
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList() ?: [];

		// ---- Persistimos estado por-tab
		$app->setUserState($stateKey . '.limit', $limit);
		$app->setUserState($stateKey . '.start', $start);

		// ---- Paginación con {$prefix}limitstart en los enlaces
		$pagination = new Pagination($total, $start, $limit, $prefix);

		// Conserva pestaña activa y filtro
		$pagination->setAdditionalUrlParam('active_tab', $contextKey);
		if ($search !== '') {
			$pagination->setAdditionalUrlParam('filter_lists_search', $search);
		}
		// Opcional: conserva también el limit namespaced
		$pagination->setAdditionalUrlParam($limitKey, $limit);

		return [$rows, $pagination];
	}

    /**
     * Función que elimina IPs de la lista negra dinámica
     *
     *
     * @return  void
     *     
     */
    function deleteip_dynamic_blacklist()
    {
        // Inicializamos las variables
        $deleted_elements = 0;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
    
        // Creamos el objeto JInput para obtener las variables del formulario
		/** @var \Joomla\CMS\Application\CMSApplication $jinput */
        $jinput = Factory::getApplication();
    
        // Obtenemos los valores de las IPs que serán eliminados de la lista negra dinámica
        $uids = $jinput->getInput()->get('dynamic_blacklist_cid', [], 'array');
		        
        foreach($uids as $uid) {
            // IP sanitizada
            $ip_to_delete = $db->Quote($db->escape($uid));
            // Borramos la IP de la tabla
            $query = "DELETE FROM #__securitycheckpro_dynamic_blacklist WHERE (ip = {$ip_to_delete})";
            $db->setQuery($query);
            $result = $db->execute();
            if ($result) {
                  $deleted_elements++;
            }        
        }
        Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_DELETED_FROM_LIST', $deleted_elements));
    }

   /**
     * Función que chequea si la opción de control center está habilitada en el firewall
     *
     *
     * @return  bool
     *     
     */
    function control_center_enabled()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        try {
            $query = $db->getQuery(true);
            $query 
                ->select($db->quoteName('storage_value'))
                ->from($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key').' = '.$db->quote('controlcenter'));
            $db->setQuery($query);
            $res = $db->loadResult();
        } catch (\Exception $e) {
            return false;    
        }
		        
        if (!empty($res)) {
			try {
				$res = json_decode($res, true, 512, JSON_THROW_ON_ERROR);
				return $res['control_center_enabled'] ?? false;
			} catch (\JsonException $e) {
				return false;
			}
		}
        
		return false;         
        
    }

    /**
     * Función que añade una ip al fichero que será consumido por el control center si el plugin 'Connect' está habilitado
     *
	 * @param   string             $ip    	  The IP to add
	 * @param   string             $option    The option
     *
     * @return  bool|void
     *     
     */
    function añadir_info_control_center($ip,$option) 
    {
        // Ruta al fichero de información
        $folder_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans';
        
        if (@file_exists(($folder_path . DIRECTORY_SEPARATOR . 'cc_info.php'))) {            
            $str=file_get_contents($folder_path . DIRECTORY_SEPARATOR . 'cc_info.php');
            // Eliminamos la parte del fichero que evita su lectura al acceder directamente
            $str = str_replace("#<?php die('Forbidden.'); ?>", '', $str);
            $info_to_add = json_decode($str, true);
            
            if (!$info_to_add) {
                $info_to_add = array(
                 'dynamic_blacklist'    =>    array(),
                 'blacklist'        => array(),        
                 'whitelist'        => array()
                );
                
                array_push($info_to_add[$option], $ip);
                $info_to_add = json_encode($info_to_add);
            } else
            {
                try
                 {
                    array_push($info_to_add[$option], $ip);                    
                    $info_to_add = json_encode($info_to_add);
                } catch (\Exception $e)
                {                
                    return false;    
                }
            }           
                
        } else 
		{
			$info_to_add = array(
                 'dynamic_blacklist'    =>    array(),
                 'blacklist'        => array(),        
                 'whitelist'        => array()
            );
                
            array_push($info_to_add[$option], $ip);
            $info_to_add = json_encode($info_to_add);
		}
		// Sobreescribimos el contenido del fichero
        file_put_contents($folder_path . DIRECTORY_SEPARATOR . 'cc_info.php', "#<?php die('Forbidden.'); ?>" . PHP_EOL . $info_to_add);
		
		// Let's get control center url to send the data
		$control_center_config = $this->getControlCenterConfig();
		$control_center_url = $control_center_config['control_center_url'];	
		
		// Launch 'Connect' task to add the ips to remote managed websites
		$frontend_model = new JsonModel();
		$frontend_model->Connect($control_center_url);		
    }

    /**
     * Función para añadir/borrar una ip a una lista
     *
	 * @param   string           $type    	  The name of the database (blacklist_whitelist...)
	 * @param   string           $action      The action (add,delete)
	 * @param   string|null      $ip    	  The ip
	 * @param   bool             $check_own   Check your own IP
	 * @param   bool             $remote  	  If the call comes from remote
     *
     * @return  string|bool|void
     *     
     */
    function manage_list($type,$action,$ip=null,$check_own=true,$remote=false)
    {

        // Inicializamos las variables
        $query = null;
        $array_size = 0;
        $added_elements = 0;
        $deleted_elements = 0;
        $ip_to_add = null;
        $uids = null;
        $database = "#__securitycheckpro_" . $type;  
		$jinput = null;
		 
        $db = Factory::getContainer()->get(DatabaseInterface::class);
    
        // Podemos pasar la IP como argumento; en ese caso no necesitamos capturar los valores del formulario
        if ( (is_null($ip)) || ($action == 'delete') ) {
            // Creamos el objeto Input para obtener las variables del formulario
			/** @var \Joomla\CMS\Application\CMSApplication $jinput */
            $jinput = Factory::getApplication();
        } 
    
                
        switch ($action) {
        case "add":
            // Obtenemos el valor de la IP introducida
            if ($type == 'blacklist') {
                if (is_null($ip)) {
                    $ip_to_add = $jinput->getInput()->get('blacklist_add_ip', '0.0.0.0', 'string');
                } else {
                    $ip_to_add = $ip;
                }                
            } else if ($type == 'whitelist') {
                if (is_null($ip)) {
                    $ip_to_add = $jinput->getInput()->get('whitelist_add_ip', '0.0.0.0', 'string');
                } else {
                    $ip_to_add = $ip;
                }                
            }
            
            // Chequeamos el formato de la entrada
            //IPv4
            if (strstr($ip_to_add, '*')) { // Si existe algún comodín, lo reemplazamos por el dígito '0'
                $ip_to_add_filtered= str_replace('*', '0', $ip_to_add);
                $ip_valid = filter_var($ip_to_add_filtered, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            } //IPv4/IPv6 CIDR
            else if (strstr($ip_to_add, '/')) { // Formato CIDR
                $ip_without_cidr = strstr($ip_to_add, '/', true);
                $ip_valid = filter_var($ip_without_cidr, FILTER_VALIDATE_IP);                
            }        
            else {
                $ip_valid = filter_var($ip_to_add, FILTER_VALIDATE_IP);                
            }
            
            if (!$ip_valid) {
                if (!$remote) {
                    Factory::getApplication()->enqueueMessage(Text::_("COM_SECURITYCHECKPRO_INVALID_FORMAT"), 'warning');
                    break;
                } else {
                    return Text::_('COM_SECURITYCHECKPRO_INVALID_FORMAT');
                }
            }
            $ipmodel = new IpModel();          
			// Get the client IP to see if the user wants to block his own IP
            $client_ip = $ipmodel->getClientIpForSecuritycheckPro();
                                    
            // Si la IP es la del cliente no la añadimos para no bloquearnos, excepto cuando la petición provenga del url inspector
            if ($check_own) {
                if (($ip_to_add == $client_ip) && ($type == 'blacklist')) {
                    if (is_null($ip)) {
                         Factory::getApplication()->enqueueMessage(Text::_("COM_SECURITYCHECKPRO_CANT_ADD_YOUR_OWN_IP"), 'warning');
                         break;
                    } else {
                        if ($remote) {
                            return Text::_('COM_SECURITYCHECKPRO_CANT_ADD_YOUR_OWN_IP');
                        }
                    }
                    
                }
            }                
                        
            $aparece_lista = $this->ChequearIpEnLista($ip_to_add, $type);
            if (!$aparece_lista) {
                $object = (object)array(
					'ip'        => $ip_to_add
				);
				
				try{
					$db->insertObject($database, $object);
					$added_elements++;
				} catch (\Exception $e)
				{    		
					return false;
				}
                
            }
            
            if ($added_elements > 0) {                
                if (!$remote) {
                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_ADDED_TO_LIST', $added_elements));
                    // Chequeamos si hemos de añadir la ip al fichero que será consumido por el plugin 'connect'
                    $control_center_enabled = $this->control_center_enabled();                
                    if ($control_center_enabled) {						
                         $this->añadir_info_control_center($ip_to_add, $type);
                    }
                } 
            } else {
                if (is_null($ip)) {
                    if (!$remote) {
                         Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_IGNORED', 1), 'notice');
                    }
                }
            }
            break;
        case "delete":
            // Obtenemos los valores de las IPs que serán introducidas en la lista negra
            if ($type == 'blacklist') {
                $uids = $jinput->getInput()->get('cid', '0', 'array');
            } else if ($type == 'whitelist') {
                $uids = $jinput->getInput()->get('whitelist_cid', '0', 'array');
            }
                        
            
            if ($uids != 0) {
                foreach($uids as $uid) {
					$ip_to_delete = $db->Quote($db->escape($uid));
					// Borramos la IP de la tabla
					$query = "DELETE FROM " . $database . " WHERE (ip = {$ip_to_delete})";
					$db->setQuery($query);
					$result = $db->execute();
					if ($result) {
						$deleted_elements++;
					}                            
                }
                if ($deleted_elements > 0) {
                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_DELETED_FROM_LIST', $deleted_elements));					                   
                }
            }
            break;
        }        
    }   

   	/**
     * Función que sube un fichero de IPs de la extensión Securitycheck Pro (previamente exportado) y lo añade a la bbdd
     *
     * @return  bool
     *     
     */
    public function import_list(): bool
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app   = Factory::getApplication();
        $input = $app->getInput();

        // CSRF
        if (!Session::checkToken('request')) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            return false;
        }

        // ACL (ajusta el permiso si usas otro)
        $user = $app->getIdentity();
        if (!$user || !$user->authorise('core.edit', 'com_securitycheckpro')) {
            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            return false;
        }

        // Lista destino
        $lista = (string) $input->getString('import', '');
        if ($lista === '' || !in_array($lista, self::ALLOWED_LISTS, true)) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_LIST'), 'warning');
            return false;
        }
        $table = '#__securitycheckpro_' . $lista;

        // Subidas habilitadas
        if (!(bool) ini_get('file_uploads')) {
            $app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLFILE'), 'warning');
            return false;
        }

        // Campo de archivo dinámico
        $fileField = 'file_to_import_' . $lista;
        $userfile  = $input->files->get($fileField);

        if (!is_array($userfile) || !isset($userfile['error'], $userfile['name'], $userfile['tmp_name'], $userfile['size'])) {
            $app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_NO_FILE_SELECTED'), 'warning');
            return false;
        }

        if ((int) $userfile['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLUPLOADERROR'), 'warning');
            return false;
        }

        if ((int) $userfile['size'] < 1 || (int) $userfile['size'] > self::MAX_UPLOAD_BYTES) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_FILE_SIZE'), 'warning');
            return false;
        }

        // Extensión y nombre seguro
        $ext = strtolower((string) pathinfo((string) $userfile['name'], PATHINFO_EXTENSION));
        if ($ext !== 'txt' && $ext !== 'csv') {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_FILE_EXTENSION'), 'warning');
            return false;
        }
        $safeName = File::makeSafe((string) $userfile['name']) ?: ('ips_' . uniqid('', true) . '.txt');

        // MIME real
        $tmpSrc = $userfile['tmp_name'];
        $mime   = '';
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $mime = (string) finfo_file($f, $tmpSrc);
                finfo_close($f);
            }
        }
        $allowedMimes = ['text/plain', 'text/csv', 'application/octet-stream'];
        if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_MIME_TYPE'), 'warning');
            return false;
        }

        // Destino temporal único
        $tmpPath = rtrim((string) $app->getConfig()->get('tmp_path'), '/\\');
        $tmpDest = $tmpPath . DIRECTORY_SEPARATOR . uniqid('ips_', true) . '_' . $safeName;

        if (!File::upload($tmpSrc, $tmpDest, false)) {
            $app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_WARNINSTALLUPLOADERROR'), 'warning');
            return false;
        }

        /** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            // Leer contenido
            $raw = file_get_contents($tmpDest);
            if ($raw === false) {
                throw new \RuntimeException('Unable to read uploaded file.');
            }

            // Quitar BOM
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;

            // Normalizar separadores y espacios
            $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
            $normalized = str_replace([';', "\t"], ',', $normalized);
            $normalized = preg_replace('/\s+/', ',', $normalized); // espacios -> coma

            // Partir, limpiar y deduplicar
            $candidates = array_filter(array_map(
                static function ($s) {
                    $s = trim((string) $s);
                    return trim($s, "\"'"); // fuera comillas accidentales
                },
                explode(',', (string) $normalized)
            ), static fn($s) => $s !== '');

            $candidates = array_values(array_unique($candidates));

            if (!$candidates) {
                $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_FILE_FORMAT'), 'warning');
                return false;
            }

            // Validar IPv4/IPv6
            $validIps = [];
            foreach ($candidates as $ip) {
                $v = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
                if ($v !== false) {
                    $validIps[] = $v;
                }
            }
            $validIps = array_values(array_unique($validIps));

            if (!$validIps) {
                $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_FILE_FORMAT'), 'warning');
                return false;
            }

            // Transacción + inserciones por lotes
            $db->transactionStart();

            $chunkSize = 500;
            $inserted  = 0;

            for ($i = 0, $n = count($validIps); $i < $n; $i += $chunkSize) {
                $chunk = array_slice($validIps, $i, $chunkSize);

                // Intento de inserción por lote (portátil). Si hay conflictos por PK, degradamos a per-row.
                $query = $db->getQuery(true)
                    ->insert($db->quoteName($table))
                    ->columns([$db->quoteName('ip')]);

                foreach ($chunk as $ip) {
                    $query->values($db->quote($ip));
                }

                try {
                    $db->setQuery($query)->execute();
                    $inserted += count($chunk);
                } catch (\Throwable $e) {
                    // Degradar a inserción individual; los duplicados se omiten (PK)
                    foreach ($chunk as $ip) {
                        $q = $db->getQuery(true)
                            ->insert($db->quoteName($table))
                            ->columns([$db->quoteName('ip')])
                            ->values($db->quote($ip));
                        try {
                            $db->setQuery($q)->execute();
                            $inserted++;
                        } catch (\Throwable $inner) {
                            // Duplicado u otro problema: lo registramos y seguimos
                            Log::add(
                                'IP import skipped (' . $table . '): ' . $ip . ' | ' . $inner->getMessage(),
                                Log::INFO,
                                'com_securitycheckpro'
                            );
                        }
                    }
                }
            }

            $db->transactionCommit();

            $app->enqueueMessage(
                Text::sprintf('COM_SECURITYCHECKPRO_IMPORT_SUCCESSFULLY_COUNT', (int) $inserted),
                'message'
            );
            return true;

        } catch (\Throwable $e) {
            $db->transactionRollback();            
            Log::add('IP import error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_IMPORT_FAILED_GENERIC'), 'error');
            return false;
        } finally {
            // Limpieza del archivo temporal
            try {
                if (is_file($tmpDest)) {
                    File::delete($tmpDest);
                }
            } catch (\Throwable $e) {
                Log::add('Temp cleanup failed: ' . $e->getMessage(), Log::WARNING, 'com_securitycheckpro');
            }
        }
    }
	
	/**
     * Función que manda un email de prueba utilizando los parámetros establecidos
     *
     *
     * @return  void
     *     
     */
    function send_email_test()
    {
        // Obtenemos las variables del formulario...
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app  = Factory::getApplication();
		$data = $app->getInput()->post->getArray();
    
        //... y las filtramos
        $subject = htmlspecialchars($data['email_subject']);
        $body = Text::_('COM_SECURITYCHECKPRO_EMAIL_TEST_BODY');
    
        $email_to = $data['email_to'];
        $to = explode(',', $email_to);
    
        $email_from_domain = filter_var($data['email_from_domain'], FILTER_SANITIZE_EMAIL);
        $email_from_name = htmlspecialchars($data['email_from_name']);
        $from = array($email_from_domain,$email_from_name);

        $send = true;
    
        try {
            // Instanciamos la clase para mandar emails
            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
            // Emisor
            $mailer->setSender($from);
            // Destinatario -- es una array de direcciones
            $mailer->addRecipient($to);
            // Asunto
            $mailer->setSubject($subject);
            // Cuerpo
            $mailer->setBody($body);
            // Opciones del correo
            $mailer->isHtml(true);
            $mailer->Encoding = 'base64';
            // Enviamos el mensaje
            $send = $mailer->Send();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'notice');
            $send = false;
        }
                    
        // Añadimos un mensaje de que todo ha funcionado correctamente
        if ($send === true) {
            Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_EMAIL_SENT_SUCCESSFULLY', $email_to));
        }
    }

    /**
     * Función que chequea si el plugin pasado como argumento está instalado
     *
	 * @param   string             $folder  	  The path to the folder
	 * @param   string             $plugin_name   The name of the plugin
     *
     * @return  bool
     *     
     */
    public function is_plugin_installed($folder,$plugin_name)
    {
        // Inicializamos las variables
        $installed= false;
    
        $plugin = PluginHelper::getPlugin($folder, $plugin_name);
    
        // Si el valor devuelto es un array, entonces el plugin no existe o no está habilitado
        if (!is_array($plugin)) {
            $installed = true;        
        }
    
        return $installed;
    }

}
