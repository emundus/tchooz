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

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;

if (!defined('SCP_USER_AGENT')) define('SCP_USER_AGENT', 'Securitycheck User agent');
if (!defined('SCP_CACERT_PEM')) define('SCP_CACERT_PEM', __DIR__ . '/cacert.pem');
/**
 * Modelo Securitycheck
 */
class DatabaseupdatesModel extends BaseModel
{
	/**
     * Variable que contendrá el tipo de componente de securitycheck instalado 
     *
     @var string
     */
    public $securitycheck_type = 'Not_defined';
	/**
     * Variable que almacena la tabla en la que insertar las nuevas vulnerabilidades
     *
     @var string
     */    
    public $vuln_table = 'Not_defined';
    /**
     * Variable que contiene la versión de la bbdd local (contendrá el mayor valor del campo 'dbversion' del archivo xml leído)
     *
     @var string
     */  
    public $higher_database_version = '0.0.0';
	/**
     Path to store scans
     *
     @var string
     */
	private $scan_path = JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'scans'.DIRECTORY_SEPARATOR;


    function __construct()
    {
        parent::__construct();		

    }

	/**
	 * Detecta qué edición de Securitycheck (Pro/Free) está instalada y habilitada.
	 */
	private function checkSecuritycheckType(): void
	{
		// Primero comprobamos la edición Pro
		if (ComponentHelper::isEnabled('com_securitycheckpro')) {
			$this->securitycheck_type = 'com_securitycheckpro';
			$this->vuln_table         = '#__securitycheckpro_db';
			return;
		}

		// Después la edición Free
		if (ComponentHelper::isEnabled('com_securitycheck')) {
			$this->securitycheck_type = 'com_securitycheck';
			$this->vuln_table         = '#__securitycheck_db';
			return;
		}

		// Si llegamos aquí, no hay ninguna instalada o están deshabilitadas
	}

	/**
	 * Añade o elimina vulnerabilidades en la BBDD de Securitycheck (Pro/Free) según
	 * registros recibidos y la versión local de la base de datos.
	 *
	 * Reglas:
	 *  - Sólo procesa registros con dbversion > $localDatabaseVersion
	 *  - Filtra por rama mayor de Joomla (jversion vs JVERSION)
	 *  - method=add (por defecto) ? upsert por (Product,published)
	 *  - method=delete ? borrado por (Product,published)
	 *
	 * @param list<array<string,string>> $vulns
	 * @param string                     $localDatabaseVersion
	 * @return void
	 */
	private function addVuln(array $vulns, string $localDatabaseVersion): void
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Asegura detección de edición (establece $this->securitycheck_type y $this->vuln_table)
		if ($this->securitycheck_type === 'Not_defined' || $this->vuln_table === 'Not_defined') {
			$this->checkSecuritycheckType();
			if ($this->securitycheck_type === 'Not_defined' || $this->vuln_table === 'Not_defined') {
				return;
			}
		}

		// Columnas permitidas por edición (evita mass-assignment)
		$allowedColsPro  = [
			'Product','vuln_type','Vulnerableversion','modvulnversion',
			'Joomlaversion','modvulnjoomla','description','vuln_class',
			'published','vulnerable','solution_type','solution',
		];
		$allowedColsFree = [
			'Product','vuln_type','Vulnerableversion','modvulnversion',
			'Joomlaversion','modvulnjoomla',
		];

		$isPro      = $this->securitycheck_type === 'com_securitycheckpro';
		$allowedMap = $isPro ? $allowedColsPro : $allowedColsFree;

		// Mapa feed -> columnas reales de la tabla
		$feedToDb = [
			'product'           => 'Product',
			'type'              => 'vuln_type',
			'vulnerableversion' => 'Vulnerableversion',
			'modvulnversion'    => 'modvulnversion',
			'joomlaversion'     => 'Joomlaversion',
			'modvulnjoomla'     => 'modvulnjoomla',
			'description'       => 'description',
			'class'             => 'vuln_class',
			'published'         => 'published',
			'vulnerable'        => 'vulnerable',
			'solution_type'     => 'solution_type',
			'solution'          => 'solution',
			// no mapeamos dbversion/jversion/method porque no existen como columnas
		];

		// Inicializa highest DB version
		$this->higher_database_version = $this->getDatabaseVersion();

		$madeChanges = false;

		$db->transactionStart();
		try {
			$localMajor = $this->getMajorVersion((string) \JVERSION);

			foreach ($vulns as $row) {
				// No añadimos limpieza adicional: asumimos $row viene ya normalizado/limpio.
				$v = $row;

				// Sólo procesa si dbversion > local
				if (!isset($v['dbversion']) || version_compare($v['dbversion'], $localDatabaseVersion, 'le')) {
					continue;
				}

				// Restringe por rama mayor de Joomla
				$vulnMajor = $this->getMajorVersion($v['jversion'] ?? '5.0.0');
				if ($vulnMajor !== $localMajor) {
					continue;
				}

				// Actualiza highest db version leída
				$this->higher_database_version = $v['dbversion'];

				$method      = $v['method']   ?? 'add';
				$product     = trim((string)($v['product']   ?? ''));
				$published   = trim((string)($v['published'] ?? ''));

				if ($product === '' || $published === '') {
					continue; // claves mínimas para identificar
				}

				$productVar   = $product;   // variables separadas para bind
				$publishedVar = $published;

				if ($method === 'delete') {
					// DELETE WHERE Product=? AND published=?
					$query = $db->getQuery(true)
						->delete($db->quoteName($this->vuln_table))
						->where($db->quoteName('Product') . ' = :product')
						->where($db->quoteName('published') . ' = :published')
						->bind(':product', $productVar, ParameterType::STRING)
						->bind(':published', $publishedVar, ParameterType::STRING);

					$db->setQuery($query);
					$db->execute();
					$madeChanges = $madeChanges || ($db->getAffectedRows() > 0);
					continue;
				}

				// ¿Existe ya por (Product,published)?
				$existsQ = $db->getQuery(true)
					->select('id')
					->from($db->quoteName($this->vuln_table))
					->where($db->quoteName('Product') . ' = :product')
					->where($db->quoteName('published') . ' = :published')
					->setLimit(1)
					->bind(':product', $productVar, ParameterType::STRING)
					->bind(':published', $publishedVar, ParameterType::STRING);

				$db->setQuery($existsQ);
				$existingId = (int) $db->loadResult();

				// ---- Construye payload mapeado y filtrado ----
				$payload = [];
				foreach ($feedToDb as $feedKey => $dbCol) {
					if (array_key_exists($feedKey, $v)) {
						$payload[$dbCol] = (string) $v[$feedKey];
					}
				}
				// Aplica allow-list de columnas según edición
				$payload = array_intersect_key($payload, array_flip($allowedMap));

				if ($existingId) {
					// Construir objeto con SOLO las columnas a actualizar (excluye claves identidad)
					$rowObj        = new \stdClass();
					$rowObj->id    = (int) $existingId;

					foreach ($payload as $col => $val) {
						if ($col === 'Product' || $col === 'published') {
							continue; // no tocamos las claves
						}
						$rowObj->$col = (string) $val;
					}

					// Si no hay nada que actualizar, salimos
					if (count(get_object_vars($rowObj)) <= 1) {
						// sólo trae 'id'
						// no marcamos cambios
					} else {
						// UPDATE clásico de Joomla
						$db->updateObject($this->vuln_table, $rowObj, 'id');
						$madeChanges = true;
					}
				} else {
					// INSERT (usando insertObject de Joomla)
					if (empty($payload)) {
						continue;
					}

					// Convierte el payload a stdClass para insertObject()
					$rowObj = new \stdClass();
					foreach ($payload as $col => $val) {
						// Mantén sólo columnas permitidas (ya aplicaste allow-list arriba,
						// esto es redundante pero seguro)
						if (in_array($col, $allowedMap, true)) {
							$rowObj->$col = (string) $val;
						}
					}

					// Ejecuta el insert				
					$db->insertObject($this->vuln_table, $rowObj , 'id');

					$madeChanges = true;
				}
			}

			$db->transactionCommit();

			if ($madeChanges) {
				$this->write_file();
			}
		} catch (\Throwable $e) {
			$db->transactionRollback();
			Log::add('DatabaseupdatesModel. addVuln function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
		}
	}
	
	/**
	 * Extrae la versión mayor (primer número) de una cadena de versión semántica.
	 *
	 * @param  string  $version  Ej: "5.4.3" -> 5, "4.0.0-beta" -> 4
	 *
	 * @return int  Número de versión mayor, o 0 si no se puede determinar
	 */
	private function getMajorVersion(string $version): int
	{
		// Usa regex para obtener el primer grupo de dígitos
		if (preg_match('/^(\d+)/', $version, $matches) === 1) {
			return (int) $matches[1];
		}

		return 0;
	}

	/**
     * Función que realiza todo el proceso de comprobación de nuevas vulnerabilidades
     *
     *
     * @return  void
     *     
     */
    function tarea_comprobacion()
    {
        
        // Inicializamos las variables
        $result = true;
        $downloadid = null;
        $xml = null;
    
        // Chequeamos el tipo de componente instalado
        $this->checkSecuritycheckType();
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
        $mainframe = Factory::getApplication();
    
        if ($this->securitycheck_type == 'Not_defined') {
            // No hay ninguna versión de Securitycheck instalada!
            $result = false;
        } else
        {    
            // Buscamos el Download ID 
            $downloadid = $downloadid ?? '';

			// 1) Intentar obtener desde el plugin de sistema (si está habilitado)
			if (PluginHelper::isEnabled('system', 'securitycheckpro_update_database')) {
				$downloadData = $this->get_extra_query_update_sites_table('securitycheckpro_update_database');

				if ($downloadData !== 'error') {
					$remoteDlid = $downloadData->extra_query ?? null;

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
				// No hay DLID: dejar mensaje y no continuar
				$this->set_campo_bbdd('message', 'COM_SECURITYCHECKPRO_UPDATE_DATABASE_DOWNLOAD_ID_EMPTY');
				$result = false;
			} else {       
                // Url que contendrá el fichero xml (debe contener el Download ID del usuario para poder acceder a ella)
                $xmlfile = "https://securitycheck.protegetuordenador.com/index.php/downloads/securitycheck-pro-database-updates-xml/securitycheck-pro-database-updates-xml-1-0-0/databases-xml?dlid=" . $downloadid;
                        
                // Array que contendrá todo el archivo xml 
                $array_complete = array();
            
                // Leemos el contenido del archivo xml (si existe la función curl_init)
				if (function_exists('curl_init')) {
					$xml = null;

					$ch = curl_init($xmlfile);

					$opts = [
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_MAXREDIRS      => 5,
						CURLOPT_CONNECTTIMEOUT => 15,
						CURLOPT_TIMEOUT        => 30,
						CURLOPT_AUTOREFERER    => true,
						CURLOPT_HEADER         => false,
						CURLOPT_ENCODING       => '', // acepta gzip/br
						CURLOPT_USERAGENT      => SCP_USER_AGENT,
						CURLOPT_SSL_VERIFYHOST => 2,
						CURLOPT_SSL_VERIFYPEER => true,
					];

					// Si existe el .pem definido en SCP_CACERT_PEM lo usamos
					if (defined('SCP_CACERT_PEM') && SCP_CACERT_PEM && is_file(SCP_CACERT_PEM)) {
						$opts[CURLOPT_CAINFO] = SCP_CACERT_PEM;
					}

					curl_setopt_array($ch, $opts);

					$xmlresponse = curl_exec($ch);
					$err         = curl_error($ch);

					if ($xmlresponse === false) {
						// Reintento sin CAINFO: lo eliminamos
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
						$xmlresponse = curl_exec($ch);
						$err = curl_error($ch);
					}

					if ($xmlresponse === false) {
						$result  = false;
						$message = $err ?: 'curl_exec returned false';
						Factory::getApplication()->enqueueMessage("Securitycheck Pro Database Update: " . $message, 'error');
					} else {
						// Chequeamos si hay meta refresh (antibot/captcha)
						$hasMetaRefresh = stripos($xmlresponse, 'http-equiv="refresh"') !== false;

						if ($hasMetaRefresh) {
							if (preg_match('~http-equiv\s*=\s*["\']refresh["\'].*?content\s*=\s*["\']\s*\d+\s*;\s*url\s*=\s*([^"\']+)~is', $xmlresponse, $m)) {
								$nextUrl = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
								$ch2 = curl_init($nextUrl);
								curl_setopt_array($ch2, $opts);
								$xmlresponse = curl_exec($ch2);								
							}
						}

						// Intentamos parsear XML
						libxml_use_internal_errors(true);
						$xml = simplexml_load_string(
							$xmlresponse,
							'SimpleXMLElement',
							LIBXML_NONET | LIBXML_NOCDATA
						);
					}
				} else {
					Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECK_CURL_NOT_DEFINED'));
					$xml = false;
				}
            
                // Comprobamos que hemos leido el archivo xml (esta variable será FALSE, por ejemplo, si no puede conectar con el servidor)
                if ($xml instanceof \SimpleXMLElement) {                                        
                    // Itera solo por los nodos <vulnerability>
					foreach ($xml->xpath('//vulnerability') as $vulnNode) {
						/** @var \SimpleXMLElement $vulnNode */
						$element = [];

						// Recorre los hijos directos de cada vulnerabilidad
						foreach ($vulnNode->children() as $key => $value) {
							$k = (string) $key;

							// Convierte a string y recorta espacios / controla entidades HTML
							$v = trim((string) $value);
							// elimina NUL/controles invisibles
							$v = preg_replace('/[^\P{C}\t\n\r]/u', '', $v) ?? '';

							// Decodifica entidades HTML para operadores (ej: &#60;&#61; ? <=)
							if ($k === 'modvulnversion' || $k === 'modvulnjoomla') {
								$v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
								$v = trim($v);
								// como tu columna es VARCHAR(2), clamp a 2 chars
								$v = mb_substr($v, 0, 2);
							}

							$element[$k] = $v;
						}

						// Asegura claves mínimas aunque falten en el XML
						$element += [
							'dbversion'         => '',
							'jversion'          => '',
							'product'           => '',
							'type'              => '',
							'vulnerableversion' => '',
							'modvulnversion'    => '',
							'joomlaversion'     => '',
							'modvulnjoomla'     => '',
							'description'       => '',
							'class'             => '',
							'published'         => '',
							'vulnerable'        => '',
							'solution_type'     => '',
							'solution'          => '',
						];

						$array_complete[] = $element;
					}
                
                    //Extraemos la versión de la bbdd local
                    $local_database_version = $this->getDatabaseVersion();
                
                    // Añadimos las nuevas vulnerabilidades a la BBDD
                    $this->addVuln($array_complete, $local_database_version);    
                } else
                {
                    $result = false;                
                    $scp_update_database_subscription_status = $mainframe->getUserState("scp_update_database_subscription_status", null);
                    if (empty($scp_update_database_subscription_status)) {
						// Establecemos la variable scp_update_database_subscription_status a 'No definida'    
                        $mainframe->setUserState("scp_update_database_subscription_status", Text::_('COM_SECURITYCHECKPRO_NOT_DEFINED'));
                    }                
                }
            
                // Si el proceso ha sido correcto, actualizamos la bbdd
				$timestamp = $this->get_Joomla_timestamp();
                if ($result) {					
                    // Actualizamos la fecha de la última comprobación y la versión de la bbdd local					
                    $this->set_campo_bbdd('last_check', $timestamp);
                    $this->set_campo_bbdd('version', $this->higher_database_version);
                    $this->set_campo_bbdd('message', 'PLG_SECURITYCHECKPRO_UPDATE_DATABASE_DATABASE_UPDATED');
				// Si no lo hacemos actualizamos la bbdd para hacer la petición en la siguiente ventana
                } else {					
                    $this->set_campo_bbdd('last_check', $timestamp);
				}
            }
        }    
    }
	
	/**
     * Función que actualiza un campo de la bbdd '#_securitycheckpro_update_database' con el valor pasado como argumento
     *
	 * @param   string             $campo    The name of field to update
	 * @param   string             $valor    The value of the field to update
     *
     * @return  void
     *     
     */
    function set_campo_bbdd($campo, $valor)
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$table = '#__securitycheckpro_update_database';

		// Validar columna
		$columns = $db->getTableColumns($table, false);
		if (!isset($columns[$campo])) {
			return; // columna inexistente, salimos
		}


		try {
			$db->transactionStart();

			// 1) Obtener id máximo (si no hay filas, creamos una)
			$query = $db->getQuery(true)
				->select('MAX(' . $db->quoteName('id') . ')')
				->from($db->quoteName($table));
			$db->setQuery($query);
			$maxId = (int) $db->loadResult();

			if ($maxId <= 0) {
				// Tabla vacía: insertar fila “singleton” con el campo ya establecido
				$query = $db->getQuery(true)
					->insert($db->quoteName($table))
					->columns($db->quoteName($campo))
					->values($db->quote($valor));
				$db->setQuery($query);
				$db->execute();

				$db->transactionCommit();
				return; // <-- IMPORTANTE: salimos para no borrar lo recién insertado
			}

			// 2) Eliminar duplicados, dejando solo la fila con id = $maxId
			$query = $db->getQuery(true)
				->delete($db->quoteName($table))
				->where($db->quoteName('id') . ' <> ' . (int) $maxId);
			$db->setQuery($query);
			$db->execute();

			// 3) Actualizar el campo en la fila superviviente (id = $maxId)
			$query = $db->getQuery(true)
				->update($db->quoteName($table))
				->set($db->quoteName($campo) . ' = ' . $db->quote($valor))
				->where($db->quoteName('id') . ' = ' . (int) $maxId);
			$db->setQuery($query);
			$db->execute();

			$db->transactionCommit();

		} catch (\Throwable $e) {
			// Revertir si algo falla
			try { $db->transactionRollback(); } catch (\Throwable $ignored) {}
			Log::add('DatabaseupdatesModel. set_campo_database function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
		}
	}
	
	/**
	 * Devuelve la fecha/hora de la última comprobación de actualizaciones.
	 *
	 *
	 * @return string|null Fecha/hora de la última comprobación o null si no existe.
	 */
	public function lastCheck(): ?string
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->select($db->quoteName('last_check'))
				->from($db->quoteName('#__securitycheckpro_update_database'))
				->order($db->quoteName('id') . ' DESC')
				->setLimit(1);

			$db->setQuery($query);
			$lastCheck = $db->loadResult();

			return is_string($lastCheck) && $lastCheck !== '' ? $lastCheck : null;
		} catch (\Throwable $e) {
			Log::add('DatabaseupdatesModel. lastCheck function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			return null;
		}
	}
	
	/**
	 * Devuelve la versión de la base de datos local de Securitycheck Pro.
	 *
	 *
	 * @return string Versión de la BBDD local o '0.0.0' si no existe.
	 */
	public function getDatabaseVersion(): string
	{
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->select($db->quoteName('version'))
				->from($db->quoteName('#__securitycheckpro_update_database'))
				->order($db->quoteName('id') . ' DESC')
				->setLimit(1);

			$db->setQuery($query);
			$version = $db->loadResult();

			return is_string($version) && $version !== '' ? $version : '0.0.0';
		} catch (\Throwable $e) {
			Log::add('DatabaseupdatesModel. getDatabaseVersion function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
			return '0.0.0';
		}
	}

    /**
	 * Chequea si es necesario lanzar una tarea de comprobación de vulnerabilidades.
	 *
	 */
	public function check_for_updates(): void
	{
		$interval = 0;

		// Último chequeo realizado (puede ser null)
		$lastCheck = $this->lastCheck();

		if ($lastCheck === null || $lastCheck === '') {
			// No hay registros previos ? forzamos primer chequeo
			$interval = 20;
		} else {
			try {
				$now = $this->get_Joomla_timestamp(); // debería devolver string con fecha/hora

				$nowDt  = new \DateTimeImmutable($now);
				$lastDt = new \DateTimeImmutable($lastCheck);

				$secondsDiff = $nowDt->getTimestamp() - $lastDt->getTimestamp();
				$interval    = (int) floor($secondsDiff / 3600);
			} catch (\Exception $e) {
				// En caso de fallo (ej: formato de fecha inválido), tratamos como si no hubiera chequeo previo
				$interval = 20;
			}
		}

		// Si han pasado más de 12h desde el último chequeo, lanzamos la tarea
		if ($interval > 12) {
			$this->tarea_comprobacion();
		}
	}    
		
	/**
	 * Escribe una "bandera" en la carpeta de escaneo para indicar
	 * que se debe actualizar la tabla de vulnerabilidades.
	 *
	 *
	 * @return void
	 */
	private function write_file(): void
	{
		// Normaliza y asegura el directorio base
		$base = Path::clean((string) $this->scan_path);
		if ($base === '' || $base === '/') {
			return; // nunca escribimos fuera o con path inválido
		}

		// Asegura que existe el directorio (0755 por defecto)
		if (!is_dir($base)) {
			try {
				if (!Folder::create($base, 0755)) {
					return;
				}
			} catch (\Throwable $e) {
				return;
			}
		}

		// Construye la ruta final y evita path traversal
		$filename   = 'update_vuln_table.php';
		$targetPath = Path::clean($base . '/' . $filename);

		// Comprueba que $targetPath está dentro de $base
		$realBase  = realpath($base);
		$realTarget = $this->safeRealpath($targetPath);

		if ($realBase === false || $realTarget === false || strpos($realTarget, $realBase) !== 0) {
			return; // intento de salir del directorio base
		}

		// Escribe de forma segura (crea el archivo si no existe, no borra contenido previo)
		// Contenido mínimo para "bandera"
		$content = "<?php // marker file for vulnerabilities DB update\n";

		try {
			// Si ya existe, no lo sobreescribimos innecesariamente
			if (is_file($realTarget)) {
				return;
			}

			// File::write usa LOCK_EX por defecto internamente (siempre que sea posible).
			if (File::write($realTarget, $content) === false) {
				return;
			}
			// Permisos conservadores
			@chmod($realTarget, 0644);
		} catch (\Throwable $e) {
			// Silencioso para no romper flujo
			return;
		}
	}

	/**
	 * realpath "seguro": devuelve false si no existe, pero intenta
	 * crear la parte de directorios para obtener ruta estable.
	 *
	 * @param string $path Ruta a resolver
	 * @return string|false Ruta absoluta (o "limpia") si es posible, o false si no existe el directorio
	 */
	private function safeRealpath(string $path): string|false
	{
		$dir = dirname($path);

		if (!is_dir($dir)) {
			// No creamos aquí; sólo intentamos resolver si existe
			return false;
		}

		return realpath($path) ?: $path; // si no existe el archivo, devolvemos la versión limpia
	}

}
