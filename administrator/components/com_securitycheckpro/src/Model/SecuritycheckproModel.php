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
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Version;
use Joomla\Filesystem\File;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\CMS\Log\Log;

class SecuritycheckproModel extends ListModel
{
    /**
     Array de datos
     *
     @var array<string>
     */
    var $_data;
	
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
     * Carga estado (filtros, orden, paginación) desde la request.
     */
    protected function populateState($ordering = 'a.id', $direction = 'DESC'): void
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app    = Factory::getApplication();
        $input  = $app->getInput();
		
        $extension_type = $app->getUserStateFromRequest('filter.extension_type', 'filter_extension_type');
        $this->setState('filter.extension_type', $extension_type);
        $vulnerable = $app->getUserStateFromRequest('filter.vulnerable', 'filter_vulnerable');
        $this->setState('filter.vulnerable', $vulnerable);

        // Orden y dirección
        $listOrder = $input->getCmd('list_ordering', $ordering);
        $listDirn  = $input->getCmd('list_direction', $direction);
        $this->setState('list.ordering', $listOrder);
        $this->setState('list.direction', $listDirn);

        // Paginación
        $limit = $input->getInt('limit', $app->get('list_limit', 20));
        $start = $input->getInt('start', 0);
        $this->setState('list.limit', $limit);
        $this->setState('list.start', $start);

        parent::populateState($listOrder, $listDirn);
    }

    /**
     * Construye la query aplicando los filtros presentes (o ninguno).
     *
     * @return QueryInterface
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('a.*')
          ->from($db->quoteName('#__securitycheckpro', 'a'));

        // Filtros
        $type       = (string) $this->getState('filter.extension_type', '');
        $vulnerable = (string) $this->getState('filter.vulnerable', '');

        if ($type !== '') {
            $query->where($db->quoteName('a.sc_type') . ' = ' . $db->quote($type));
        }

        if ($vulnerable !== '') {
            // Si vulnerable es "0/1" o "si/no", normaliza aquí si procede
            $normalized = in_array(strtolower($vulnerable), ['1','si','y','true'], true) ? '1' : (in_array(strtolower($vulnerable), ['0','no','n','false'], true) ? '0' : $vulnerable);
            $query->where($db->quoteName('a.vulnerable') . ' = ' . $db->quote($normalized));
        }

        // Orden
        $orderCol  = $this->getState('list.ordering', 'a.id');
        $orderDirn = 'ASC';
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
	
	/**
	 * Get the list of items.
	 *
	 * @return array<int, object> List of records as objects
	 */
	public function getItems(): array
	{
		$this->buscar();

		return parent::getItems();
	}

    
    /**
     * Compara los componentes de la BBDD de 'securitycheck' con los de 'securitycheck_db" y actualiza los componentes que sean vulnerables 
     *
   	 *
     * @return  void
     *     
     */
    function chequear_vulnerabilidades()
    {
        // Extraemos los componentes instalados
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app     = Factory::getApplication();
		
        $query = 'SELECT * FROM #__securitycheckpro as a ORDER BY a.id ASC';
        $db->setQuery($query);
        $components = $db->loadAssocList();
		
		// Versión de Joomla instalada
		$local_joomla_branch = explode('.', JVERSION)[0];
		
        // Extraemos los componentes vulnerables para nuestra versión de Joomla
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__securitycheckpro_db'))
            ->where($db->quoteName('Joomlaversion').' = '.$db->quote($local_joomla_branch));
        $db->setQuery($query);
		$vuln_components = $db->loadAssocList();
		
		foreach ($vuln_components as $vulnerable_product)
        {
			$valor_campo_vulnerable = "Si"; // Valor que tendrá el campo 'Vulnerable' cuando se actualice. También puede tener el valor 'Indefinido'.
			$components_key = array_search($vulnerable_product['Product'], array_column($components, 'Product'));
			
			if ($components_key === false) {
				// El producto vulnerable no está instalado
			} else {
				if ( $components[$components_key]['sc_type'] == $vulnerable_product['vuln_type'] ) {
					$modvulnversion = $vulnerable_product['modvulnversion']; //Modificador sobre la versión de la extensión
                    $db_version = $components[$components_key]['Installedversion']; // Versión de la extensión instalada
                    $vuln_version = $vulnerable_product['Vulnerableversion']; // Versión de la extensión vulnerable
					                
                    // Usamos la funcion 'version_compare' de php para comparar las versiones del producto instalado y la del componente vulnerable					
                    $version_compare = version_compare($db_version, $vuln_version, $modvulnversion);
					if ( ($version_compare) || ($vuln_version == '---') ) {
						// El producto es vulnerable
						$query = $db->getQuery(true)
                            ->select(array('id'))
                            ->from($db->quoteName('#__securitycheckpro_vuln_components'))
                            ->where($db->quoteName('Product').' = '.$db->quote($vulnerable_product['Product']))
							->where($db->quoteName('vuln_id').' = '.$db->quote($vulnerable_product['id']));
                        $db->setQuery($query);
                        $exists = $db->loadResult();
						
						$valor = (object) array(
							'Product' => $vulnerable_product['Product'],
                            'vuln_id' => $vulnerable_product['id'],
                        );
                                                                    
                        try
                        {
							// Añadimos los datos a la tabla 'securitycheck_vuln_components' si no existen ya   
							if (is_null($exists)) {
								$result = $db->insertObject('#__securitycheckpro_vuln_components', $valor);        
							}
							
							$res_actualizar = $this->actualizarRegistro($vulnerable_product['Product'], 'securitycheckpro', 'Product', $valor_campo_vulnerable, 'Vulnerable');
							if ( $res_actualizar ) { // Se ha actualizado la BBDD correctamente                            
							} else {                            
								$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_UPDATE_VULNERABLE_FAILED') ."'" . $vulnerable_product['Product'] ."'", 'error');
							}
                        } catch (\Exception $e)
                        {    
                            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_UPDATE_VULNERABLE_FAILED') ."'" . $vulnerable_product['Product'] ."'", 'error');                         
                        }						
					} else {
						// El producto NO es vulnerable. Borramos la entrada de la tabla 'securitycheck_vuln_components' si existe
						$query2 = $db->getQuery(true);
						$conditions = array(
							$db->quoteName('Product') . ' = ' . $db->quote($vulnerable_product['Product']), 
							$db->quoteName('vuln_id') . ' = ' . $db->quote($vulnerable_product['id'])
						);

						$query2->delete($db->quoteName('#__securitycheckpro_vuln_components'));
						$query2->where($conditions);
						
                        $db->setQuery($query2);						
                        $db->execute();						
					}
					
				}
			}
		}			
    }

	/**
	 * Actualiza de forma segura un campo en una tabla permitida.
	 *
	 * Seguridad:
	 *  - Allow-list de tablas y columnas
	 *  - Parámetros enlazados (bind) en vez de concatenar
	 *  - Normalización de strings y límites de longitud
	 *  - Manejo de errores sin filtrar mensajes de BBDD al usuario
	 *
	 * @param string      $nombre      Valor para la condición WHERE (del campo $campo)
	 * @param string      $database    Nombre lógico de la tabla (sin prefijo), debe estar en la allow-list
	 * @param string      $campo       Campo para la condición WHERE (p.ej. 'Product'), must be allowed
	 * @param string      $nuevo_valor Nuevo valor para $campo_set
	 * @param string      $campo_set   Campo a actualizar
	 * @param string|null $tipo        Filtro adicional por 'sc_type' (opcional)
	 *
	 * @return bool True si se actualizó al menos una fila, False en caso contrario o error
	 */
	function actualizarRegistro(
		string $nombre,
		string $database,
		string $campo,
		string $nuevo_valor,
		string $campo_set,
		?string $tipo = null
	): bool {
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// ---- Allow-lists ----
		// Mapea nombres lógicos a tablas reales (sin prefijo)
		$allowedTables = [
			'securitycheckpro' => '#__securitycheckpro',			
		];

		// Columnas permitidas (por tabla).
		/** @var array<string, array<int, string>> $allowedColumns */
		$allowedColumns = [
			'securitycheckpro' => [	
				'Product',			
				'Vulnerable',
				'sc_type'				
			],
		];

		// Validación de tabla
		if (!isset($allowedTables[$database])) {
			// Tabla no permitida
			return false;
		}

		$tableName   = $allowedTables[$database];
		$tableKey    = $database; // clave para $allowedColumns
		/** @var array<int, string> $tableCols */
		$tableCols   = $allowedColumns[$tableKey];

		// Normaliza y valida nombres de columna (WHERE y SET)
		$campo     = trim($campo);
		$campo_set = trim($campo_set);
		
		if ($campo === '' || $campo_set === '') {
			return false;
		}
		if (!in_array($campo, $tableCols, true)) {
			return false;
		}
		if (!in_array($campo_set, $tableCols, true)) {
			return false;
		}

		// Si se usa $tipo, solo permitimos si existe la columna sc_type
		$useTipo = $tipo !== null && $tipo !== '';
		if ($useTipo && !in_array('sc_type', $tableCols, true)) {
			return false;
		}
		
		// Normalización/defensas básicas de datos (no afecta al bind, solo higiene)
		$nombre      = mb_substr($nombre, 0, 255);
		$nuevo_valor = mb_substr($nuevo_valor, 0, 2048);
		$tipo        = $useTipo ? mb_substr((string) $tipo, 0, 255) : null;	
		// Elige tipo Joomla (string|array) para bind
		$dataType = $tipo === null ? ParameterType::NULL : ParameterType::STRING;

		try {
			$query = $db->getQuery(true);

			// UPDATE <tabla>
			$query->update($db->quoteName($tableName));

			// SET <campo_set> = :nuevo_valor
			$query->set($db->quoteName($campo_set) . ' = :nuevo_valor');

			// WHERE <campo> = :nombre
			$query->where($db->quoteName($campo) . ' = :nombre');

			// AND sc_type = :tipo (opcional)
			if ($useTipo) {
				$query->where($db->quoteName('sc_type') . ' = :tipo');
				$query->bind(':tipo', $tipo, ParameterType::STRING);
			}

			// Bind de parámetros
			$query->bind(':nuevo_valor', $nuevo_valor);
			$query->bind(':nombre', $nombre);

			if ($useTipo) {
				$query->bind(':tipo', $tipo, $dataType);
			}

			$db->setQuery($query);
			$db->execute();

			// Devolvemos true solo si afectó al menos a una fila
			return $db->getAffectedRows() > 0;
		} catch (\Throwable $e) {
			Log::add('SecuritycheckproModel. actualizarRegistro function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			return false;
		}
	}

    /**
	 * Busca si existe un registro en #__extensions por el campo permitido.
	 *
	 * @param string $nombre   Valor a buscar (p.ej. 'com_content' o 'securitycheckpro')
	 * @param string $database Debe ser 'extensions' (se fuerza a este valor por seguridad)
	 * @param string $campo    Campo permitido: 'element' | 'name'
	 *
	 * @return bool True si existe, false en caso contrario
	 */
	function buscar_registro(string $nombre, string $database, string $campo): bool
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// --- Allow-list de tabla y campo (evita inyección en identificadores) ---
		$allowedTable  = 'extensions';
		$allowedFields = ['element', 'name'];

		if (strtolower($database) !== $allowedTable) {
			// Forzamos a la tabla segura
			$database = $allowedTable;
		}

		if (!in_array($campo, $allowedFields, true)) {
			// Por seguridad, si el campo no es válido, devolvemos false
			return false;
		}

		$q = $db->getQuery(true)
			->select('1')
			->from($db->quoteName('#__' . $database))
			->where($db->quoteName($campo) . ' = ' . $db->quote($nombre))
			->setLimit(1);

		try {
			$db->setQuery($q);
			// Si existe alguna fila, loadResult() devolverá '1'
			return (bool) $db->loadResult();
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Inserta un registro en #__securitycheckpro de forma segura.
	 *
	 * @return bool True si inserta, false si falla
	 */
	function insertar_registro(string $nombre, string $version, string $tipo): bool
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Normalización mínima (no hace falta escapar: insertObject crea los bindings)
		$nombre  = trim($nombre);
		$version = trim($version);
		$tipo    = trim($tipo);

		if ($nombre === '' || $version === '' || $tipo === '') {
			return false;
		}

		$row             = new \stdClass();
		$row->Product            = $nombre;
		$row->Installedversion   = $version;
		$row->sc_type            = $tipo;

		try {
			// insertObject usa consultas preparadas de Joomla; no concatenamos SQL.
			return $db->insertObject('#__securitycheckpro', $row, 'id');
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Elimina de #__securitycheckpro las entradas cuyo Product ya no existe como componente en #__extensions.
	 * Mantiene 'Joomla!' como excepción.
	 *
	 * Eficiente: borra en una sola consulta usando NOT EXISTS.
	 *
	 * @return void
	 */
	function eliminar_componentes_desinstalados(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplicationInterface $app */
		$app   = Factory::getApplication();
		$input = $app->getInput();

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Subconsulta: existe en #__extensions como componente con element = Product
		$sub = $db->getQuery(true)
			->select('1')
			->from($db->quoteName('#__extensions', 'e'))
			->where('e.' . $db->quoteName('element') . ' = sp.' . $db->quoteName('Product'))
			->where('e.' . $db->quoteName('type') . ' = ' . $db->quote('component'));

		// DELETE con NOT EXISTS (portátil y seguro)
		$q = $db->getQuery(true)
			->delete($db->quoteName('#__securitycheckpro', 'sp'))
			->where('sp.' . $db->quoteName('Product') . ' <> ' . $db->quote('Joomla!'))
			->where('NOT EXISTS (' . $sub . ')');

		try {
			$db->setQuery($q);
			$db->execute();
			$deleted = (int) $db->getAffectedRows();

			if ($deleted > 0) {
				// Compatibilidad con tu lógica original: dejamos el dato en input
				$input->set('comp_eliminados', Text::_('COM_SECURITYCHECKPRO_DELETED_COMPONENTS') . $deleted);
				// Y además mostramos un mensaje informativo en backend
				$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_DELETED_COMPONENTS') . $deleted, 'info');
			}
		} catch (\Throwable $e) {
			// Si quieres notificar fallo sin revelar detalles sensibles:
			$app->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
		}
	}

    /**
	 * Extrae los componentes instalados y sincroniza la tabla #__securitycheckpro.
	 *
	 * @param array<int,object> $registros Filas de #__extensions con al menos ->element y ->manifest_cache
	 * @return void
	 */
	function actualizarbbdd(array $registros): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$scanPath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components'
			. DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR
			. 'scans' . DIRECTORY_SEPARATOR;

		// --- 1) ¿Hace falta sincronizar? ---
		$needSync = false;

		// (a) Archivo testigo
		if (is_file($scanPath . 'update_vuln_table.php')) {
			$needSync = true;
			$app->setUserState('show_vulnerabilities_table_updated', true);
		}

		// (b) Tabla vacía
		if (!$needSync) {
			try {
				$qCount = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->quoteName('#__securitycheckpro'));
				$db->setQuery($qCount);
				$count = (int) $db->loadResult();
				if ($count === 0) {
					$needSync = true;
				}
			} catch (\Throwable $e) {
				// Si no podemos leer, forzamos sincronización inicial
				$needSync = true;
			}
		}

		if (!$needSync) {
			return;
		}

		// --- 2) Normaliza y mapea registros de #__extensions ---
		$rows = [];
		foreach ($registros as $row) {
			// Valida forma mínima
			if (!isset($row->element)) {
				continue;
			}

			$product = trim((string) $row->element);
			if ($product === '') {
				continue;
			}

			$manifestRaw = isset($row->manifest_cache) ? (string) $row->manifest_cache : '';
			$version = '0.0.0';
			$type    = 'Notdefined';

			if ($manifestRaw !== '') {
				$decoded = json_decode(
					$manifestRaw,
					false, // objeto
					256,
					JSON_THROW_ON_ERROR
					| JSON_INVALID_UTF8_SUBSTITUTE
				);
				if ($decoded instanceof \stdClass) {
					if (property_exists($decoded, 'version') && is_scalar($decoded->version)) {
						$version = (string) $decoded->version;
					}
					if (property_exists($decoded, 'type') && is_scalar($decoded->type)) {
						$type = (string) $decoded->type;
					}
				}
			}

			// Normalizaciones finales (longitudes razonables)
			$product = mb_substr($product, 0, 190);    // índices/únicos suelen estar <191 chars en utf8mb4
			$version = mb_substr(trim($version), 0, 64);
			$type    = mb_substr(trim($type), 0, 32);

			$rows[] = [
				'Product'          => $product,
				'Installedversion' => $version,
				'sc_type'          => $type,
			];
		}

		// --- 3) Transacción: TRUNCATE + inserciones ---
		try {
			$db->transactionStart();

			// TRUNCATE específico por driver
			$driver = strtolower((string) $db->getName()); // 'mysqli', 'pdomysql', 'pgsql', etc.
			$table  = $db->quoteName('#__securitycheckpro');

			if (str_contains($driver, 'pgsql')) {
				$db->setQuery('TRUNCATE TABLE ' . $table . ' RESTART IDENTITY')->execute();
			} else {
				// MySQL/MariaDB y otros
				$db->setQuery('TRUNCATE TABLE ' . $table)->execute();
			}

			// Inserta fila para "Joomla!"
			$joomlaVersion = (new Version())->getShortVersion();

			$core = new \stdClass();
			$core->Product          = 'Joomla!';
			$core->Installedversion = mb_substr($joomlaVersion, 0, 64);
			$core->sc_type          = 'core';
			$db->insertObject('#__securitycheckpro', $core);

			// Inserta extensiones mapeadas
			foreach ($rows as $ext) {
				$obj                   = new \stdClass();
				$obj->Product          = $ext['Product'];
				$obj->Installedversion = $ext['Installedversion'];
				$obj->sc_type          = $ext['sc_type'];
				$db->insertObject('#__securitycheckpro', $obj);
			}

			$db->transactionCommit();
		} catch (\JsonException $e) {
			// JSON malformado en algún manifest_cache: no aborta toda la sync; puedes loguear y continuar.
			$db->transactionRollback();
			Log::add('SecuritycheckproModel. actualizarbbdd function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			return;
		} catch (\Throwable $e) {
			$db->transactionRollback();
			return;
		}

		// --- 4) Post-acciones: chequeo de vulnerabilidades + limpiar archivo testigo ---
		try {
			$this->chequear_vulnerabilidades();
		} catch (\Throwable $e) {
			// No interrumpir el flujo; opcional: log
		}

		$witness = $scanPath . 'update_vuln_table.php';
		if (is_file($witness)) {
			// Ignoramos error de borrado
			try {
				File::delete($witness);
			} catch (\Throwable) {
			}
		}
	}

    /**
	 * Busca componentes instalados (state = 0) de tipos permitidos.
	 *
	 * @return bool True si el flujo se ejecuta sin errores (independientemente de si hay filas), False en caso de error
	 */
	function buscar(): bool
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app    = Factory::getApplication();
		$jinput = $app->getInput();

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Tipos permitidos (lista blanca)
		$allowedTypes = ['component', 'module', 'plugin'];
		
		$state = 0;

		try {
			$query = $db->getQuery(true);

			$query
				->select([
					$db->quoteName('extension_id'),
					$db->quoteName('name'),
					$db->quoteName('type'),
					$db->quoteName('element'),
					$db->quoteName('folder'),
					$db->quoteName('client_id'),
					$db->quoteName('enabled'),
					$db->quoteName('state'),
					$db->quoteName('manifest_cache'),
				])
				->from($db->quoteName('#__extensions'))
				// state = :state
				->where($db->quoteName('state') . ' = :state');

			$inList = implode(',', array_map([$db, 'quote'], $allowedTypes));
			$query->where($db->quoteName('type') . " IN ($inList)");

			$query->bind(':state', $state, ParameterType::INTEGER);

			$db->setQuery($query);

			// Carga segura de resultados
			/** @var array<int, \stdClass> $result */
			$result = (array) $db->loadObjectList();

			// Solo si hay datos y es array, delega a la rutina que actualiza la BBDD
			if ($result !== []) {
				$this->actualizarbbdd($result);
			}
			return true;
		} catch (\Throwable $e) {
			Log::add('SecuritycheckproModel. buscar function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			return false;
		}
	}

    /**
	 * Obtiene la fecha de actualización del último componente añadido
	 * a la tabla #__securitycheckpro_db por el plugin 'Update Database'.
	 *
	 * @return string Fecha (formato de la columna `published`) o cadena vacía si no hay registros o en caso de error
	 */
	function get_last_update(): string
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->select($db->quoteName('published'))
				->from($db->quoteName('#__securitycheckpro_db'))
				->order($db->quoteName('id') . ' DESC');

			// En Joomla el LIMIT se pasa con setLimit()
			$db->setQuery($query, 0, 1);

			/** @var string|null $lastDate */
			$lastDate = $db->loadResult();

			return $lastDate !== null ? (string) $lastDate : '';
		} catch (\Throwable $e) {
			// Opcional: log interno
			return '';
		}
	}

   
    /**
	 * Devuelve una tabla HTML con las vulnerabilidades del producto.
	 *
	 * @param  string $product
	 * @return string HTML
	 */
	public function filter_vulnerable_extension(string $product): string
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Helper de escape local (incluye ENT_QUOTES)
		$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

		try {
			// Query con bind param y alias
			$q = $db->getQuery(true)
				->select($db->quoteName([
					'd.description',
					'd.vuln_class',
					'd.published',
					'd.vulnerable',
					'd.solution_type',
					'd.solution',
				]))
				->from($db->quoteName('#__securitycheckpro_db', 'd'))
				->join(
					'INNER',
					$db->quoteName('#__securitycheckpro_vuln_components', 'vc') . ' ON ' .
					$db->quoteName('vc.vuln_id') . ' = ' . $db->quoteName('d.id')
				)
				->where($db->quoteName('vc.Product') . ' = :product')
				->order($db->quoteName('d.published') . ' DESC');

			$q->bind(':product', $product, ParameterType::STRING);

			$db->setQuery($q);
			$rows = (array) $db->loadAssocList();

		} catch (\Throwable $ex) {
			// Mostramos error amigable y salimos
			$msg = Text::sprintf('JERROR_LOADING_MENUS', $e($ex->getMessage())); // reutilizamos texto genérico
			return '<div class="alert alert-danger" role="alert">' . $msg . '</div>';
		}

		if (!$rows) {
			return '<div class="alert alert-info" role="alert">'
				 . Text::_('COM_SECURITYCHECKPRO_NO_VULNERABILITIES_FOR_PRODUCT')
				 . '</div>';
		}

		// Tabla: cabecera
		$html  = '<table class="table table-bordered table-hover align-middle">';
		$html .= '  <thead>';
		$html .= '    <tr>';
		$html .= '      <th class="alert alert-warning text-center">' . $e(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_DETAILS'))   . '</th>';
		$html .= '      <th class="alert alert-warning text-center">' . $e(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_CLASS'))     . '</th>';
		$html .= '      <th class="alert alert-warning text-center">' . $e(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_PUBLISHED')) . '</th>';
		$html .= '      <th class="alert alert-warning text-center">' . $e(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_VULNERABLE')). '</th>';
		$html .= '      <th class="alert alert-warning text-center">' . $e(Text::_('COM_SECURITYCHECKPRO_VULNERABILITY_SOLUTION'))   . '</th>';
		$html .= '    </tr>';
		$html .= '  </thead>';
		$html .= '  <tbody>';

		foreach ($rows as $r) {
			$description = $e($r['description'] ?? '');
			$class       = $e($r['vuln_class'] ?? '');
			$published   = (string) ($r['published'] ?? '');
			$vulnerable  = (string) ($r['vulnerable'] ?? '');
			$solType     = (string) ($r['solution_type'] ?? '');
			$solutionRaw = (string) ($r['solution'] ?? '');

			// Formato de fecha legible por sitio
			$publishedTxt = $published !== ''
				? $e(HTMLHelper::_('date', $published, Text::_('DATE_FORMAT_LC4')))
				: '-';

			// Badge para estado vulnerable
			$vulnBadgeClass = 'badge bg-warning';
			if (strcasecmp($vulnerable, 'Si') === 0) {
				$vulnBadgeClass = 'badge bg-danger';
			} elseif (strcasecmp($vulnerable, 'No') === 0) {
				$vulnBadgeClass = 'badge bg-success';
			}

			// Mensaje de solución
			switch ($solType) {
				case 'update':
					$solution = Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_UPDATE') . ' ' . $e($solutionRaw);
					break;
				case 'none':
					$solution = Text::_('COM_SECURITYCHECKPRO_SOLUTION_TYPE_NONE');
					break;
				default:
					// Fallback: mostrar contenido si existe
					$solution = $solutionRaw !== '' ? $e($solutionRaw) : Text::_('JNONE');
					break;
			}

			$html .= '    <tr>';
			$html .= '      <td class="text-center">' . $description . '</td>';
			$html .= '      <td class="text-center">' . $class . '</td>';
			$html .= '      <td class="text-center">' . $publishedTxt . '</td>';
			$html .= '      <td class="text-center"><span class="' . $e($vulnBadgeClass) . '">' . $e($vulnerable) . '</span></td>';
			$html .= '      <td class="text-center">' . $solution . '</td>';
			$html .= '    </tr>';
		}

		$html .= '  </tbody>';
		$html .= '</table>';

		return $html;
	}

}
