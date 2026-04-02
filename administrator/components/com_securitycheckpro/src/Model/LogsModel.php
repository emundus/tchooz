<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Session\Session;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\IpModel;

class LogsModel extends ListModel
{

	/**
     * @var array<string, int> Default config
     */
    private $defaultConfig = [
		'logs_attacks'=> 1,    
    ];
	
	/**
     Configuración aplicada
     *
     @var \Joomla\Registry\Registry
     */
    private $config = null;
	
	/**
     * @var string Database type
     */
	private $dbtype = "mysql";
	
	protected $context = 'com_securitycheckpro.logs';

	/**
     * Constructor
     *
     * @param   array<string>                $config   An array of configuration options (name, state, dbo, table_path, ignore_request).     
     *
     * @since   3.0
     * @throws  \Exception
     */
    public function __construct($config = array())
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app     = Factory::getApplication();
        $scp_config   = $app->getConfig();
		$this->dbtype = $scp_config->get('dbtype');

		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'search',
				'ip', 'a.ip',
				'time', 'a.time',
				'description', 'a.description',
				'component', 'a.component',
				'type', 'a.type',
				'marked', 'a.marked',
			];
		}
		parent::__construct($config);
    
    }

    protected function populateState($ordering = 'a.time', $direction = 'DESC')
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		$input = $app->getInput();

		if ($app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface) {
			$search      = $app->getUserStateFromRequest($this->context . '.filter.search',      'filter_search',      '', 'string');
			$description = $app->getUserStateFromRequest($this->context . '.filter.description', 'filter_description', '', 'string');
			$type        = $app->getUserStateFromRequest($this->context . '.filter.type',        'filter_type',        '', 'string');
			$leido       = $app->getUserStateFromRequest($this->context . '.filter.leido',       'filter_leido',       '', 'string');

			$this->setState('filter.search', $search);
			$this->setState('filter.description', $description);
			$this->setState('filter.type', $type);
			$this->setState('filter.leido', $leido);

			// datefrom
			$dateFrom = $app->getUserStateFromRequest($this->context . '.filter.datefrom', 'filter[datefrom]', '', 'string');
			if ($dateFrom === '') {
				$urlDateFrom = $app->getInput()->get('datefrom', '');
				if ($urlDateFrom !== '') {
					$dateFrom = $urlDateFrom;
					$app->setUserState($this->context . '.filter.datefrom', $dateFrom);
					$this->setState('list.start', 0);
				}
			}
			$this->setState('filter.datefrom', $dateFrom);

			// dateto (ojo al bug de variable)
			$dateTo = $app->getUserStateFromRequest($this->context . '.filter.dateto', 'filter[dateto]', '', 'string');
			if ($dateTo === '') {
				$urlDateTo = $app->getInput()->get('dateto', '');
				if ($urlDateTo !== '') {
					$dateTo = $urlDateTo; // <- antes estabas tocando $dateFrom por error
					$app->setUserState($this->context . '.filter.dateto', $dateTo);
					$this->setState('list.start', 0);
				}
			}
			$this->setState('filter.dateto', $dateTo);
		}

		// Defaults: más recientes primero
		parent::populateState($ordering, $direction);
		
		// Si en ESTA request NO viene 'filter_order_Dir', imponemos el default DESC
		$dirFromRequest = $input->getCmd('filter_order_Dir', ''); // '' = no vino en la request
		
		if ($dirFromRequest === '') {
			$this->setState('list.direction', 'DESC');
			// Asegura también la columna por defecto si no vino en la request
			$orderFromRequest = $input->getCmd('filter_order', '');
			if ($orderFromRequest === '') {
				$this->setState('list.ordering', 'a.time');
			}
			// Opcional: resetea la paginación al cambiar orden por defecto
			$this->setState('list.start', 0);
			// Persiste en user-state para siguientes vistas
			$app->setUserState($this->context . '.list.direction', 'DESC');
			$app->setUserState($this->context . '.list.ordering', 'a.time');
		}
    }
	
	/** @return array<string, mixed> */
    protected function loadFormData(): array
    {
        // Esto ya carga ['filter' => ...] desde el user state si existe
        $data = parent::loadFormData();
		
		// Normaliza a array
        if ($data instanceof \stdClass) {
            $data = ArrayHelper::fromObject($data, true); // true = recursivo
        } elseif (!is_array($data)) {
            $data = [];
        }
        
		$data['filter'] = isset($data['filter']) && is_array($data['filter']) ? $data['filter'] : [];
		
        $app = Factory::getApplication();
        $urlDateFrom = (string) $app->getInput()->get('datefrom','');
		$urlDateTo = (string) $app->getInput()->get('dateto','');

        // Sólo lo inyectamos si el filtro aún no tiene valor
        if ($urlDateFrom !== '' && (($data['filter']['datefrom'] ?? '') === '')) {
            $data['filter']['datefrom'] = $urlDateFrom;
        }
		
		// Sólo lo inyectamos si el filtro aún no tiene valor
        if ($urlDateTo !== '' && (($data['filter']['dateto'] ?? '') === '')) {
            $data['filter']['dateto'] = $urlDateTo;
        }

        // Refleja en el state plano para uso en getListQuery()
        $this->setState('filter.datefrom', (string) ($data['filter']['datefrom'] ?? ''));
		$this->setState('filter.dateto', (string) ($data['filter']['dateto'] ?? ''));

        return $data;
    }
	
	/**
     * Overwrite the parent method
     *
	 * @param   string             $id    The id of the state
     *
     * @return  string 
     *     
     */
	protected function getStoreId($id = '') {
        $norm = static function (string $d): string {
			$d = trim($d);
			if ($d === '') return '';
			try {
				return (new \DateTime($d))->format('Y-m-d');
			} catch (\Throwable) {
				return $d; // si no parsea, usa tal cual
			}
		};

		$id .= ':' . $norm((string) $this->getState('filter.datefrom', ''));
		$id .= ':' . $norm((string) $this->getState('filter.dateto', ''));

		return parent::getStoreId($id);
    }

	/**
     * Overwrite the parent method
     *
     *
     * @return  string 
     *     
     */
    public function getListQuery()
    {
        
        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        
        $app        = Factory::getApplication();
        $search = $this->getState('filter.search');

		$query->select('a.*');
        $query->from('#__securitycheckpro_logs AS a');		
    
        // Sanitizamos la entrada
        if (!empty($search)) {
            $search = $db->Quote('%' . $db->escape($search, true) . '%');
			
			if (strstr($this->dbtype,"mysql")) {
				$query->where('(a.ip LIKE '.$search.' OR a.time LIKE '.$search.' OR a.username LIKE '.$search.' OR a.description LIKE '.$search.' OR a.uri LIKE '.$search.' OR a.geolocation LIKE '.$search.')');
			} else if (strstr($this->dbtype,"pgsql")) {
				$query->where('(a.ip LIKE '.$search.' OR CAST(a.time as TEXT) LIKE '.$search.' OR a.username LIKE '.$search.' OR a.description LIKE '.$search.' OR a.uri LIKE '.$search.' OR a.geolocation LIKE '.$search.')');
			}
        }		
            
        // Filtramos la descripcion
        if ($description = $this->getState('filter.description')) {
            $query->where('a.tag_description = '.$db->quote($description));
        }
		    
        // Filtramos el tipo
        if ($log_type = $this->getState('filter.type')) {
            $query->where('a.type = '.$db->quote($log_type));
        }
        
        // Filtramos leido/no leido
        $leido = $this->getState('filter.leido');
		
		if (empty($leido)) {
			$leido = 0;
		}
		
        if (is_numeric($leido)) {
			if (strstr($this->dbtype,"mysql")) {
				 $query->where('a.marked = '.(int) $leido);
			} else if (strstr($this->dbtype,"pgsql")) {
				 $query->where("CAST(a.marked as TEXT) = '".(int) $leido . "'");
			}
           
        }       
    
        // Filtramos el rango de fechas       
        $fltDateFrom = $this->getState('filter.datefrom', null);		
				    
        if (!empty($fltDateFrom)) {
            $is_valid = $this->checkIsAValidDate($fltDateFrom);
			if ($is_valid) {
				$date = new Date($fltDateFrom);
				if (strstr($this->dbtype,"mysql")) {					
					$query->where($db->quoteName('time').' >= '.$db->Quote($date->toSql()));
				} else if (strstr($this->dbtype,"pgsql")) {
					$query->where($db->quoteName('time').' >= '.$db->Quote($date));
				}
            } else 
            {
                if ($fltDateFrom != "0000-00-00 00:00:00") {
                    Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_DATE_NOT_VALID'), 'notice');
                }            
            }    
        }
		
		$fltDateTo = $this->getState('filter.dateto', null);	
		
        if (!empty($fltDateTo)) {
            $is_valid = $this->checkIsAValidDate($fltDateTo);			
            if ($is_valid) {
				$date = new Date($fltDateTo);
				if (strstr($this->dbtype,"mysql")) {
					$query->where($db->quoteName('time').' <= '.$db->Quote($date->toSql()));
				} else if (strstr($this->dbtype,"pgsql")) {
					$query->where($db->quoteName('time').' <= '.$db->Quote($date));
				}
                
            } else 
            {
                if ($fltDateTo != "0000-00-00 00:00:00") {
                    Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_DATE_NOT_VALID'), 'notice');
                }
            }    
        }
    
        $ordering  = $this->state->get('list.ordering', 'a.time');
		$direction = strtoupper($this->state->get('list.direction', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
		

		// Por seguridad, si viene 'time' sin alias, ponle 'a.time'
		if ($ordering === 'time') {
			$ordering = 'a.time';
		}

		$query->order($db->quoteName($ordering) . ' ' . $direction);		
					
        return $query;
    }
        
	/**
     * Chequea si un string es una fecha válida
     *
	 * @param   string             $myDateString    The date to check
     *
     * @return  bool 
     *     
     */
    function checkIsAValidDate($myDateString)
    {
        return (bool)strtotime($myDateString);
    }

    /**
     * Cambia el estado de marcado (leído/no leído) de los logs indicados.
     *
     * - Si $uids es null, leerá los IDs de la request: input['cid'] (array).
     * - Sanitiza y normaliza los IDs, evitando valores no numéricos y duplicados.
     * - Usa UPDATE con WHERE IN y parámetros ligados (sin concatenaciones).
     *
     * @param  bool                $setRead true => marcado=1 (leído), false => marcado=0 (no leído)
     * @param  array<int|string>|null $uids   IDs de logs a actualizar; si null, usa input['cid']
     * @return void
     */
    public function markLogs(bool $setRead, ?array $uids = null): void
    {
        // Obtiene IDs desde la request si no se proporcionan
        if ($uids === null) {
			/** @var \Joomla\CMS\Application\CMSApplication $app */
            $app  = Factory::getApplication();
            /** @var array<int|string> $raw */
            $raw  = (array) $app->getInput()->get('cid', [], 'array');
            $uids = $raw;
        }

        // Normaliza IDs a enteros positivos y quita duplicados
        // Convierte in-place y vuelve a mapear a int por si el helper no actuó por referencia
		ArrayHelper::toInteger($uids);
		$uids = array_map(static fn ($v): int => (int) $v, (array) $uids);

		// Filtra > 0 sin type-hint estricto en el closure (evita TypeError si llega algo raro)
		$ids = array_values(array_unique(array_filter(
			$uids,
			static fn ($v): bool => (int) $v > 0
		)));

        if ($ids === []) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_LOG_SELECTED'), 'warning');
            return;
        }

        /** @var DatabaseInterface $db */
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // 1) Prepara placeholders y valores estables
        $placeholders = [];
        /** @var array<int,int> $paramIds */
        $paramIds = []; // <- mantiene las referencias vivas hasta execute()

        foreach ($ids as $i => $id) {
            $placeholders[] = ':id' . $i;
            $paramIds[$i]   = (int) $id; // valor estable
        }

        $marked = $setRead ? 1 : 0; // variable (no expresión)

        // 2) Construye la query
        $query
            ->update($db->quoteName('#__securitycheckpro_logs'))
            ->set($db->quoteName('marked') . ' = :marked')
            ->where(
                $db->quoteName('id') . ' IN (' . implode(',', $placeholders) . ')'
            );

        // 3) Bind *por referencia* a variables/elementos estables
        $query->bind(':marked', $marked, ParameterType::INTEGER);

        foreach ($paramIds as $i => $val) {
            // ¡OJO! Hay que ligar el elemento del array, no (int)$id ni $ids[$i]
            $query->bind(':id' . $i, $paramIds[$i], ParameterType::INTEGER);
        }

        try {
            $db->setQuery($query);
            $db->execute();
        } catch (\Throwable $e) {
            // Mensaje genérico para el usuario
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_UPDATING_LOGS'), 'error');
			Log::add('LogsModel. markLogs function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
            
        }
    }

	/**
	 * Delete selected log records safely.
	 *
	 * - Validates CSRF token
	 * - Checks user permission (core.delete)
	 * - Normalizes and deduplicates IDs
	 * - Uses Query Builder (no SQL string concatenation)
	 * - Handles exceptions and reports precise messages
	 */
	function delete(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app  = Factory::getApplication(); 
		$user = $app->getIdentity();

		// 1) CSRF protection
		if (!Session::checkToken('request')) {
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			return;
		}

		// 2) Authorization
		if (!$user || !$user->authorise('core.delete', 'com_securitycheckpro')) {
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			return;
		}

		// 3) Read and sanitize IDs from request
		/** @var array<int|string> $rawIds */
		$rawIds = (array) $app->getInput()->get('cid', [], 'array');

		// toInteger() returns array<int>, then we filter > 0 and unique
		/** @var array<int> $ids */
		$ids = array_values(
			array_unique(
				array_filter(ArrayHelper::toInteger($rawIds), static fn (int $v): bool => $v > 0)
			)
		);

		if ($ids === []) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_LOG_SELECTED'), 'warning');
			return;
		}

		// 4) Delete in a single statement using the Query Builder
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_logs'))
				->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

			$db->setQuery($query);
			$db->execute();

			$count = count($ids);
			$app->enqueueMessage(
				$count === 1
					? Text::_('COM_SECURITYCHECKPRO_N_LOGS_DELETED_1')
					: Text::sprintf('COM_SECURITYCHECKPRO_N_LOGS_DELETED_MORE', $count),
				'message'
			);
		} catch (\Throwable $e) {
			Log::add('LogsModel. delete function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ERROR_DELETING_LOGS'), 'error');
		}
	}

    public function add_to_blacklist(): void
    {
        // Evita bloquear la IP del cliente en blacklist
        $this->addIpsToList('blacklist', true);
    }

    public function add_to_whitelist(): void
    {
        // En whitelist no aplicamos la restricción de “propia IP”
        $this->addIpsToList('whitelist', false);
    }

    /**
     * Núcleo común para añadir IPs a blacklist/whitelist desde los logs seleccionados.
     *
     * @param 'blacklist'|'whitelist' $listType
     * @param bool                    $preventSelfBlock  Evita añadir la IP del cliente (sólo blacklist)
     */
    private function addIpsToList(string $listType, bool $preventSelfBlock): void
    {
        $app = Factory::getApplication();
        /** @var DatabaseInterface $db */
        $db  = Factory::getContainer()->get(DatabaseInterface::class);

        // Valida tipo de lista
        if (!\in_array($listType, ['blacklist', 'whitelist'], true)) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_LIST_TYPE'), 'error');
            return;
        }

        // 1) Recoge y sanea IDs de logs
        $ids = $app->getInput()->get('cid', [], 'array');
        $ids = \is_array($ids) ? $ids : [];
        $ids = array_values(
            array_unique(
                array_filter(
                    array_map(static fn($v): int => (int) $v, $ids),
                    static fn(int $v): bool => $v > 0
                )
            )
        );

        if ($ids === []) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_LOG_SELECTED'), 'warning');
            return;
        }

        // 2) Carga IPs desde logs
        $q = $db->getQuery(true)
            ->select($db->quoteName(['id', 'ip']))
            ->from($db->quoteName('#__securitycheckpro_logs'))
            ->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        $db->setQuery($q);
        /** @var array<int,array{id:int,ip:string|null}> $rows */
        $rows = (array) $db->loadAssocList();

        if ($rows === []) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_LOG_SELECTED'), 'warning');
            return;
        }

        // Normalizador/validador de IP
        $normalizeIp = static function (?string $ip): string {
            $ip = trim((string) $ip);
            if ($ip === '') {
                return '';
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return strtolower($ip);
            }
            return '';
        };

        // 3) IPs candidatas (normalizadas, únicas)
        /** @var array<string,bool> $candidateIps */
        $candidateIps = [];
        foreach ($rows as $row) {
            $n = $normalizeIp($row['ip'] ?? '');
            if ($n !== '') {
                $candidateIps[$n] = true;
            }
        }

        if ($candidateIps === []) {
            $this->markLogs(true, $ids);
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_ELEMENTS_IGNORED'), 'notice');
            return;
        }

        // 4) Evita bloquear IP propia si aplica (sólo blacklist)
        $selfIpRemoved = false;
        if ($preventSelfBlock) {
            try {
                $clientIpModel  = new IpModel(); // cambia a DI si prefieres
                $clientIp       = (string) $clientIpModel->getClientIpForSecuritycheckPro();
                $clientIpNorm   = $normalizeIp($clientIp);
                if ($clientIpNorm !== '' && isset($candidateIps[$clientIpNorm])) {
                    unset($candidateIps[$clientIpNorm]);
                    $selfIpRemoved = true;
                    $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CANT_ADD_YOUR_OWN_IP'), 'warning');
                }
            } catch (\Throwable $e) {
                // si falla la detección de IP cliente, no impedimos el flujo
            }
        }

        if ($candidateIps === []) {
            $this->markLogs(true, $ids);
            return;
        }

        // 5) Filtra duplicados ya presentes en la lista
        $table = $listType === 'blacklist' ? '#__securitycheckpro_blacklist' : '#__securitycheckpro_whitelist';
        $quoted = array_map([$db, 'quote'], array_keys($candidateIps));

        $q = $db->getQuery(true)
            ->select($db->quoteName('ip'))
            ->from($db->quoteName($table))
            ->where($db->quoteName('ip') . ' IN (' . implode(',', $quoted) . ')');

        $db->setQuery($q);
        /** @var array<int,string> $already */
        $already = (array) $db->loadColumn();
        foreach ($already as $ip) {
            $n = $normalizeIp($ip);
            if ($n !== '') {
                unset($candidateIps[$n]);
            }
        }

        // 6) Inserta bajo transacción
        $added = 0;
        if ($candidateIps !== []) {
            $db->transactionStart();
            try {
                foreach (array_keys($candidateIps) as $ip) {
                    $record     = new \stdClass();
                    $record->ip = $ip;
                    $db->insertObject($table, $record);
                    $added++;
                }
                $db->transactionCommit();
            } catch (\Throwable $e) {
                $db->transactionRollback();
                $app->enqueueMessage(
                    Text::sprintf('COM_SECURITYCHECKPRO_DB_ERROR', $e->getMessage()),
                    'error'
                );
            }
        }

        // 7) Mensajes
        $originalCount = \count($rows);
        $notAdded = max(0, $originalCount - $added - ($selfIpRemoved ? 1 : 0));
        if ($added > 0) {
            $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_ADDED_TO_LIST', $added));
        }
        if ($notAdded > 0) {
            $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_IGNORED', $notAdded), 'notice');
        }

        // 8) Marca los logs como leídos usando tu nuevo método
        $this->markLogs(true, $ids);
    }

   	/**
     * Obtiene el valor de una opción de configuración
     *
     *
	 * @param   string             $key    The key of the element
	 * @param   string             $default    The default value
	 * @param   string             $key_name    The name of the key to load
	 * 	 
     * @return  array<string>
     *     
     */
    public function getValue($key, $default = null, $key_name = 'cparams')
    {
       $this->load($key_name);       
    
       return $this->config->get($key, $default);
        
    }

    /**
     * Hace una consulta a la tabla especificada como parámetro
     *
     * @param   string             $key_name    The name of the key
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
     * Obtiene la configuración de los parámetros de la opción 'Mode'
     *
     *
     * @return  array<string, array<string>>
     *     
     */
    function getConfig()
    {
        $model = new BaseModel();
		
        $config = [];
        foreach($this->defaultConfig as $k => $v)
        {
            $config[$k] = $model->getValue($k, $v, 'pro_plugin');
        }
        return $config;
    }

	/**
	 * Vacía toda la tabla de logs (backend).
	 * Requiere permiso fuerte específico.
	 */
	function delete_all(): void
	{
		if (!Session::checkToken('request')) {
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		if (!$user->authorise('logs.deleteall', 'com_securitycheckpro')) {
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_logs'));
			$db->setQuery($query);
			$db->execute();

			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_TABLE_CLEARED'), 'message');
		} catch (\Throwable $e) {
			throw new \RuntimeException(Text::_('COM_SECURITYCHECKPRO_TRUNCATE_FAILED') . ': ' . $e->getMessage(), 500, $e);
		}
	}    
	
	/**
     * Función para guardar en la tabla securitycheck_storage la configuración pasada como argumento. Se usa para añadir un componente como excepción desde los logs
     *
	 * @param   array<string,mixed>      $data    The data to store
     *
     * @return  void
     *     
     */
	function save_config($data) {
		
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		$query2 = $db->getQuery(true);
		
		$data = json_encode($data);
		$key_name = 'pro_plugin';
		
		if ($data !== false) {
			//Json is valid			
			
			//Get the previous value
			$query2->select('storage_value');
            $query2->from('#__securitycheckpro_storage');
            $query2->where($db->quoteName('storage_key').' = '.$db->quote($key_name));
			$db->setQuery($query2);
			$db->execute();
			$previous_data = $db->loadResult();
						
			try {
				//delete stored value
				$query
					->delete($db->quoteName('#__securitycheckpro_storage'))
					->where($db->quoteName('storage_key').' = '.$db->quote($key_name));
				$db->setQuery($query);
				$db->execute();
				
				$object = (object)array(
				'storage_key'        => $key_name,
				'storage_value'        => $data
				);
					
				$db->insertObject('#__securitycheckpro_storage', $object);
			
				Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CONFIGSAVED'));
			} catch (\Exception $e)
			{    
				// Let's restore the previous config
				$object = (object)array(
				'storage_key'        => $key_name,
				'storage_value'        => $previous_data
				);
					
				$db->insertObject('#__securitycheckpro_storage', $object);
			} 
		} else {
			Factory::getApplication()->enqueueMessage("Error", 'error');
		}
	}
	
	/**
	 * Añade de forma segura un elemento a una lista CSV.
	 *
	 * Reglas:
	 *  - No guarda HTML escapado (escapa sólo al mostrar).
	 *  - Normaliza espacios y elimina controles.
	 *  - Rechaza elementos que contengan comas.
	 *  - Evita duplicados (comparación case-insensitive).
	 *
	 * @param string $csv         Lista actual separada por comas (p.ej. "A,B,C")
	 * @param string $newElement  Elemento a añadir (sin coma)
	 *
	 * @return string             Nueva lista CSV normalizada
	 *
	 * @throws \InvalidArgumentException Si $newElement contiene coma
	 */
	function addElement(string $csv, string $newElement): string
	{
		// Limpieza básica del nuevo elemento
		$cleanNew = trim($newElement);
		// Elimina caracteres de control (incluyendo \r \n \t, etc.)
		$cleanNew = preg_replace('/\p{C}+/u', '', $cleanNew) ?? '';

		// Normaliza espacios internos a uno solo
		$cleanNew = preg_replace('/\s+/u', ' ', $cleanNew) ?? '';

		if ($cleanNew === '') {
			// No añadimos vacíos; devolvemos tal cual normalizado
			return $this->normalizeCsv($csv);
		}

		// Rechaza comas para no romper el CSV ni permitir inyecciones
		if (str_contains($cleanNew, ',')) {
			throw new \InvalidArgumentException('The element must not contain commas.');
		}

		// Descompone CSV original en elementos limpios
		$items = $this->parseCsv($csv);

		// Evita duplicados (case-insensitive) manteniendo el primer casing visto
		$seen = [];
		foreach ($items as $item) {
			$key = mb_strtolower($item, 'UTF-8');
			$seen[$key] = true;
		}

		$newKey = mb_strtolower($cleanNew, 'UTF-8');
		if (!isset($seen[$newKey])) {
			$items[] = $cleanNew;
		}

		// Reconstruye CSV sin espacios alrededor de comas
		return implode(',', $items);
	}

	/**
	 * Convierte un CSV en array normalizado.
	 *
	 * @param string $csv
	 * @return list<string>
	 */
	function parseCsv(string $csv): array
	{
		if ($csv === '') {
			return [];
		}

		// Divide por coma y recorta espacios alrededor de cada valor
		$raw = preg_split('/\s*,\s*/u', $csv, -1, PREG_SPLIT_NO_EMPTY) ?: [];

		$out = [];
		$seen = [];
		foreach ($raw as $val) {
			$v = trim($val);
			$v = preg_replace('/\p{C}+/u', '', $v) ?? '';
			$v = preg_replace('/\s+/u', ' ', $v) ?? '';
			if ($v === '' || str_contains($v, ',')) {
				// Descarta vacíos y entradas corruptas con coma
				continue;
			}
			$key = mb_strtolower($v, 'UTF-8');
			if (!isset($seen[$key])) {
				$seen[$key] = true;
				$out[] = $v;
			}
		}

		return $out;
	}

	/**
	 * Normaliza un CSV (útil para entradas existentes).
	 *
	 * @param string $csv
	 * @return string
	 */
	function normalizeCsv(string $csv): string
	{
		return implode(',', $this->parseCsv($csv));
	}
	
	/**
     * Añade excepciones a la configuración a partir de logs seleccionados.
     *
     * Seguridad:
     *  - Tipado estricto y retorno void
     *  - Consultas preparadas con parámetros tipados
     *  - Allow-list de tags -> clave de parámetro
     *  - Coincidencia exacta por elemento (sin 'stristr')
     *  - Saneado/validación del valor 'component'
     *
     * @return void
     */
    public function add_exception(): void
    {
        $app = Factory::getApplication();

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // --- IDs seleccionados (cid) ---
        $uids = (array) $app->getInput()->get('cid', [], 'array');
        ArrayHelper::toInteger($uids);
        $uids = array_values(array_filter($uids, static fn (int $v): bool => $v > 0));

        if ($uids === []) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_LOG_SELECTED'), 'warning');
            return;
        }

        // --- Carga de parámetros del firewall (storage_key = 'pro_plugin') ---
        $query = $db->getQuery(true)
            ->select($db->quoteName('storage_value'))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key') . ' = :key');

        $storageKey = 'pro_plugin'; // <-- VARIABLE (no literal) para bind por referencia
        $query->bind(':key', $storageKey, ParameterType::STRING);

        $db->setQuery($query);

        /** @var string|null $rawParams */
        $rawParams = $db->loadResult();

        /** @var array<string,mixed> $params */
        $params = [];
        if (is_string($rawParams) && $rawParams !== '') {
            try {
                $decoded = json_decode($rawParams, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $params = $decoded;
                }
            } catch (\Throwable) {
                // Si hay JSON corrupto, continúa con params vacíos para no romper UX
                $params = [];
            }
        }

        // Allow-list: tag_description => clave de configuración
        $tagToParam = [
            'TAGS_STRIPPED'         => 'strip_tags_exceptions',
            'DUPLICATE_BACKSLASHES' => 'duplicate_backslashes_exceptions',
            'LINE_COMMENTS'         => 'line_comments_exceptions',
            'SQL_PATTERN'           => 'sql_pattern_exceptions',
            'IF_STATEMENT'          => 'if_statement_exceptions',
            'INTEGERS'              => 'using_integers_exceptions',
            'BACKSLASHES_ADDED'     => 'escape_strings_exceptions',
            'LFI'                   => 'lfi_exceptions',
            'FORBIDDEN_WORDS'       => 'second_level_exceptions',
        ];

        $somethingAdded = false;

        // --- Procesa cada log seleccionado ---
        foreach ($uids as $uid) {
            $q = $db->getQuery(true)
                ->select([
                    $db->quoteName('component'),
                    $db->quoteName('type'),
                    $db->quoteName('tag_description'),
                ])
                ->from($db->quoteName('#__securitycheckpro_logs'))
                ->where($db->quoteName('id') . ' = :id');

            $logId = (int) $uid; 
            $q->bind(':id', $logId, ParameterType::INTEGER);

            $db->setQuery($q);
            /** @var object|null $row */
            $row = $db->loadObject();

            if ($row === null) {
                // Log inexistente
                continue;
            }

            $tag = is_string($row->tag_description) ? strtoupper(trim($row->tag_description)) : '';
            if ($tag === '' || !array_key_exists($tag, $tagToParam)) {
                $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_UNSUPPORTED_TAG', $tag), 'warning');
                continue;
            }

            $paramKey  = $tagToParam[$tag];
            $component = $this->sanitizeComponent((string) ($row->component ?? ''));

            if ($component === '') {
                $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_COMPONENT_VALUE'), 'warning');
                continue;
            }

            // Asegura que el índice existe como CSV (compatibilidad)
            $currentCsv = (string) ($params[$paramKey] ?? '');

            // Añade si no existe (comparación exacta tras normalizar)
            [$newCsv, $added] = $this->addToCsvListUnique($currentCsv, $component);

            if ($added) {
                $params[$paramKey] = $newCsv;
                $somethingAdded    = true;
            } else {
                $type = (string) ($row->type ?? '');
                $app->enqueueMessage(
                    Text::sprintf('COM_SECURITYCHECKPRO_EXCEPTION_ALREADY_EXISTS', $component, $type),
                    'notice'
                );
            }
        }

        // --- Guarda configuración sólo si hubo cambios ---
        if ($somethingAdded) {
            try {
                $this->save_config($params);                
            } catch (\Throwable) {
                // No exponemos detalles internos
                throw new \RuntimeException(Text::_('COM_SECURITYCHECKPRO_CONFIG_SAVE_FAILED'));
            }
        }
    }

    /**
     * Normaliza y valida el identificador de componente.
     * Acepta patrones tipo "com_xxx", "plugin:group/element", etc.
     *
     * @return non-empty-string|string '' si inválido
     */
    private function sanitizeComponent(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        // Recorte defensivo
        if (strlen($value) > 255) {
            $value = substr($value, 0, 255);
        }

        // Permitimos letras, dígitos, guion, guion bajo, punto, dos puntos y barra
        $value = preg_replace('/[^a-zA-Z0-9_\-.:\/]/', '', $value) ?? '';

        return $value;
    }

    /**
     * Dada una lista CSV, añade un elemento si no existe (comparación exacta tras trim).
     *
     * @param non-empty-string $value
     * @return array{0:string,1:bool} [csvNormalizado, añadido]
     */
    private function addToCsvListUnique(string $csv, string $value): array
    {
        // Explota en items, normaliza espacios y filtra vacíos
        $items = array_values(
            array_filter(
                array_map(
                    static fn (string $v): string => trim($v),
                    $csv === '' ? [] : explode(',', $csv)
                ),
                static fn (string $v): bool => $v !== ''
            )
        );

        // Coincidencia exacta (case-sensitive). Si prefieres insensible a mayúsculas, usa strtolower en ambos lados.
        $exists = in_array($value, $items, true);

        if (!$exists) {
            $items[] = $value;
        }

        // Reconstruye CSV compacto "A,B,C" (sin espacios extra)
        $newCsv = implode(',', $items);

        return [$newCsv, !$exists];
    }

}
