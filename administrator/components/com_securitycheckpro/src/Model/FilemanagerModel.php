<?php
declare(strict_types=1);
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Client\ClientHelper;
use Joomla\CMS\Client\FtpClient;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Input\Input;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Console\Application as ConsoleApplication;
use Joomla\Registry\Registry;
use JsonException;

class FilemanagerModel extends BaseModel
{
	/**
     * @var object Pagination 
     */
    var $_pagination = null;

    /**
     * @var int Total number of files of Pagination 
     */
    var $total = 0;

    /**
     * @var array<string> The files to process 
     */
    private $Stack = [];

    /**
	 * @var array<int, array{
	 *     path: string,
	 *     hash: string,
	 *     notes: string,
	 *     new_file: int|string,
	 *     safe_integrity: int|string
	 * }>
	 */
    private $Stack_Integrity = [];
	
	 /**
	 * @var array<int, array{
	 *     path: string,
	 *     hash: string,
	 *     notes: string,
	 *     new_file: int|string,
	 *     safe_integrity: int|string
	 * }>
	 */
    
    /**
     * @var int Total numbers of file/folders in this site. Permissions option 
     */
    public $files_scanned = 0;

    /**
     * @var int Total numbers of file/folders in this site. Integrity option
     */
    public $files_scanned_integrity = 0;

    /**
     * @var int Numbers of files/folders with  incorrect permissions 
     */
    public $files_with_incorrect_permissions = 0;

    /**
     * @var int Numbers of files/folders with  incorrect integrity 
     */
    public $files_with_incorrect_integrity = 0;

    /**
     * @var array<string> Skip subdirectories and files of these directories. Permissions option 
     */
    private $skipDirsPermissions = [];

    /**
     * @var array<string> Skip subdirectories and files of these directories. Integrity option 
     */
    private $skipDirsIntegrity = [];

    /**
     * @var int Percent of files processed each time 
     */
    public $last_percent = 0;

    /**
     * @var int Percent of files processed each time 
     */
    private $last_percent_permissions = 0;

    /**
     * @var int Percent of files processed each time 
     */
    private $files_processed_permissions = 0;

    /**
     * @var boolean Task completed 
     */
    private $task_completed = false;

    /**
     * @var string Path to the folder where scans will be stored 
     */
    private $folder_path = '';

    /**
     * @var string filemanager's name 
     */
    private $filemanager_name = '';

    /**
     * @var string fileintegrity's name 
     */
    private $fileintegrity_name = '';

    /**
     * @var int Numbers of files scanned looking for malware 
     */
    public $files_scanned_malwarescan = 0;

    /**
     * @var int Numbers of files suspicious of malware 
     */
    public $suspicious_files = 0;

    /**
     * @var int Percent of files processed each time 
     */
    private $files_processed_malwaresecan = 0;

    /**
     * @var int Percent of files processed each time 
     */
    private $last_percent_malwarescan = 0;

    /**
     * @var array<array<string>> The files to process 
     */
    private $Stack_malwarescan = [];

    /**
     * @var string malwarescan's name 
     */
    private $malwarescan_name = '';

    /**
     * @var string file content 
     */
    public $content = null;

    /**
     * @var string File extensions to analyze looking for malware 
     */
    private $fileExt = null;

    /**
     * @var int Use the exceptions stablished in File Manager option (Malware scan) 
     */
    private $use_filemanager_exceptions = 1;

    /**
     * @var array<string> Skip subdirectories and files of these directories. Integrity option 
     */
    private $skipDirsMalwarescan =[];

    /**
     * @var int Percent of files processed each time 
     */
    private $files_processed = 0;

    /**
     * @var SplFileObject|resource|null  The file pointer to the current log file 
     */
    protected $fp = null;

    /**
     * @var string File name for permissions log 
     */
    private $filepermissions_log_name = null;

    /**
     * @var string File name for integrity log 
     */
    private $fileintegrity_log_name = null;

    /**
     * @var string File name for malware log 
     */
    private $filemalware_log_name = null;
	
	/**
     * @var string File name for malware log 
     */
	private $controlcenter_log_name = null;
	
	/**
     * @var int Number of executable files
     */
	private $executable_files = 0;
	
	/**
     * @var int Number of non executable files
     */
	private	$non_executable_files = 0;
	
	/**
     * @var array<string> Array of executable file extensions
     */
	private $excludedExtensions =['aif','iff','conf','m3u','m4a','mid','mp3','mpa','wav','wma','3g2','3gp','asf','asx','avi','flv','m4v','mov','mp4','mpg','rm','srt','swf','vob','wmv','bmp','dds','gif','jpg','png','psd','pspimage','tga','thm','tif','tiff','yuv','eps','svg','txt','tar','zip','jpa','pdf','woff','scss','css','gz','j01','j02','j03','log','less','sql','md'];
	
	/**
     * @var string Time taken to finish scans
     */
	private $time_taken = "";
	
	/**
     * @var array<string, mixed>|string Last scan info
     */
	private $last_scan_info = "";
	
	/**
     * @var array<string> The files to process 
     */
    private $stack_resume = [];
	
	/**
     * @var string The last time a file integrity scan was launched
     */
    private $last_check_integrity = '';	
	
	/**
     * @var array<string> keys of analized files by Metadefender Cloud
     */
    private $analized_keys_array = [];	
	
	/**
     * @var int The number of files analyzed by Metadefender Cloud
     */
    private $analized_files_last_hour = 0;	
	
	/**
     * @var int The number of hashes analyzed by Metadefender Cloud
     */
    private $analized_hashes_last_hour = 0;		

	/**
	 * @param array<string,mixed> $config
	 * @throws \Exception
	 */
	public function __construct(array $config = [])
	{
		// Filtros por defecto (solo si el llamante no los pasa)
		if (empty($config['filter_fields'])) {
			/** @var list<string> $ff */
			$ff = ['malware_type', 'alert_level'];
			$config['filter_fields'] = $ff;
		}

		parent::__construct($config);

		// Rutas base inmutables
		$adminPath  = rtrim((string) JPATH_ADMINISTRATOR, DIRECTORY_SEPARATOR);
		$rootPath   = rtrim((string) JPATH_ROOT, DIRECTORY_SEPARATOR);

		// Archivos / carpetas internas
		$this->folder_path = $this->safePath($adminPath . '/components/com_securitycheckpro/scans', $rootPath);
		$excepcionEscaneos = $this->safePath($adminPath . '/components/com_securitycheckpro/models/protection.php', $rootPath);

		// Parámetros del componente
		$params = ComponentHelper::getParams('com_securitycheckpro');

		// ---- Memory limit defensivo ----
		$memoryLimit = (string) $params->get('memory_limit', '512M');
		$effective   = $this->sanitizeMemoryLimit($memoryLimit, '512M'); // 128M..16384M + sufijo M
		@ini_set('memory_limit', $effective);
		if ($effective !== $memoryLimit) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_VALID_MEMORY_LIMIT'), 'error');
		}

		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		// ---- Listas de exclusión seguras ----
		// cache
		$this->addException($this->skipDirsPermissions, $this->safePath((string) JPATH_CACHE, $rootPath));
		$this->addException($this->skipDirsPermissions, $this->safePath($rootPath . '/cache', $rootPath));
		$this->addException($this->skipDirsIntegrity,   $this->safePath((string) JPATH_CACHE, $rootPath));
		$this->addException($this->skipDirsIntegrity,   $this->safePath($rootPath . '/cache', $rootPath));
		$this->addException($this->skipDirsMalwarescan, $this->safePath((string) JPATH_CACHE, $rootPath));
		$this->addException($this->skipDirsMalwarescan, $this->safePath($rootPath . '/cache', $rootPath));

		// carpeta de escaneos propia
		$this->addException($this->skipDirsMalwarescan, $this->folder_path);
		$this->addException($this->skipDirsIntegrity,   $this->folder_path);

		// tmp y logs (de configuración)
		$tmpConfigured = (string) $app->getConfig()->get('tmp_path', $rootPath . '/tmp');
		$logConfigured = (string) $app->getConfig()->get('log_path', $rootPath . '/logs');

		$this->addException($this->skipDirsPermissions, $this->safePath($tmpConfigured, $rootPath));
		$this->addException($this->skipDirsPermissions, $this->safePath($logConfigured, $rootPath));
		$this->addException($this->skipDirsIntegrity,   $this->safePath($tmpConfigured, $rootPath));
		$this->addException($this->skipDirsIntegrity,   $this->safePath($logConfigured, $rootPath));
		$this->addException($this->skipDirsMalwarescan, $this->safePath($logConfigured, $rootPath));

		// Akeeba (J3/J4)
		$this->addException($this->skipDirsIntegrity, $this->safePath($adminPath . '/components/com_akeebabackup/backup', $rootPath));
		$this->addException($this->skipDirsIntegrity, $this->safePath($adminPath . '/components/com_akeeba/backup', $rootPath));

		// protection.php (integridad + malware)
		$this->addException($this->skipDirsIntegrity,   $excepcionEscaneos);
		$this->addException($this->skipDirsMalwarescan, $excepcionEscaneos);

		// ---- Excepciones definidas por el usuario ----
		$this->appendExceptionsFromParam($params, 'file_manager_path_exceptions', $this->skipDirsPermissions, $rootPath);
		$this->appendExceptionsFromParam($params, 'file_integrity_path_exceptions', $this->skipDirsIntegrity, $rootPath);
		$this->appendExceptionsFromParam($params, 'malwarescan_path_exceptions', $this->skipDirsMalwarescan, $rootPath);

		// ---- Cargar “resume” previos de BBDD (con defensas) ----
		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$stack            = $this->loadJsonStorageValue($db, 'filemanager_resume');
		$stackIntegrity   = $this->loadJsonStorageValue($db, 'fileintegrity_resume');
		$stackMalwarescan = $this->loadJsonStorageValue($db, 'malwarescan_resume');

		// ---- Obtener nombres de log (tu lógica actual) ----
		$this->get_log_filename('filepermissions_log');
		$this->get_log_filename('fileintegrity_log');
		$this->get_log_filename('filemalware_log');

		// ---- Establecer nombres de ficheros (saneados) ----
		if (isset($stack['filename'])) {
			$safe = $this->sanitizeFilename((string) $stack['filename']);
			if ($safe !== '') {
				$this->filemanager_name = $safe;
			}
		}
		if (isset($stackIntegrity['filename'])) {
			$safe = $this->sanitizeFilename((string) $stackIntegrity['filename']);
			if ($safe !== '') {
				$this->fileintegrity_name = $safe;
			}
		}
		if (isset($stackMalwarescan['filename'])) {
			$safe = $this->sanitizeFilename((string) $stackMalwarescan['filename']);
			if ($safe !== '') {
				$this->malwarescan_name = $safe;
			}
		}

		// ---- Parámetros de malware scan ----
		$this->fileExt = $params->get('malwarescan_file_extensions') ?? null;
		$this->use_filemanager_exceptions = (int) $params->get('use_filemanager_exceptions', 1);

		// ---- Control de escaneo online ----
		$this->checkLastOnlinecheck();

		// ---- Paginación segura (CLI vs Web) ----
		$limit = 0;
		$limitstart = 0;

		if ($app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface) {
			/** @var int $limit */
			$limit = (int) $app->getUserStateFromRequest(
				'global.list.limit',
				'limit',
				(int) $app->get('list_limit'),
				'int'
			);
			/** @var int $limitstart */
			$limitstart = (int) $app->getInput()->get('limitstart', 0, 'int');
			$limitstart = ($limit > 0) ? (int) (floor($limitstart / $limit) * $limit) : 0;
		}

		// Limitar duro para evitar desbordes en memoria
		if ($limit <= 0) {
			$this->setState('limit', 100);
			$this->setState('showall', 1);
		} else {
			$this->setState('limit', $limit);
		}
		$this->setState('limitstart', $limitstart);

		// Deduplicar listas por si quedaron duplicados tras merges
		$this->skipDirsPermissions = array_values(array_unique($this->skipDirsPermissions));
		$this->skipDirsIntegrity   = array_values(array_unique($this->skipDirsIntegrity));
		$this->skipDirsMalwarescan = array_values(array_unique($this->skipDirsMalwarescan));
	}

	/* ======================= */
	/* ====== HELPERS =========*/
	/* ======================= */

	/**
	 * Normaliza y valida una ruta para que permanezca dentro de $root (p.ej. JPATH_ROOT).
	 *
	 * Acepta:
	 *  - Absoluta filesystem dentro de root: /home/.../site/images/...
	 *  - "Absoluta web" (desde docroot): /images/...
	 *  - Relativa: images/...
	 *
	 * Si no pasa la validación, devuelve $root (fallback).
	 */
	private function safePath(string $path, string $root): string
	{
		$rootClean = rtrim(Path::clean($root), DIRECTORY_SEPARATOR);

		if ($rootClean === '' || !is_dir($rootClean)) {
			// Fallback ultra conservador (si root no es válido)
			return rtrim(Path::clean($root), DIRECTORY_SEPARATOR);
		}

		$pathTrim = trim($path);

		if ($pathTrim === '') {
			return $rootClean;
		}

		// Limpia la ruta recibida (sin exigir que exista)
		$clean = Path::clean($pathTrim);

		// 1) Si empieza por root => ya es filesystem absoluta (o al menos con ese prefijo)
		// 2) Si empieza por "/" o "\" pero NO por root => lo interpretamos como "web absolute" => $root + ltrim(...)
		// 3) Si no empieza por separador => relativa => $root + $clean
		if (str_starts_with($clean, $rootClean)) {
			$candidate = $clean;
		} elseif (str_starts_with($clean, DIRECTORY_SEPARATOR) || str_starts_with($clean, '/') || str_starts_with($clean, '\\')) {
			// /images/...  =>  /root/images/...
			$candidate = $rootClean . DIRECTORY_SEPARATOR . ltrim($clean, "/\\");
		} else {
			// images/...  =>  /root/images/...
			$candidate = $rootClean . DIRECTORY_SEPARATOR . $clean;
		}

		$candidate = rtrim(Path::clean($candidate), DIRECTORY_SEPARATOR);

		// Validación final anti-traversal / escape
		if (!$this->isPathInside($rootClean, $candidate)) {
			return $rootClean;
		}

		return $candidate;
	}

	/**
	 * Añade una excepción (si es válida) a la lista dada.
	 *
	 * @param array<int,string> $list
	 */
	private function addException(array &$list, string $candidate): void
	{
		$candidate = rtrim($candidate, DIRECTORY_SEPARATOR);
		if ($candidate !== '' && !in_array($candidate, $list, true)) {
			$list[] = $candidate;
		}
	}

	/**
	 * Lee una clave de excepciones que puede venir como CSV o array y las añade.
	 *
	 * @param array<int,string> $target
	 */
	private function appendExceptionsFromParam(Registry $params, string $key, array &$target, string $rootPath): void
	{
		$raw = $params->get($key);
		/** @var list<string> $items */
		$items = [];

		if (is_string($raw)) {
			// Soporta CSV con comas y salto de línea
			$parts = preg_split('/[,\r\n]+/', $raw) ?: [];
			foreach ($parts as $p) {
				$p = trim($p);
				if ($p !== '') {
					$items[] = $p;
				}
			}
		} elseif (is_array($raw)) {
			foreach ($raw as $p) {
				if (is_string($p)) {
					$p = trim($p);
					if ($p !== '') {
						$items[] = $p;
					}
				}
			}
		}

		foreach ($items as $path) {
			$this->addException($target, $this->safePath($path, $rootPath));
		}
	}

	/**
	 * Devuelve un array asociativo desde storage_key (o [] si vacío / inválido).
	 *
	 * @return array<string,mixed>
	 */
	private function loadJsonStorageValue(DatabaseInterface $db, string $storageKey): array
	{
		$query = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote($storageKey));
		$db->setQuery($query, 0, 1);

		try {
			/** @var string|null $raw */
			$raw = $db->loadResult();
		} catch (\Throwable $e) {
			// No exponer detalles de BBDD
			Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_DB_READ_ERROR'), 'error');
			return [];
		}

		if (!is_string($raw) || $raw === '') {
			return [];
		}

		try {
			/** @var array<string,mixed> $decoded */
			$decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
			return is_array($decoded) ? $decoded : [];
		} catch (\JsonException) {
			Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_INVALID_JSON_STORAGE'), 'warning');
			return [];
		}
	}

	/**
	 * Sanitiza un nombre de fichero permitiendo Unicode (chino, japonés, etc.) y espacios.
	 * - Normaliza a NFC si está disponible (ext/intl)
	 * - Rechaza separadores de ruta, bytes nulos y "." / ".."
	 * - Sustituye caracteres fuera del allowlist por "_"
	 * - Colapsa espacios Unicode, evita nombres reservados de Windows,
	 *   quita punto/espacio final (Windows) y limita longitud.
	 */
	private function sanitizeFilename(string $name): string
	{
		$base = basename($name);

		// Normaliza Unicode (opcional pero recomendable)
		if (class_exists(\Normalizer::class)) {
			$norm = \Normalizer::normalize($base, \Normalizer::FORM_C);
			if (is_string($norm)) {
				$base = $norm;
			}
		}

		$base = trim($base);
		if ($base === '' || $base === '.' || $base === '..') {
			return '';
		}

		// Prohibido: separadores de ruta y byte nulo
		if (strpbrk($base, "/\\\0") !== false) {
			return '';
		}

		// Allowlist:
		//  - \p{L} letras de cualquier alfabeto (CJK incluido)
		//  - \p{N} números, \p{M} marcas (acentos combinados)
		//  - \p{Zs} espacios Unicode
		//  - y los signos: . _ - ( ) [ ] { } +
		$clean = (string) preg_replace(
			'/[^\p{L}\p{N}\p{M}\p{Zs}\.\_\-\(\)\[\]\{\}\+]+/u',
			'_',
			$base
		);

		// Colapsa espacios Unicode a un solo espacio y subrayados repetidos
		$clean = (string) preg_replace('/[\p{Zs}]+/u', ' ', $clean);
		$clean = (string) preg_replace('/_{2,}/', '_', $clean);
		$clean = trim($clean);

		// Evita nombres reservados Windows
		if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i', $clean) === 1) {
			$clean = '_' . $clean;
		}

		// Quita punto o espacio final (inválido en Windows)
		$clean = rtrim($clean, ". ");

		// Longitud máxima razonable
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($clean, 'UTF-8') > 200) {
				$clean = mb_substr($clean, 0, 200, 'UTF-8');
			}
		} else {
			if (strlen($clean) > 200) {
				$clean = substr($clean, 0, 200);
			}
		}

		return $clean !== '' ? $clean : '';
	}

	/**
	 * Valida un memory_limit tipo "512M" y lo ajusta a límites seguros si es necesario.
	 */
	private function sanitizeMemoryLimit(string $value, string $fallback = '512M'): string
	{
		// Solo sufijo M por simplicidad/consistencia
		if (preg_match('/^(?<num>\d{2,5})M$/', $value, $m) === 1) {
			$n = (int) $m['num'];
			// clamp entre 128M y 16384M (16G) para evitar valores ridículos
			$n = max(128, min(16384, $n));
			return $n . 'M';
		}
		return $fallback;
	}
	
	/**
     * When shutting down this class always close any open log files
     *
     *
     * @return  void 
     *     
     */
    public function __destruct()
    {
        $this->closeLogSCP();
    }

    protected function populateState()
    {
        // Inicializamos las variables
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app        = Factory::getApplication();
		
		// This is needed to avoid errors getting the file from cli
		if ( $app instanceof \Joomla\CMS\Application\CMSWebApplicationInterface ) {
			$search = $app->getUserStateFromRequest('filter.filemanager_search', 'filter_filemanager_search');
			$this->setState('filter.filemanager_search', $search);
			$filemanager_kind = $app->getUserStateFromRequest('filter.filemanager_kind', 'filter_filemanager_kind');
			$this->setState('filter.filemanager_kind', $filemanager_kind);
			$filemanager_permissions_status = $app->getUserStateFromRequest('filter.filemanager_permissions_status', 'filter_filemanager_permissions_status');
			$this->setState('filter.filemanager_permissions_status', $filemanager_permissions_status);
			$filemanager_permissions_status = $app->getUserStateFromRequest('filter.filemanager_permissions_status', 'filter_filemanager_permissions_status');
		
			$fileintegrity_search = $app->getUserStateFromRequest('filter.fileintegrity_search', 'filter_fileintegrity_search');
			$this->setState('filter.fileintegrity_search', $fileintegrity_search);
			$fileintegrity_status = $app->getUserStateFromRequest('filter.fileintegrity_status', 'filter_fileintegrity_status');
			$this->setState('filter.fileintegrity_status', $fileintegrity_status);
		
			$malwarescan_search = $app->getUserStateFromRequest('filter.malwarescan_search', 'filter_malwarescan_search');
			$this->setState('filter.malwarescan_search', $malwarescan_search);
			$malwarescan_status = $app->getUserStateFromRequest('filter.malwarescan_status', 'filter_malwarescan_status');
			$this->setState('filter.malwarescan_status', $malwarescan_status);	
		       
			parent::populateState();
		}
    }
	
	/**
     * Función que obtiene todos los archivos de un directorio
     *
     * @param   string            										 $dir    				The path
	 * @param   string            										 $opcion   				The option (integrity, malwarescan, permissions)
	 * @param   array<array<string, bool|int|string>|string>      		 $files    				Files found
	 * @param   string            										 $include_exceptions   	Include exceptions in the database or not
	 * @param   array<string>    										 $excludedFiles    		Include files set as exceptions or not
	 * @param   array<string>           								 $extensions_excluded   The excluded exceptions
     *
     * @return  array<string>|null
     *     
     */
	function get_file_list_recursively($dir, $opcion, &$files, $include_exceptions, $excludedFiles, $extensions_excluded=null)
	{
		$files_found = array();
		$exclude = array();
				
		/** @var \Joomla\CMS\Application\CMSApplication $app */	
		$app       = Factory::getApplication();
		$lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
		
		if (!$include_exceptions) {
			$text_for_log_exceptions = "Excluded files/folders - WILL NOT BE STORED IN DATABASE";		
		} else {
			$text_for_log_exceptions = "Excluded files/folders - WILL BE STORED IN DATABASE";
		}
		
		if ($opcion == "integrity")
		{
			$this->setCampoFilemanager("files_scanned_integrity", 30);			
		}
		
		$this->write_log("****** " . $text_for_log_exceptions . " ******");
		
		 /* Dejamos sin efecto el tiempo máximo de ejecución del script. Esto es necesario cuando existen miles de archivos a escanear */
		set_time_limit(0);
		
		foreach($excludedFiles as $exception)
		{
			if ( !strstr($exception,JPATH_ROOT) )
			{
				$exception = JPATH_ROOT . $exception;				
			}
			
			$exclude[] = $exception;			
			
			switch ($opcion)
			{
				case "permissions":
					if ($include_exceptions) {
						$permissions = "Not calculated";						
						$files[] = array(
							'path'      => $exception,                            
							'kind'    => $lang->_('COM_SECURITYCHECKPRO_FILEMANAGER_FILE'),
							'permissions' => $permissions,
							'last_modified' => date('Y-m-d H:i:s', filemtime($exception)),
							'safe' => 2
						);
					}
					break;
				case "integrity":
					$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_IN_EXCEPTIONS_LIST');
					$hash_actual = "Not calculated";					
					if ($include_exceptions) {
						$files[] = array(
							'path'      => $exception,                            
							'hash' => $hash_actual,                            
							'notes' => $texto_notes,
							'new_file' => 0,
							'safe_integrity' => 2
						); 
					}
					break;
				case "malwarescan":
					$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_IN_EXCEPTIONS_LIST');
					$not_calculated = "Not calculated";
					$safe_malwarescan = (int) 2;
					$malware_type = '';
                    $malware_description = '';
                    $malware_code = '';
                    $malware_alert_level = '';
					$quarantined_file_name = '';
                    $moved = 0;
					$size = '';
					$last_modified = '';
						   
					if ($include_exceptions) {
						if (is_file($exception)) {	
							$size = filesize($exception);
							$last_modified = date('Y-m-d H:i:s', filemtime($exception));
						} 
						$files[] = array(
							'path'      => $exception,
							'size'      => $size,
							'last_modified' => $last_modified,
							'malware_type' => $malware_type,
							'malware_description' => $malware_description,
							'malware_code' => $malware_code,
							'malware_alert_level'    => $malware_alert_level,
							'safe_malwarescan' => $safe_malwarescan,
							'sha1_value' => $not_calculated,
							'data_id' => '',
							'rest_ip' => '',
							'online_check' => 200,
							'moved_to_quarantine' => $moved,
							'quarantined_file_name'    =>    $quarantined_file_name
                           );
					}
					break;
				case "malwarescan_modified":
					$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_IN_EXCEPTIONS_LIST');
					$not_calculated = "Not calculated";
					$safe_malwarescan = (int) 2;
					$malware_type = '';
                    $malware_description = '';
                    $malware_code = '';
                    $malware_alert_level = '';
					$quarantined_file_name = '';
                    $moved = 0;					
					$size = '';
					$last_modified = '';
						   
					if ($include_exceptions) {
						if (is_file($exception)) {	
							$size = filesize($exception);
							$last_modified = date('Y-m-d H:i:s', filemtime($exception));
						} 
						$files[] = array(
							'path'      => $exception,
							'size'      => $size,
							'last_modified' => $last_modified,
							'malware_type' => $malware_type,
							'malware_description' => $malware_description,
							'malware_code' => $malware_code,
							'malware_alert_level'    => $malware_alert_level,
							'safe_malwarescan' => $safe_malwarescan,
							'sha1_value' => $not_calculated,
							'data_id' => '',
							'rest_ip' => '',
							'online_check' => 200,
							'moved_to_quarantine' => $moved,
							'quarantined_file_name'    =>    $quarantined_file_name
                           );
					}
					break;
					
			}
			$this->write_log("FILE: " . $exception . " -- In exception list");
		}
		
		$this->write_log("****** End Excluded files/folders ******");
						
		
		/**
		 * @param SplFileInfo $file
		 * @param mixed $key
		 * @param RecursiveCallbackFilterIterator $iterator
		 * @return bool True if you need to recurse or if the item is acceptable
		 */
		$filter = function ($file, $key, $iterator) use ($exclude) {
			$path = $file->getPathname();
			if ( $iterator->hasChildren() ) {
				// Excluimos los directorios que estén en el array "exclude", aquellos que tengan un . en alguno de sus directorios (por ejemplo, /www/.git/) y aquellos que contengan /cache/ (jch_optimize, 4seo...)
				if ( (!in_array($path, $exclude)) && (!preg_match("/\/[.]\w+\//i", $path)) && (!preg_match("/\/cache\//i", $path)) )
				{				
					return true;
				} 	
			}
			return $file->isFile();
		};

		$innerIterator = new \RecursiveDirectoryIterator(
			$dir,
			\RecursiveDirectoryIterator::SKIP_DOTS
		);
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveCallbackFilterIterator($innerIterator, $filter)
		);
		
		// Obtenemos el algoritmo con el que crearemos el valor hash de los ficheros (extraido del fichero de configuración)
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $hash_alg = $params->get('file_integrity_hash_alg', 'SHA1');
        // Obtenemos el algoritmo con el que se ha calculado el hash de los ficheros almacenados en la BBDD (extraido de la tabla '#__securitycheckpro_file_manager')
        $hash_alg_db = $this->GetCampoFilemanager('hash_alg');
		/* Comparamos los dos valores anteriores para ver si se ha cambiado o no estaba establecido el algoritmo con el que se calcula el hash. En ese caso debemos volver a almacenar los valores obtenidos para cada fichero chequeado */
        if ((is_null($hash_alg_db)) || ($hash_alg != $hash_alg_db)) {
            $hash_alg_has_changed = true;
			$hash_alg_db = $hash_alg;
            $this->setCampoFilemanager('hash_alg', $hash_alg);
        }
		
		if ($opcion == "integrity")
		{
			$this->setCampoFilemanager("files_scanned_integrity", 60);			
		}
		
		//Max time used to get the hash of a file
		$max_time = 0;
		$max_time_filename = "";
						
		foreach ($iterator as $pathname => $fileInfo) {
			
			if (!$fileInfo instanceof \SplFileInfo) {
				// Defensa extra por si alguien cambia el iterador en el futuro
				continue;
			}
			
			// La ruta absoluta y el nombre de archivo
			$pathname = $fileInfo->getPathname();          // string
			$file = strtolower($fileInfo->getBasename());          // p.ej. "index.php"

			// Extensión robusta (sin el punto); vacía si no hay extensión
			// En PHP 8+, SplFileInfo::getExtension() es lo más limpio:
			$extension = strtolower($fileInfo->getExtension()); // "" si no hay
					
			$hash_actual = '';					
								
			// Excluimos los archivos que empiezan por . (.htaccess, .htpasswd...), los archivos tipo 'shCacheContent.2b10d384e20f6e5b7596256d22339488.shlock' (pero sí view.html.php) y los archivos explicitamente en la lista de excepciones
			if ( (!preg_match("/\/[.]\w+/i", $file)) && (!preg_match("/([.][^php]\w+){2,}/i", $file)) && (!in_array($pathname, $exclude)) )			
			{			
				
				// Excluimos los archivos cuya extensión haya sido incluida en la lista de extensiones a ignorar
				if ( !empty($extensions_excluded) && (!empty($extension)) )
				{
					if ( !in_array($extension, $extensions_excluded) )
					{						
						if ($opcion == "integrity") 
						{
							$timestamp = $this->get_Joomla_timestamp();
							$datetime1 = new \DateTime($timestamp);//start time
				
							$this->files_scanned_integrity++;
							if (!file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name)) {
								$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');
								$new_file = (int) 1;
								// Lo marcamos con integridad correcta porque es el primer escaneo
								$safe_integrity = 1; 
							} else {
								$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');
								$new_file = (int) 0;
								// Lo marcamos con integridad correcta
								$safe_integrity = 1; 
							}
							switch ($hash_alg_db)
							{
								case "SHA1":
									$hash_actual = hash_file("sha1",$pathname);
									break;
								case "MD5":
									$hash_actual = hash_file("md5",$pathname);
									break;
							}

							$this->write_log("FILE: " . $pathname);
								
							$files_found[] = array(
								'path'      => $pathname,                            
								'hash' => $hash_actual,                            
								'notes' => $texto_notes,
								'new_file' => $new_file,
								'safe_integrity' => $safe_integrity
							);
							
							$timestamp = $this->get_Joomla_timestamp();
							$datetime2 = new \DateTime($timestamp);//end time
							try {								
								$interval = $datetime1->diff($datetime2);								
								(int) $interval_in_seconds = $interval->format('%s');
							} catch (\Throwable $e)
							{
								(int) $interval_in_seconds =  0;
							}
							
							
							if ($interval_in_seconds >= $max_time) {
								$max_time = $interval_in_seconds;								
								$max_time_filename = $pathname;
							}
							
						} else {
							$files_found[] = $pathname;
						}
					}					
				} else {
					if ($opcion == "integrity") 
					{
						$timestamp = $this->get_Joomla_timestamp();
						$datetime1 = new \DateTime($timestamp);//start time
							
						$this->files_scanned_integrity++;
						if (!file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name)) {
							$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');
							$new_file = (int) 1;
							// Lo marcamos con integridad correcta porque es el primer escaneo
							$safe_integrity = 1; 
						} else {
							$texto_notes = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');
							$new_file = (int) 0;
							// Lo marcamos con integridad correcta
							$safe_integrity = 1; 
						}
						switch ($hash_alg_db)
						{
							case "SHA1":
								$hash_actual = hash_file("sha1",$pathname);
								break;
							case "MD5":
								$hash_actual = hash_file("md5",$pathname);
								break;
						}

						$this->write_log("FILE: " . $pathname);
							
						$files_found[] = array(
							'path'      => $pathname,                            
							'hash' => $hash_actual,                            
							'notes' => $texto_notes,
							'new_file' => $new_file,
							'safe_integrity' => $safe_integrity
						);
						
						$timestamp = $this->get_Joomla_timestamp();
						$datetime2 = new \DateTime($timestamp);//end time
						try {								
							$interval = $datetime1->diff($datetime2);								
							(int) $interval_in_seconds = $interval->format('%s');
						} catch (\Throwable $e)
						{
							(int) $interval_in_seconds =  0;
						}
							
						if ($interval_in_seconds >= $max_time) {
							$max_time = $interval_in_seconds;								
							$max_time_filename = $pathname;
						}
						
					} else {
						$files_found[] = $pathname;
					}
				}

				if ( in_array($extension, $this->excludedExtensions) )
				{
					$this->non_executable_files++;
				} else {
					$this->executable_files++;
				}	
			}
		}
		sort($files_found);
				
		if ($opcion == "integrity") 
		{
			// Casi hemos acabado. Establecemos un valor cercano al 100
			$this->setCampoFilemanager("files_scanned_integrity", 95);
			
			if (is_object($max_time)) {
				$max_time = 0;
			}
						
			$scan_info['max_time'] = $max_time;
			$scan_info['max_time_filename'] = $max_time_filename;
			$scan_info['executable_files'] = $this->executable_files;
			$scan_info['non_executable_files'] = $this->non_executable_files;

			$this->last_scan_info = $scan_info;
			
		}	
		
		return $files_found;		
	}
	
	/**
	 * @param non-empty-string $ext  Extensión en minúsculas, sin punto.
	 */
	private function isImageExtension(string $ext): bool
	{
		// Tratamos svg como imagen		
		return \in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'ico', 'svg'], true);
	}
	

    /**
	 * Obtiene y procesa archivos del sitio de forma segura.
	 *
	 * @param non-empty-string|null $root                Ruta base a escanear (si null/empty => JPATH_ROOT)
	 * @param bool                  $includeExceptions   Incluir excepciones en el listado recursivo
	 * @param 'integrity'|'malwarescan'|'malwarescan_modified'|'permissions' $opcion
	 * @return void
	 */
	public function getFiles(?string $root, bool $includeExceptions, string $opcion): void
	{
		// --- Validación temprana de $opcion ---
		$allowed = ['integrity', 'malwarescan', 'malwarescan_modified', 'permissions'];
		if (!in_array($opcion, $allowed, true)) {
			$this->write_log("Invalid option '{$opcion}' in getFiles", "ERROR");
			return;
		}

		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		$lang = $app->getLanguage();
		$lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

		$isCli = $app instanceof ConsoleApplication;

		// Evita Notice en CLI
		if (!$isCli) {
			$app->setUserState("time_taken_set", "");
			$app->setUserState("executable_files", 0);
			$app->setUserState("non_executable_files", 0);
		}

		// --- Normalización y confinamiento de la ruta raíz ---
		$root = ($root !== null && $root !== '') ? $root : JPATH_ROOT;
		$root = Path::clean($root);

		// Garantiza que $root está bajo JPATH_ROOT
		try {
			Path::check($root, JPATH_ROOT);
		} catch (\Throwable $e) {
			// Fallback estricto a JPATH_ROOT si hay intento de escape
			$this->write_log("Root path '{$root}' rejected by Path::check: " . $e->getMessage(), "WARNING");
			$root = JPATH_ROOT;
		}

		// Si por cualquier razón sigue vacío, resolvemos de forma segura
		if ($root === '' || !is_dir($root)) {
			$root = JPATH_ROOT;
		}

		// --- Parámetros de componente y variables auxiliares ---
		$cparams = ComponentHelper::getParams('com_securitycheckpro');
		$scanExecutablesOnly = (int) $cparams->get('scan_executables_only', 0) === 1;

		/** @var array<int, string> $excludedFiles */
		$excludedFiles = [];
		/** @var array<int, string> $exceptionsToSend */
		$exceptionsToSend = [];

		// Selector de excepciones por modo
		switch ($opcion) {
			case 'permissions':
				$exceptionsToSend = $this->skipDirsPermissions ?? [];
				break;
			case 'integrity':
				$exceptionsToSend = $this->skipDirsIntegrity ?? [];
				break;
			case 'malwarescan':
				$exceptionsToSend = $this->use_filemanager_exceptions ? ($this->skipDirsIntegrity ?? []) : ($this->skipDirsMalwarescan ?? []);
				break;
			case 'malwarescan_modified':
				// Se gestiona más abajo
				break;
		}

		// --- Construcción de la lista base de archivos según modo ---
		/** @var array<int, string|array<string, mixed>> $filesName */
		$filesName = [];
		
		// Contenedor de resultados por modo
		/** @var array<int, array<string, mixed>> $files */
		$files = [];


		if ($opcion === 'malwarescan_modified') {
			$filesName = $this->loadModifiedFiles();
			if (!is_array($filesName)) {
				$filesName = [];
			}
		} else {
			if ($scanExecutablesOnly) {
				/** @var array<int, string> $excludedExt */
				$excludedExt = $this->excludedExtensions ?? [];
				$filesName = $this->get_file_list_recursively($root, $opcion, $files, $includeExceptions, $exceptionsToSend, $excludedExt);
			} else {
				$filesName = $this->get_file_list_recursively($root, $opcion, $files, $includeExceptions, $exceptionsToSend);
			}
		}

		// En modos no-integrity normalizamos separadores a DIRECTORY_SEPARATOR
		if ($opcion !== 'integrity' && !empty($filesName)) {
			$filesName = array_map(
				static function ($f) {
					return is_string($f)
						? str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $f)
						: $f;
				},
				$filesName
			);
		}
		
		// Un set_time_limit global (no dentro de bucles) para procesos largos
		if (\function_exists('set_time_limit')) {
			@set_time_limit(0);
		}

		// ======================
		// MODO: PERMISSIONS
		// ======================
		if ($opcion === 'permissions') {
			$total = \count($filesName);
			$this->files_scanned += $total;

			if ($total > 0) {
				foreach ($filesName as $file) {
					if (!is_string($file)) {
						continue;
					}
					$this->files_processed_permissions++;
					$percent = (int) round(($this->files_processed_permissions / max(1, $this->files_scanned)) * 100);

					if (!$isCli) {
						if ((($percent - (int) $this->last_percent_permissions) >= 10) && $percent < 100) {
							$this->setCampoFilemanager("files_scanned", $percent);
							$this->last_percent_permissions = $percent;
						} elseif ($percent === 100) {
							$this->task_completed = true;
						}
					}

					$this->write_log("FILE: {$file}");

					if (!is_file($file) || !is_readable($file)) {
						// Saltamos archivos ilegibles, evitando warnings
						continue;
					}

					$safe = 1;
					$perms = $this->file_perms($file);

					if ($this->isTooPermissive($file)) {
						$safe = 0;
						$this->files_with_incorrect_permissions++;
					}

					$mtime = \filemtime($file);
					$files[] = [
						'path'          => $file,
						'kind'          => $lang->_('COM_SECURITYCHECKPRO_FILEMANAGER_FILE'),
						'permissions'   => $perms,
						'last_modified' => $mtime !== false ? \date('c', $mtime) : null,
						'safe'          => $safe,
					];
				}
			}

			if (!empty($files)) {
				$this->Stack = \array_merge($this->Stack, $files);
			}

			return;
		}

		// ======================
		// MODO: INTEGRITY
		// ======================
		if ($opcion === 'integrity') {
			$hashAlgHasChanged = false; // mantenido por compatibilidad
			$textoNotes        = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');
			$newFileDefault    = 0;

			// Cargar estado anterior desde fichero, si existe
			$prevStackRaw = '';
			if (!empty($this->fileintegrity_name)) {
				$prevPath = $this->folder_path . DIRECTORY_SEPARATOR . $this->fileintegrity_name;
				if (is_file($prevPath) && is_readable($prevPath)) {
					$prevStackRaw = (string) \file_get_contents($prevPath);
					$prevStackRaw = str_replace("#<?php die('Forbidden.'); ?>", '', $prevStackRaw);
				}
			}

			// Cargar resumen desde BBDD
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->select($db->quoteName('storage_value'))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('fileintegrity_resume'));
			$db->setQuery($query);
			$stackResumeJson = $db->loadResult();

			// Decodificación segura
			/** @var array<string, mixed>|null $prevStack */
			$prevStack = null;
			if ($prevStackRaw !== '') {
				$prevStack = json_decode($prevStackRaw, true);
				if (!is_array($prevStack) || !isset($prevStack['files_folders']) || !is_array($prevStack['files_folders'])) {
					$prevStack = null;
				}
			}

			/** @var array<string, mixed>|null $stackResume */
			$stackResume = null;
			if (\is_string($stackResumeJson) && $stackResumeJson !== '') {
				$stackResume = json_decode($stackResumeJson, true);
				if (!is_array($stackResume)) {
					$stackResume = null;
				}
			}

			if ($stackResume && isset($stackResume['files_with_incorrect_integrity']) && \is_int($stackResume['files_with_incorrect_integrity'])) {
				$this->files_with_incorrect_integrity = $stackResume['files_with_incorrect_integrity'];
			}

			$this->setCampoFilemanager('estado_integrity', 'CHECKING_DELETED_FILES');

			if ($prevStack === null || empty($prevStack['files_folders'])) {
				// Primera ejecución o sin datos válidos previos
				/** @var array<int, array<string, mixed>> $filesNameTyped */
				$filesNameTyped = is_array($filesName) ? $filesName : [];
				$this->Stack_Integrity = \array_merge($filesNameTyped, $files);
			} else {
				/** @var array<int, array<string, mixed>> $prevFiles */
				$prevFiles = $prevStack['files_folders'];

				// Filtramos entradas previas con hash calculado
				$prevWithHash = array_values(array_filter($prevFiles, static function (array $e): bool {
					return isset($e['hash']) && $e['hash'] !== "Not calculated";
				}));

				// Extraer listas auxiliares
				$prevUnsafePaths = array_map(
					static fn(array $e): string => (string) $e['path'],
					array_values(array_filter($prevWithHash, static function (array $e): bool {
						return isset($e['safe_integrity']) && (int) $e['safe_integrity'] !== 1;
					}))
				);

				$prevHashes = array_map(
					static fn(array $e): string => (string) $e['hash'],
					$prevWithHash
				);

				$currHashes = array_map(
					static fn(array $e): string => (string) ($e['hash'] ?? ''),
					is_array($filesName) ? $filesName : []
				);

				// Diferencias de hash => nuevos/modificados
				$diffHashes = \array_diff($currHashes, $prevHashes);

				$this->write_log("------- Begin New/modified files --------");

				foreach ($diffHashes as $changedHash) {
					$keyCurr = array_search($changedHash, array_column($filesName, 'hash'), true);
					if ($keyCurr === false) {
						continue;
					}

					$pathCurr = (string) $filesName[$keyCurr]['path'];
					$keyPrev  = array_search($pathCurr, array_column($prevFiles, 'path'), true);

					if ($keyPrev !== false) {
						// Modificado
						$filesName[$keyCurr]['notes'] = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_HASH_CHANGED');
						$filesName[$keyCurr]['safe_integrity'] = 0;
						$this->write_log("FILE: {$pathCurr} -- Hash changed");
					} else {
						// Nuevo
						$filesName[$keyCurr]['notes'] = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_NEW_FILE');
						$filesName[$keyCurr]['new_file'] = 1;
						$filesName[$keyCurr]['safe_integrity'] = 0;
						$this->write_log("FILE: {$pathCurr} -- New file");
					}
				}

				foreach ($prevUnsafePaths as $path) {
					$keyCurr = array_search($path, array_column($filesName, 'path'), true);
					if ($keyCurr !== false) {
						$filesName[$keyCurr]['notes'] = $lang->_('COM_SECURITYCHECKPRO_FILEINTEGRITY_HASH_CHANGED');
						$filesName[$keyCurr]['safe_integrity'] = 0;
					}
				}

				$this->write_log("------- End New/modified files --------");

				$this->Stack_Integrity = \array_merge($filesName, $files);

				// Recuenta inseguros
				$this->files_with_incorrect_integrity = \count(array_filter(
					$this->Stack_Integrity,
					static fn(array $e): bool => isset($e['safe_integrity']) && (int) $e['safe_integrity'] === 0
				));
			}

			$this->setCampoFilemanager("files_scanned_integrity", 100);
			$this->task_completed = true;
			return;
		}

		// ======================
		// MODO: MALWARESCAN / MALWARESCAN_MODIFIED
		// ======================
		// Carpeta de cuarentena (bajo admin del componente)
		$quarantinePath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans' . DIRECTORY_SEPARATOR . 'quarantine';

		$this->files_scanned_malwarescan += \count($filesName);		
		
		// Extensiones a analizar
		$fileExtList = str_replace(' ', '', (string) ($this->fileExt ?? ''));
		$scanExt = $fileExtList !== '' ? explode(',', $fileExtList) : [];

		// Timeline (edad máxima de modificación, en días)
		$timelineDays = (int) $cparams->get('timeline', 7);

		// Ruta de escaneos
		$this->folder_path = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans' . DIRECTORY_SEPARATOR;

		// Cargar nombre del escaneo anterior desde BBDD
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote('malwarescan_resume'));
		$db->setQuery($query);
		$stackMalwareJson = $db->loadResult();

		$malwarescan_name = '';
		if (\is_string($stackMalwareJson) && $stackMalwareJson !== '') {
			$prev = json_decode($stackMalwareJson, true);
			if (is_array($prev) && isset($prev['filename']) && \is_string($prev['filename'])) {
				$malwarescan_name = $prev['filename'];
			}
		}

		// Carga de escaneo previo (si se pide el nombre correcto)
		$filteredQuarantined = [];
		if ($malwarescan_name !== '') {
			$prevScanPath = $this->folder_path . DIRECTORY_SEPARATOR . $malwarescan_name;
			if (is_file($prevScanPath) && is_readable($prevScanPath)) {
				$raw = (string) \file_get_contents($prevScanPath);
				$raw = str_replace("#<?php die('Forbidden.'); ?>", '', $raw);
				$prevScan = json_decode($raw, true);

				if (is_array($prevScan) && isset($prevScan['files_folders']) && is_array($prevScan['files_folders'])) {
					// Mantén los ya movidos a cuarentena
					$filteredQuarantined = array_values(array_filter(
						$prevScan['files_folders'],
						static fn(array $e): bool => isset($e['moved_to_quarantine']) && (int) $e['moved_to_quarantine'] === 1
					));
				}
			}
		}

		if (!empty($filteredQuarantined)) {
			$this->Stack_malwarescan = \array_merge($this->Stack_malwarescan, $filteredQuarantined);
		}

		if (!empty($filesName)) {
			foreach ($filesName as $file) {
				if (!\is_string($file)) {
					continue;
				}

				$this->write_log("FILE: {$file}");

				$this->files_processed_malwaresecan++;
				$percent = (int) round(($this->files_processed_malwaresecan / max(1, $this->files_scanned_malwarescan)) * 100);

				if (!$isCli) {
					if ((($percent - (int) $this->last_percent_malwarescan) >= 10) && $percent < 100) {
						$this->setCampoFilemanager("files_scanned_malwarescan", $percent);
						$this->last_percent_malwarescan = $percent;
					} elseif ($percent === 100) {
						$this->task_completed = true;
					}
				}

				if (!is_file($file) || !is_readable($file)) {
					continue;
				}

				// Inicializa estado por archivo
				$safeMs = 1;
				$malType = '';
				$malDesc = '';
				$malCode = '';
				$malLevel = '';

				$mtime   = \filemtime($file);
				$daysOld = ($mtime !== false) ? (int) floor((\time() - $mtime) / 86400) : PHP_INT_MAX;

				if ($daysOld <= $timelineDays) {
					// --- Configuración ---
					$basename = basename($file);
					$parts    = explode('.', $basename);
					$numExt   = \count($parts);

					if ($numExt >= 3) {
						// Últimas tres partes para comprobar patrón
						$last3 = array_map('strtolower', \array_slice($parts, -3));
						$last2 = array_map('strtolower', \array_slice($parts, -2));

						$last   = $last2[1] ?? '';
						$middle = $last2[0] ?? '';
						$preLast= $last3[0] ?? '';

						// Caso A: *.php.xxx
						if ($middle === 'php' && $last !== '') {
							$safeMs   = 0;
							$malType  = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_MULTIPLE_EXTENSIONS');
							$malDesc  = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION') . implode('.', $last2);
							$malCode  = $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
							$malLevel = '0';
							$this->suspicious_files++;
							$this->write_log("FILE: {$file} has suspicious pattern .php.xxx");
						}
						// Caso B: *.php.xxx.yyy
						elseif ($preLast === 'php' && $last3[1] !== '' && $last3[2] !== '') {
							$safeMs   = 0;
							$malType  = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_MULTIPLE_EXTENSIONS');
							$malDesc  = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_EXTENSION') . implode('.', $last3);
							$malCode  = $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
							$malLevel = '0';
							$this->suspicious_files++;
							$this->write_log("FILE: {$file} has suspicious pattern .php.xxx.yyy");
						}
					}
					
					$ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
					$isImage = $this->isImageExtension($ext);					

					// Análisis de contenido:
					if (!$isImage && \in_array($ext, $scanExt, true) && \filesize($file) > 0) {						
						$resultado = $this->scan_file($file);
						if (isset($resultado[0][0]) && $resultado[0][0]) {
							$this->write_log("FILE: {$file} has malicious content");
							$safeMs   = 0;
							$malType  = (string) ($resultado[0][1] ?? '');
							$malDesc  = (string) ($resultado[0][2] ?? '');
							$malCode  = (string) ($resultado[0][3] ?? '');
							$malLevel = (string) ($resultado[0][4] ?? '');
							$this->suspicious_files++;
						}
					} else {
						$this->write_log("File not analysed because it is not included in the list of extensions to be analysed.");
					}
					
				} else {
					$this->write_log("File not analyzed because its modification date exceeds the one set in the timeline.");
				}

				// Si hay algo sospechoso, registramos e intentamos cuarentena si aplica
				if ($safeMs !== 1) {
					$original = $file;
					$quarantined = '';
					$moved = 0;
					$toMove = true;

					$moveToQuarantine = (int) $cparams->get('move_to_quarantine', 0) === 1;

					// Nivel alto => mover
					if ($moveToQuarantine && $malLevel === '0') {
						$lastPart = explode(DIRECTORY_SEPARATOR, $file);
						$target = $quarantinePath . DIRECTORY_SEPARATOR . end($lastPart);

						if (is_file($target)) {
							// Evita overwrite si ya hay un registro previo con ese path
							$idxPrev = array_search($original, array_column($filteredQuarantined, 'path'), true);
							if (\is_int($idxPrev) && $idxPrev >= 0) {
								$toMove = false;
							} else {
								// Nombre temporal alternativo para colisión
								$target .= '1';
							}
						}

						if ($toMove) {
							// Intento de movimiento atómico
							try {
								if (!is_dir($quarantinePath)) {
									@mkdir($quarantinePath, 0755, true);
								}
								if (File::move($file, $target)) {
									$moved = 1;
									$file = $target;
									$quarantined = $target;
									$safeMs = 3; // marcamos como "en cuarentena"
									$this->write_log("FILE: {$original} has been moved to quarantine folder");
								}
							} catch (\Throwable $e) {
								$this->write_log("Move to quarantine failed: {$original} => {$e->getMessage()}", "ERROR");
								// Si falla el movimiento, mantenemos el original
								$file = $original;
							}
						}
					}

					$mtime2 = \filemtime($file);
					$size   = \filesize($file);

					$files[] = [
						'path'                 => $original,
						'size'                 => ($size !== false ? $size : null),
						'last_modified'        => $mtime2 !== false ? \date('c', $mtime2) : null,
						'malware_type'         => $malType,
						'malware_description'  => $malDesc,
						'malware_code'         => $malCode,
						'malware_alert_level'  => $malLevel,
						'safe_malwarescan'     => $safeMs,
						'sha1_value'           => (\is_file($file) && \is_readable($file) ? \hash_file('sha1', $file) : null),
						'data_id'              => '',
						'rest_ip'              => '',
						'online_check'         => 200,
						'moved_to_quarantine'  => $moved,
						'quarantined_file_name'=> $quarantined,
					];
				} 
			}
		}

		if (!empty($files)) {
			$this->Stack_malwarescan = \array_merge($this->Stack_malwarescan, $files);
		}
	}

    /**
	 * Obtiene el nombre del fichero de log desde la tabla de almacenamiento
	 * y lo asigna a la propiedad correspondiente. Si $devolver es true,
	 * retorna el nombre encontrado; en otro caso null.
	 *
	 * @param string $opcion   'filepermissions_log'|'fileintegrity_log'|'filemalware_log'|'controlcenter_log'
	 * @param bool   $devolver Si true, retorna el nombre en vez de solo asignarlo
	 * @return string|null
	 */
	public function get_log_filename(string $opcion, bool $devolver = false): ?string
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$query = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote($opcion));

		$db->setQuery($query);
		/** @var string|null $tempName */
		$tempName = $db->loadResult();

		if ($tempName === null || $tempName === '') {
			return null;
		}

		/** @var array<string,mixed>|null $decoded */
		$decoded = json_decode($tempName, true);
		if (!is_array($decoded) || empty($decoded['filename'])) {
			return null;
		}

		$filename = (string) $decoded['filename'];

		switch ($opcion) {
			case 'filepermissions_log':
				$this->filepermissions_log_name = $filename;
				break;
			case 'fileintegrity_log':
				$this->fileintegrity_log_name = $filename;
				break;
			case 'filemalware_log':
				$this->filemalware_log_name = $filename;
				break;
			case 'controlcenter_log':
				$this->controlcenter_log_name = $filename;
				break;
			case 'filemanager_resume':
				$this->filemanager_name = $filename;
				break;
			case 'fileintegrity_resume':
				$this->fileintegrity_name = $filename;
				break;
			case 'malwarescan_resume':
				$this->malwarescan_name = $filename;
				break;
			default:
				return null;
		}

		return $devolver ? $filename : null;
	}

    /**
	 * Obtiene todos los directorios del sitio de forma segura.
	 *
	 * @param string $root                Ruta base (relativa o absoluta)
	 * @param bool   $include_exceptions  Incluir también los directorios marcados como excepción
	 * @param string $opcion              'integrity' | 'malwarescan' | 'permissions'
	 */
	public function getDirectories(string $root, bool $include_exceptions, string $opcion): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app  = Factory::getApplication();
		$lang = $app->getLanguage();
		$lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

		// 1) Normalizar entrada y absolutizar bajo JPATH_ROOT si viene relativa
		$root = trim($root) !== '' ? $root : JPATH_ROOT;
		$root = Path::clean($root);

		if (!$this->isAbsolutePath($root)) {
			$root = Path::clean(JPATH_ROOT . DIRECTORY_SEPARATOR . $root);
		}

		// 2) Canonicalizar y asegurar que está bajo JPATH_ROOT
		$canonicalRoot = $this->canonical($root);
		$canonicalBase = $this->canonical(JPATH_ROOT);

		if ($canonicalRoot === null || $canonicalBase === null || !$this->isUnder($canonicalRoot, $canonicalBase)) {
			$this->write_log('getDirectories(): ruta fuera de JPATH_ROOT: ' . $root, 'ERROR');
			return;
		}

		// 3) Opciones válidas
		if (!in_array($opcion, ['integrity', 'malwarescan', 'permissions', 'malwarescan_modified'], true)) {
			$this->write_log('getDirectories(): opción no soportada: ' . $opcion, 'WARNING');
			return;
		}

		if ($opcion === 'integrity') {
			// No se calculan hashes en directorios
			return;
		}

		// 4) Preparar exclusiones normalizadas (prefijos absolutos canónicos)
		$excludeList = is_array($this->skipDirsPermissions) ? $this->skipDirsPermissions : [];
		$normalizedExcludes = $this->normalizeExcludes($excludeList);

		// 5) Listado de carpetas (recursivo, rutas completas)
		try {
			/** @var list<string> $folders_name */
			$folders_name = Folder::folders($canonicalRoot, '.', true, true, [] /*exclude*/, [] /*excludefilter*/);
		} catch (\Throwable $e) {
			$this->write_log('getDirectories(): error leyendo directorios: ' . $e->getMessage(), 'ERROR');
			return;
		}

		// 6) Filtrar symlinks y escapes de base
		$folders_name = array_values(array_filter(
			$folders_name,
			function (string $p) use ($canonicalBase): bool {
				$c = $this->canonical($p);
				if ($c === null) {
					return false;
				}
				if (is_link($p)) {
					return false;
				}
				return $this->isUnder($c, $canonicalBase);
			}
		));

		// 7) Estado y progreso
		$this->files_scanned = count($folders_name);
		if ($opcion === 'permissions') {
			$this->setCampoFilemanager('files_scanned', 0);
			$this->setCampoFilemanager('estado', 'IN_PROGRESS');
		} else if ($opcion === 'malwarescan') {
			$this->setCampoFilemanager('files_scanned_malwarescan', 0);
			$this->setCampoFilemanager('estado_malwarescan', 'IN_PROGRESS');
		}

		// 8) Procesar
		$folders = [];
		foreach ($folders_name as $folder) {
			try {
				$this->files_processed_permissions++;

				// Progreso cada 10% (sin pasar de 100)
				if ($this->files_scanned > 0) {
					$percent = (int) round(($this->files_processed_permissions / $this->files_scanned) * 100);
					$percent = max(0, min(100, $percent));
					if ($percent < 100 && ($percent - $this->last_percent_permissions) >= 10) {
						$this->setCampoFilemanager('files_scanned', $percent);
						$this->last_percent_permissions = $percent;
					} elseif ($percent >= 100) {
						$this->task_completed = true;
					}
				}

				// ¿Excepción?
				$isExcepted = $this->isExcepted($folder, $normalizedExcludes);
				$safe = $isExcepted ? 2 : 1;

				// Permisos y "demasiado permisivo"
				$permissions = $this->file_perms($folder);
				if ($this->isTooPermissive($folder)) {
					$safe = 0;
					$this->files_with_incorrect_permissions++;
				}

				// mtime seguro
				$mtime = @filemtime($folder);
				$lastModified = $mtime !== false ? (string) gmdate('Y-m-d H:i:s', $mtime) : '';

				// Incluir si:
				//  - es excepción y se pidió incluir excepciones, o
				//  - no es excepción
				if (($isExcepted && $include_exceptions) || !$isExcepted) {
					$folders[] = [
						'path'          => $folder,
						'kind'          => Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_DIRECTORY'),
						'permissions'   => $permissions,
						'last_modified' => $lastModified,
						'safe'          => $safe,
					];
					$this->write_log('FOLDER: ' . $folder);
				}
			} catch (\Throwable $e) {
				$this->write_log('getDirectories(): excepción en ' . $folder . ' -> ' . $e->getMessage(), 'ERROR');
				// Continuar
			}
		}

		if ($folders !== []) {
			/** @var array<int,array<string,mixed>> $folders */
			$this->Stack = array_merge($this->Stack, $folders);
		}
	}

	/**
	 * Compatibilidad J5/J6: comprobar si una ruta es absoluta sin Path::isAbsolute().
	 */
	private function isAbsolutePath(string $path): bool
	{
		if ($path === '') {
			return false;
		}

		if (DIRECTORY_SEPARATOR === '\\') {
			// Windows: C:\... o \\servidor\share
			return (bool) preg_match('#^[A-Z]:\\\\|^\\\\\\\\#i', $path);
		}

		// Unix-like: empieza por '/'
		return str_starts_with($path, '/');
	}

	/**
	 * Normaliza y resuelve una ruta; devuelve null si queda vacía.
	 */
	private function canonical(string $path): ?string
	{
		$clean = Path::clean($path);
		$real  = @realpath($clean);

		// Si no existe en disco, devolvemos versión "clean" absoluta (bajo JPATH_ROOT si era relativa)
		if ($real === false) {
			if (!$this->isAbsolutePath($clean)) {
				$clean = Path::clean(JPATH_ROOT . DIRECTORY_SEPARATOR . $clean);
			}
			return $clean !== '' ? $clean : null;
		}

		return Path::clean((string) $real);
	}

	/**
	 * True si $child está bajo (o igual que) $base.
	 */
	private function isUnder(string $child, string $base): bool
	{
		$base  = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$child = rtrim($child, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if (DIRECTORY_SEPARATOR === '\\') {
			$base  = strtolower($base);
			$child = strtolower($child);
		}

		return str_starts_with($child, $base);
	}

	/**
	 * Normaliza exclusiones a rutas canónicas absolutas únicas bajo JPATH_ROOT.
	 *
	 * @param  array<int,string> $excludes
	 * @return list<string>
	 */
	private function normalizeExcludes(array $excludes): array
	{
		$base = $this->canonical(JPATH_ROOT) ?? '';
		$set  = [];

		foreach ($excludes as $ex) {
			$ex = trim((string) $ex);
			if ($ex === '') {
				continue;
			}
			$abs = $ex;
			if (!$this->isAbsolutePath($abs)) {
				$abs = Path::clean($base . DIRECTORY_SEPARATOR . $abs);
			}
			$can = $this->canonical($abs);
			if ($can !== null && $this->isUnder($can, $base)) {
				$set[$can] = true;
			}
		}

		/** @var list<string> */
		return array_keys($set);
	}

	/**
	 * ¿La ruta cae bajo alguna exclusión?
	 *
	 * @param list<string> $normalizedExcludes
	 */
	private function isExcepted(string $path, array $normalizedExcludes): bool
	{
		$c = $this->canonical($path);
		if ($c === null) {
			return false;
		}
		foreach ($normalizedExcludes as $ex) {
			if ($this->isUnder($c, $ex)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Comprueba si $candidate está dentro de $baseDir (sin traversal).
	 * Resuelve realpath cuando es posible; si el candidato no existe aún, usa fallback seguro.
	 *
	 * @param  string $baseDir
	 * @param  string $candidate
	 * @return bool
	 */
	private function isPathInside(string $baseDir, string $candidate): bool
	{
		$baseClean = rtrim(Path::clean($baseDir), DIRECTORY_SEPARATOR);
		$candClean = Path::clean($candidate);

		if ($baseClean === '' || $candClean === '') {
			return false;
		}

		$baseReal = realpath($baseClean) ?: $baseClean;

		// Si el candidato no existe todavía (p.ej., vamos a crearlo), realpath devuelve false.
		$candReal = realpath($candClean);
		if ($candReal === false) {
			// Normaliza a absoluto de forma conservadora
			if (str_starts_with($candClean, DIRECTORY_SEPARATOR)) {
				$candReal = $candClean;
			} else {
				$candReal = $baseReal . DIRECTORY_SEPARATOR . ltrim($candClean, DIRECTORY_SEPARATOR);
			}
		}

		// Normaliza separadores
		$baseNorm = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
		$candNorm = str_replace('\\', '/', $candReal);

		// Windows: comparación case-insensitive
		if (DIRECTORY_SEPARATOR === '\\') {
			$baseNorm = strtolower($baseNorm);
			$candNorm = strtolower($candNorm);
		}

		return strncmp($candNorm, $baseNorm, strlen($baseNorm)) === 0;
	}

    /**
     * Serializa y guarda el stack según la opción pedida.
     *
     * @param  string $opcion 'permissions'|'integrity'|'malwarescan'|'malwarescan_modified'
     * @param  bool   $borrar
     */
    private function saveStack(string $opcion, bool $borrar = true): void
    {
        $allowed = ['permissions', 'integrity', 'malwarescan', 'malwarescan_modified'];
        if (!in_array($opcion, $allowed, true)) {
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
		
		// Obtenemos el nombre de los ficheros de logs
        $this->get_log_filename("filemanager_resume");
		$this->get_log_filename("fileintegrity_resume");
		$this->get_log_filename("malwarescan_resume");
        $this->get_log_filename("filepermissions_log");
        $this->get_log_filename("fileintegrity_log");
        $this->get_log_filename("filemalware_log");
		$this->get_log_filename("controlcenter_log");

        // --- Exentos (solo basenames, limpiando vacíos)
        $exentos = array_values(array_filter(array_map(
            static fn(string $s): string => basename($s),
            [
                'index.html',
                'web.config',
                '.htaccess',
                (string) ($this->filemanager_name ?? ''),
                (string) ($this->fileintegrity_name ?? ''),
                (string) ($this->malwarescan_name ?? ''),
                'error.php',
                'update_vuln_table.php',
                (string) ($this->filepermissions_log_name ?? ''),
                (string) ($this->fileintegrity_log_name ?? ''),
                (string) ($this->filemalware_log_name ?? ''),
                (string) ($this->controlcenter_log_name ?? ''),
            ]
        ), static fn(string $v): bool => $v !== ''));
		
		
        // Añade nombres de escaneos online
        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('filename'))
                ->from($db->quoteName('#__securitycheckpro_online_checks'));
            $db->setQuery($query);
            /** @var list<array{0:string}> $onlineRows */
            $onlineRows = (array) $db->loadRowList();
            foreach ($onlineRows as $row) {
                $bn = basename((string) ($row[0] ?? ''));
                if ($bn !== '') {
                    $exentos[] = $bn;
                }
            }
        } catch (\Throwable) {
            // opcional: log
        }

        $scanDir = Path::clean((string) $this->folder_path);
        if ($borrar && is_dir($scanDir)) {
            $this->purgeOldScanFiles($scanDir, array_values(array_unique($exentos)));
        }

        // --- Tiempo transcurrido
        $timestamp = (string) $this->get_Joomla_timestamp();
        $app       = Factory::getApplication();
        $timeTakenSet = ($app instanceof CMSApplication) ? (string) $app->getUserState('time_taken_set', '') : '';
		
        if ($timeTakenSet === '') {
            $scanStart = ($app instanceof CMSApplication)
                ? (string) $app->getUserState('scan_start_time', $timestamp)
                : $timestamp;
            try {
                $dt1 = new \DateTime($scanStart);
                $dt2 = new \DateTime($timestamp);
				$interval = $dt1->diff($dt2);
                $this->time_taken = $interval->format('%i ' . Text::_('COM_SECURITYCHECKPRO_MINUTES') . ' %s ' . Text::_('COM_SECURITYCHECKPRO_SECONDS'));
            } catch (\Throwable) {
                $this->time_taken = '0 ' . Text::_('COM_SECURITYCHECKPRO_MINUTES') . ' 0 ' . Text::_('COM_SECURITYCHECKPRO_SECONDS');
            }
        } else {
            $this->time_taken = $timeTakenSet;
        }
		
        // --- Ramas
        if ($opcion === 'permissions') {
            // Elimina anterior
            if (!empty($this->filemanager_name)) {
                $old = Path::clean($scanDir . DIRECTORY_SEPARATOR . $this->filemanager_name);
                if (is_file($old) && $this->isPathInside($scanDir, $old)) {
                    try { File::delete($old); } catch (\Throwable) {}
                }
            }

            $result = $this->writeScanJson($scanDir, ['files_folders' => (array) ($this->Stack ?? [])], 'error_permissions_scan.php');
            $this->Stack = [];

            // Limpia resumen anterior
            try {
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__securitycheckpro_storage'))
                    ->where($db->quoteName('storage_key') . ' = ' . $db->quote('filemanager_resume'));
                $db->setQuery($query)->execute();
            } catch (\Throwable) {}

            $resume = [
                'files_scanned'                    => (int) ($this->files_scanned ?? 0),
                'files_with_incorrect_permissions' => (int) ($this->files_with_incorrect_permissions ?? 0),
                'last_check'                       => $timestamp,
                'filename'                         => (string) ($result['filename'] ?? ''),
                'time_taken'                       => (string) ($this->time_taken ?? ''),
            ];

            try {
                $obj = (object) [
                    'storage_key'   => 'filemanager_resume',
                    'storage_value' => json_encode($resume, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
                $db->insertObject('#__securitycheckpro_storage', $obj);
            } catch (\Throwable $e) {
                $this->setCampoFilemanager('estado', 'DATABASE_ERROR');
                File::write(Path::clean($scanDir . DIRECTORY_SEPARATOR . 'error_permissions_scan.php'), $e->getMessage());
            }

            if (($this->task_completed ?? false) && $result['ok'] === true) {
                $this->setCampoFilemanager('estado', 'ENDED');
            }
            $this->setCampoFilemanager('files_scanned', 100);
            return;
        }

        if ($opcion === 'integrity') {
            if (!empty($this->fileintegrity_name)) {
                $old = Path::clean($scanDir . DIRECTORY_SEPARATOR . $this->fileintegrity_name);
                if (is_file($old) && $this->isPathInside($scanDir, $old)) {
                    try { File::delete($old); } catch (\Throwable) {}
                }
            }

            $result = $this->writeScanJson($scanDir, ['files_folders' => (array) ($this->Stack_Integrity ?? [])], 'error_integrity_scan.php');
            $this->Stack_Integrity = [];

            try {
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__securitycheckpro_storage'))
                    ->where($db->quoteName('storage_key') . ' = ' . $db->quote('fileintegrity_resume'));
                $db->setQuery($query)->execute();
            } catch (\Throwable) {}

            $resume = [
                'files_scanned_integrity'       => (int) ($this->files_scanned_integrity ?? 0),
                'files_with_incorrect_integrity'=> (int) ($this->files_with_incorrect_integrity ?? 0),
                'last_check_integrity'          => $timestamp,
                'filename'                      => (string) ($result['filename'] ?? ''),
                'time_taken'                    => (string) ($this->time_taken ?? ''),
                'last_scan_info'                => (array) ($this->last_scan_info ?? []),
            ];

            try {
                $storageValueJson = json_encode(
					$resume,
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
				);

				$obj = (object) [
					'storage_key'   => 'fileintegrity_resume',
					'storage_value' => $storageValueJson,
				];
                $db->insertObject('#__securitycheckpro_storage', $obj);
            } catch (\JsonException $je) {
				// registra el error para depurar
				File::write(Path::clean($scanDir . DIRECTORY_SEPARATOR . 'error_integrity_scan.php'),$je->getMessage());
				$this->setCampoFilemanager('estado_integrity', 'DATABASE_ERROR');
			} catch (\Throwable $e) {
				File::write(Path::clean($scanDir . DIRECTORY_SEPARATOR . 'error_integrity_scan.php'),$e->getMessage());
				$this->setCampoFilemanager('estado_integrity', 'DATABASE_ERROR');
			}

            if (($this->task_completed ?? false) && $result['ok'] === true) {
                $this->setCampoFilemanager('estado_integrity', 'ENDED');
            }
            return;
        }

        // malwarescan | malwarescan_modified
        if (!empty($this->malwarescan_name)) {
            $old = Path::clean($scanDir . DIRECTORY_SEPARATOR . $this->malwarescan_name);
            if (is_file($old) && $this->isPathInside($scanDir, $old)) {
                try { File::delete($old); } catch (\Throwable) {}
            }
        }
		

        $result = $this->writeScanJson($scanDir, ['files_folders' => (array) ($this->Stack_malwarescan ?? [])], 'error_malware_scan.php');
        $this->Stack_malwarescan = [];

        try {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key') . ' = ' . $db->quote('malwarescan_resume'));
            $db->setQuery($query)->execute();
        } catch (\Throwable) {}

        $resume = [
            'files_scanned_malwarescan' => (int) ($this->files_scanned_malwarescan ?? 0),
            'suspicious_files'          => (int) ($this->suspicious_files ?? 0),
            'last_check_malwarescan'    => $timestamp,
            'filename'                  => (string) ($result['filename'] ?? ''),
            'time_taken'                => (string) ($this->time_taken ?? ''),
        ];

        try {
            $obj = (object) [
                'storage_key'   => 'malwarescan_resume',
                'storage_value' => json_encode($resume, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
            $db->insertObject('#__securitycheckpro_storage', $obj);
        } catch (\Throwable $e) {
            $this->setCampoFilemanager('estado_malwarescan', 'DATABASE_ERROR');
            File::write(Path::clean($scanDir . DIRECTORY_SEPARATOR . 'error_malware_scan.php'), $e->getMessage());
        }

        if (($this->task_completed ?? false) && $result['ok'] === true) {
            $this->setCampoFilemanager('estado_malwarescan', 'ENDED');
        }
        $this->setCampoFilemanager('files_scanned_malwarescan', 100);
    }

    /**
     * Guarda un array como JSON protegido por kill-header en la carpeta de escaneos.
     *
     * @param  string                      $scanDir
     * @param  array<int|string,mixed>     $payload
     * @param  string                      $errorFileName Nombre del fichero de error a escribir si falla.
     * @return array{ok:bool, filename:string|null, error:string|null}
     */
    private function writeScanJson(string $scanDir, array $payload, string $errorFileName): array
	{
		$scanDir = Path::clean($scanDir);

		if (!is_dir($scanDir)) {
			return ['ok' => false, 'filename' => null, 'error' => 'Scan directory does not exist'];
		}

		$filename = $this->generateKey();
		$target   = Path::clean($scanDir . DIRECTORY_SEPARATOR . $filename);

		if (!$this->isPathInside($scanDir, $target)) {
			return ['ok' => false, 'filename' => null, 'error' => 'Refused to write outside scan directory'];
		}

		// 1) Normaliza strings a UTF-8 válido (recursivo) antes de json_encode
		$payload = $this->normalizePayloadUtf8($payload);

		try {
			$json = json_encode(
				$payload,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
			);
		} catch (\JsonException $e) {
			File::write(Path::clean($scanDir . DIRECTORY_SEPARATOR . $errorFileName), $e->getMessage());
			return ['ok' => false, 'filename' => null, 'error' => $e->getMessage()];
		}

		$content = "#<?php die('Forbidden.'); ?>" . PHP_EOL . $json;

		try {
			if (!File::write($target, $content)) {
				return ['ok' => false, 'filename' => null, 'error' => 'Unable to write scan file'];
			}
			@chmod($target, 0644);
		} catch (\Throwable $e) {
			File::write(Path::clean($scanDir . DIRECTORY_SEPARATOR . $errorFileName), $e->getMessage());
			return ['ok' => false, 'filename' => null, 'error' => $e->getMessage()];
		}

		@chmod($scanDir, 0755);

		return ['ok' => true, 'filename' => basename($target), 'error' => null];
	}

	/**
	 * Normaliza un payload para que todas las strings sean UTF-8 válidas (recursivo).
	 *
	 * - Mantiene ints/bools/null/float tal cual.
	 * - Convierte objetos JsonSerializable a array simple.
	 * - Para strings inválidas:
	 *   - intenta convertir desde la codificación más probable a UTF-8 (mb_convert_encoding),
	 *   - si no se puede, elimina bytes inválidos (iconv //IGNORE).
	 *
	 * @param array<mixed> $payload
	 * @return array<mixed>
	 */
	private function normalizePayloadUtf8(array $payload): array
	{
		/** @var array<mixed> $out */
		$out = [];

		foreach ($payload as $k => $v) {
			$key = is_string($k) ? $this->normalizeStringUtf8($k) : $k;
			$out[$key] = $this->normalizeValueUtf8($v);
		}

		return $out;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private function normalizeValueUtf8($value)
	{
		if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
			return $value;
		}

		if (is_string($value)) {
			return $this->normalizeStringUtf8($value);
		}

		if (is_array($value)) {
			// @phpstan-ignore-next-line
			return $this->normalizePayloadUtf8($value);
		}

		if ($value instanceof \JsonSerializable) {
			/** @var mixed $json */
			$json = $value->jsonSerialize();
			return $this->normalizeValueUtf8($json);
		}

		if (is_object($value)) {
			// Evita que json_encode reviente con objetos arbitrarios:
			// lo convertimos a array “simple” y normalizamos strings internas si las hay.
			/** @var array<string, mixed> $vars */
			$vars = get_object_vars($value);
			return $this->normalizePayloadUtf8($vars);
		}

		// resources, etc: representarlos de forma segura
		if (is_resource($value)) {
			return 'RESOURCE';
		}

		return (string) $value;
	}

	/**
	 * Devuelve una string en UTF-8 válido.
	 * Si ya es UTF-8 válido, la deja (opcionalmente normaliza a NFC si ext-intl está disponible).
	 */
	private function normalizeStringUtf8(string $s): string
	{
		if ($this->isValidUtf8($s)) {
			return $this->normalizeUnicodeNfc($s);
		}

		// 1) Intento de conversión “probable” -> UTF-8
		if (function_exists('mb_convert_encoding')) {
			// Detecta de forma best-effort
			$enc = function_exists('mb_detect_encoding')
				? (mb_detect_encoding($s, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'SJIS', 'EUC-JP'], true) ?: 'UTF-8')
				: 'UTF-8';

			$converted = @mb_convert_encoding($s, 'UTF-8', $enc);

			if (is_string($converted) && $this->isValidUtf8($converted)) {
				return $this->normalizeUnicodeNfc($converted);
			}

			// fallback: forzar desde “auto” (best effort)
			$converted2 = @mb_convert_encoding($s, 'UTF-8', 'auto');
			if (is_string($converted2) && $this->isValidUtf8($converted2)) {
				return $this->normalizeUnicodeNfc($converted2);
			}
		}

		// 2) Último recurso: eliminar bytes inválidos
		$clean = $this->stripInvalidUtf8Bytes($s);

		// Si quedara vacío, al menos devolvemos algo estable
		if ($clean === '') {
			return '[invalid-utf8]';
		}

		return $this->normalizeUnicodeNfc($clean);
	}

	private function isValidUtf8(string $s): bool
	{
		// preg_match('//u', ...) es un validador rápido de UTF-8
		return preg_match('//u', $s) === 1;
	}

	private function stripInvalidUtf8Bytes(string $s): string
	{
		if (function_exists('iconv')) {
			$out = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
			if (is_string($out)) {
				return $out;
			}
		}

		// Fallback si no hay iconv: elimina bytes no ASCII (menos ideal)
		return preg_replace('/[^\x00-\x7F]+/', '', $s) ?? '';
	}

	private function normalizeUnicodeNfc(string $s): string
	{
		// Normalización Unicode (por ejemplo, para nombres de fichero con formas combinadas).
		if (class_exists(\Normalizer::class)) {
			$n = \Normalizer::normalize($s, \Normalizer::FORM_C);
			if (is_string($n)) {
				return $n;
			}
		}

		return $s;
	}

    /**
     * Borra ficheros antiguos de la carpeta de escaneos manteniendo exentos.
     *
     * @param  string       $scanDir
     * @param  list<string> $exemptBasenames
     */
    private function purgeOldScanFiles(string $scanDir, array $exemptBasenames): void
    {
        $scanDir = Path::clean($scanDir);

        // Normaliza a basenames únicos
        $exempt = [];
        foreach ($exemptBasenames as $name) {
            $bn = basename((string) $name);
            if ($bn !== '') {
                $exempt[$bn] = true;
            }
        }

        $files = Folder::files($scanDir, '.', false, true);
        foreach ($files as $abs) {
            if (!is_file($abs)) {
                continue;
            }
            $bn = basename($abs);
            if (isset($exempt[$bn])) {
                continue;
            }
            if (!$this->isPathInside($scanDir, $abs)) {
                continue;
            }
            try {
                File::delete($abs);
            } catch (\Throwable) {
                // opcional: log
            }
        }

        @chmod($scanDir, 0755);
    }

    /**
     * Función que obtiene un array con los datos que serán mostrados en la opción 'file manager'
     *
     * @param   string             $opcion   				The option (integrity, malwarescan, permissions)
	 * @param   string        	   $field   			 	The field to filter to array
	 * @param   bool        	   $showall   			 	Show all files
     *
     * @return  array<array<string>>|string|int|null|void
     *     
     */
    function loadStack($opcion,$field,$showall=false)
    {
		$db = Factory::getContainer()->get(DatabaseInterface::class);
        $stack = null;
    
        // Establecemos el tamaño máximo de memoria que el script puede consumir
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $memory_limit = $params->get('memory_limit', '512M');
        if (preg_match('/^[0-9]*M$/', $memory_limit)) {
            ini_set('memory_limit', $memory_limit);
        } else
        {
            ini_set('memory_limit', '512M');
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_VALID_MEMORY_LIMIT'), 'error');
        }
                
        switch ($opcion)
        {
        case "permissions":      
			if (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name)) {
				// Leemos el contenido del fichero
				$stack = @file_get_contents($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name);
				// Eliminamos la parte del fichero que evita su lectura al acceder directamente
				$stack = str_replace("#<?php die('Forbidden.'); ?>", '', $stack);
			}
                        
            if (empty($stack)) {
                $this->Stack = array();
                return;
            }
            break;
        case "integrity":			
            // Leemos el contenido del fichero
			
            if (!file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name)) {
                $query = $db->getQuery(true)
                    ->select(array($db->quoteName('storage_value')))
                    ->from($db->quoteName('#__securitycheckpro_storage'))
                    ->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
                $db->setQuery($query);
                $stack_integrity = $db->loadResult();
                $stack_integrity = json_decode($stack_integrity, true);
    
                if ((!empty($stack_integrity)) && (isset($stack_integrity['filename']))) {            
                    $this->fileintegrity_name = $stack_integrity['filename'];
                }
            }
            
			if (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name)) {
				$stack = @file_get_contents($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name);
				// Eliminamos la parte del fichero que evita su lectura al acceder directamente
				$stack = str_replace("#<?php die('Forbidden.'); ?>", '', $stack);
			}
			                        
            if (empty($stack)) {
                $this->Stack_Integrity = array();
                return;
            }
            break;
        case "malwarescan":
            // Leemos el contenido del fichero            
            if (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name)) {
                $stack = @file_get_contents($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name);
                // Eliminamos la parte del fichero que evita su lectura al acceder directamente
                $stack = str_replace("#<?php die('Forbidden.'); ?>", '', $stack);
            }
            
            if (empty($stack)) {
                $this->Stack_malwarescan = array();
                return;
            }
            break;
        case "filemanager_resume":
            $query = $db->getQuery(true)
                ->select(array($db->quoteName('storage_value')))
                ->from($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key').' = '.$db->quote('filemanager_resume'));
            $db->setQuery($query);
            $stack = $db->loadResult();
            
            if (empty($stack)) {
                $this->files_scanned = 0;
                $this->files_with_incorrect_permissions = 0;
                return;
            }
            break;
        case "fileintegrity_resume":
            $query = $db->getQuery(true)
                ->select(array($db->quoteName('storage_value')))
                ->from($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
            $db->setQuery($query);
            $stack = $db->loadResult();
                        
            if (empty($stack)) {
                $this->files_scanned_integrity = 0;
                $this->files_with_incorrect_integrity = 0;
                return;
            }
            break;
        case "malwarescan_resume":
            $query = $db->getQuery(true)
                ->select(array($db->quoteName('storage_value')))
                ->from($db->quoteName('#__securitycheckpro_storage'))
                ->where($db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume'));
            $db->setQuery($query);
            $stack = $db->loadResult();
            
            if (empty($stack)) {
                $this->files_scanned_malwarescan = 0;
                $this->suspicious_files = 0;
                return;
            }
            break;
        }
    
        $stack = json_decode($stack, true);
				    
        /* Obtenemos el número de registros del array que hemos de mostrar. Si el límite superior es '0', entonces devolvemos todo el array */
        $upper_limit = $this->getState('limitstart');
        $lower_limit = $this->getState('limit');
    
        switch ($field)
        {
        case "file_manager":
            /* Obtenemos los valores de los filtros */
            $filter_permissions_status = $this->state->get('filter.filemanager_permissions_status');
            $filter_kind = $this->state->get('filter.filemanager_kind');
			if (!empty($this->state->get('filter.filemanager_search'))) {
				$search = htmlentities($this->state->get('filter.filemanager_search'));
			}            
            
            if (!is_null($stack['files_folders'])) {
                $filtered_array = array();
                /* Si el campo 'search' no está vacío, buscamos en todos los campos del array */            
                if (!empty($search)) {
                    $filtered_array = array_values(
                        array_filter(
                            $stack['files_folders'], function ($element) use ($filter_permissions_status,$filter_kind,$search) {
                                return (($element['safe'] == $filter_permissions_status) && ($element['kind'] == $filter_kind) && ((strstr($element['path'], $search)) || (strstr($element['last_modified'], $search)) || (strstr($element['permissions'], $search))));
                            }
                        )
                    );
                } else 
                {
                    $filtered_array = array_values(
                        array_filter(
                            $stack['files_folders'], function ($element) use ($filter_permissions_status,$filter_kind) {
                                return (($element['safe'] == $filter_permissions_status) && ($element['kind'] == $filter_kind));
                            }
                        )
                    );                
                }
                
                $this->total = count($filtered_array);            
                /* Cortamos el array para mostrar sólo los valores mostrados por la paginación */
                $this->Stack = array_splice($filtered_array, $upper_limit, $lower_limit);
                return ($this->Stack);
            }
        case "file_integrity":
            /* Obtenemos los valores de los filtros */
            $filter_fileintegrity_status = $this->state->get('filter.fileintegrity_status');
			if( !empty($this->state->get('filter.fileintegrity_search')) ) {
				$search = htmlentities($this->state->get('filter.fileintegrity_search'));
			}	
            
            
            if ( (!is_null($stack)) && (array_key_exists('files_folders',$stack)) ) {
                $filtered_array = array();
                /* Si el campo 'search' no está vacío, buscamos en todos los campos del array */            
                if (!empty($search)) {
                    $filtered_array = array_values(
                        array_filter(
                            $stack['files_folders'], function ($element) use ($filter_fileintegrity_status,$search) {
                                return (($element['safe_integrity'] == $filter_fileintegrity_status) && ((strstr($element['path'], $search)) || (strstr($element['hash'], $search)) || (strstr($element['notes'], $search))));
                            }
                        )
                    );
                } else
                {
                    $filtered_array = array_values(
                        array_filter(
                            $stack['files_folders'], function ($element) use ($filter_fileintegrity_status) {
                                return (($element['safe_integrity'] == $filter_fileintegrity_status));
                            }
                        )
                    );
                }
                $this->total = count($filtered_array);
                /* Cortamos el array para mostrar sólo los valores mostrados por la paginación */
                $this->Stack_Integrity = array_splice($filtered_array, $upper_limit, $lower_limit);
                return ($this->Stack_Integrity);
            }
        case "malwarescan":
            // 1) Filtros desde el estado
			/** @var string|null $filter_malwarescan_status */
			$filter_malwarescan_status = (string) $this->state->get('filter.malwarescan_status', '');

			// Nota: no HTML-encodees aquí; eso es para la vista. Aquí trabajamos con datos crudos.
			/** @var string $search */
			$searchRaw = (string) $this->state->get('filter.malwarescan_search', '');
			$search    = trim($searchRaw);

			// 2) Fuente de datos segura
			/** @var array<int,array<string,mixed>> $files */
			$files = [];
			if (is_array($stack) && array_key_exists('files_folders', $stack) && is_array($stack['files_folders'])) {
				/** @var array<int,array<string,mixed>> $files */
				$files = $stack['files_folders'];
			}

			// 3) Filtro principal
			$hasSearch = ($search !== '');
			$filtered  = array_values(array_filter(
				$files,
				static function (array $element) use ($filter_malwarescan_status, $hasSearch, $search): bool {
					// Comparación segura del estado
					$status = (string) ($element['safe_malwarescan'] ?? '');
					if ($status !== (string) $filter_malwarescan_status) {
						return false;
					}

					if (!$hasSearch) {
						return true;
					}

					// Buscar en varios campos, todo casteado a string
					foreach (['path', 'size', 'last_modified', 'malware_type', 'malware_description'] as $key) {
						if (array_key_exists($key, $element)) {
							$haystack = (string) $element[$key];
							if ($haystack !== '' && str_contains($haystack, $search)) {
								return true;
							}
						}
					}

					return false;
				}
			));

			// 4) Ordenar por nivel de alerta (ascendente), robusto ante valores ausentes/no numéricos
			usort(
				$filtered,
				static function (array $a, array $b): int {
					$av = isset($a['malware_alert_level']) ? (int) $a['malware_alert_level'] : PHP_INT_MAX;
					$bv = isset($b['malware_alert_level']) ? (int) $b['malware_alert_level'] : PHP_INT_MAX;
					return $av <=> $bv;
				}
			);

			// 5) Total antes de paginar
			$this->total = \count($filtered);

			// 6) Paginación (no modificar $filtered con splice, mejor slice)
			if ($showall) {
				$this->Stack_malwarescan = $filtered;
			} else {
				// Aseguramos límites sanos
				$offset = max(0, $upper_limit);
				$length = max(0, $lower_limit);
				$this->Stack_malwarescan = array_slice($filtered, $offset, $length);
			}

			return $this->Stack_malwarescan;
        case "files_scanned":
			if ( (!is_null($stack)) && (array_key_exists('files_scanned',$stack)) ) {
				$this->files_scanned = $stack['files_scanned'];
			} else {
				$this->files_scanned = 0;
			}
            return ($this->files_scanned);
        case "files_with_incorrect_permissions":
            if (empty($stack)) {
                $this->files_with_incorrect_permissions = 0;
            } else
            {
                $this->files_with_incorrect_permissions = $stack['files_with_incorrect_permissions'];            
            }    
            return ($this->files_with_incorrect_permissions);
        case "last_check":
            return ($stack['last_check']);
        case "files_scanned_integrity":
            $this->files_scanned_integrity = $stack['files_scanned_integrity'];
            return ($this->files_scanned_integrity);
        case "files_with_bad_integrity":
            if (empty($stack)) {
                $this->files_with_incorrect_integrity = 0;
            } else 
            {
                $this->files_with_incorrect_integrity = $stack['files_with_incorrect_integrity'];            
            }
            return ($this->files_with_incorrect_integrity);
        case "last_check_integrity":
            return ($stack['last_check_integrity']);
		case "last_check_malwarescan":
            return ($stack['last_check_malwarescan']);
        case "time_taken":
			if (array_key_exists('time_taken',$stack)){
				return ($stack['time_taken']);
			} else {
				return Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_NEVER');
			}
            
		 case "last_scan_info":
			if ( array_key_exists('last_scan_info',$stack) )
			{
				return ($stack['last_scan_info']);
			} else {
				return array();
			}            
        case "files_with_incorrect_integrity":
            $this->files_with_incorrect_integrity = $stack['files_with_incorrect_integrity'];
            return ($this->files_with_incorrect_integrity);
        case "files_scanned_malwarescan":
            $this->files_scanned_malwarescan = $stack['files_scanned_malwarescan'];
            return ($this->files_scanned_malwarescan);
        case "suspicious_files":
            if (empty($stack)) {
                $this->suspicious_files = 0;
            } else
            {
                $this->suspicious_files = $stack['suspicious_files'];            
            }    
            return ($this->suspicious_files);
        }
    }

    /**
     * Función que escanea el sitio para obtener los permisos o la integridad de los archivos y directorios
     *
     * @param   string             $opcion   				The option (integrity, malwarescan, permissions)	
     *
     * @return  void
     *     
     */
    function scan($opcion)
    {

        $include_exceptions = 0;
        $folder_exceptions = 0;
        
        // Obtenemos la ruta sobre la que vamos a hacer el chequeo
        $params = ComponentHelper::getParams('com_securitycheckpro');
				
        $file_check_path = $params->get('file_manager_path', JPATH_ROOT);
    
        if (($file_check_path == "JPATH_ROOT") || ($file_check_path == JPATH_ROOT)) {
            $file_check_path = JPATH_ROOT;
        } else 
        {
            $file_check_path = JPATH_ROOT . DIRECTORY_SEPARATOR . $file_check_path;
        }
    
        switch ($opcion)
        {
        case "permissions":            
            $this->files_processed_permissions = 0;
            // Obtenemos si debemos guardar las excepciones
            $include_exceptions = $params->get('file_manager_include_exceptions_in_database', 1);    
            break;
        case "integrity":
            // Obtenemos si debemos guardar las excepciones
            $include_exceptions = $params->get('file_manager_include_exceptions_in_database', 1);             
            break;
        case "malwarescan":
            // Obtenemos si debemos guardar las excepciones
            $include_exceptions = $params->get('file_manager_include_exceptions_in_database', 1);  
            break;
        case "malwarescan_modified":
            // Obtenemos si debemos guardar las excepciones
            $include_exceptions = $params->get('file_manager_include_exceptions_in_database', 1);            
            break;
        }
    
        $this->prepareLog($opcion);
        $this->write_log("------- Begin scan: " . strtoupper($opcion) . " --------");
		
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
		$now = $this->get_Joomla_timestamp();  
		// This is needed to avoid errors getting the file from cli
		if ( !($mainframe instanceof ConsoleApplication) ) {		
			$mainframe->setUserState("scan_start_time", $now );			
		}
    
        $this->getDirectories($file_check_path, (bool) $include_exceptions, $opcion);
        $this->getFiles($file_check_path, (bool) $include_exceptions, $opcion);
        $this->saveStack($opcion);
		    
		$this->write_log("------- End scan: " . strtoupper($opcion) . " --------");
    }
  	
    	
	/**
	 * Indica si el fichero/directorio es más permisivo de lo permitido.
	 * - Archivos: como máximo 0644
	 * - Directorios: como máximo 0755
	 * Además marca como “malo” si hay suid/sgid/sticky.
	 * @param   string             $file   				The path to the file
	 *
     * @return  bool
	 */
	function isTooPermissive(string $file)
	{
		$stat = @fileperms($file);
		if ($stat === false) {
			// Si no podemos leer permisos, trátalo como no permisivo extra (decisión conservadora)
			return false;
		}

		$perm      = $stat & 0o777;    // rwx
		$isDir     = ($stat & 0x4000) === 0x4000; // S_IFDIR
		$max       = $isDir ? 0o755 : 0o644;
		$extraBits = 0o777 & (~$max);  // bits “sobrantes” sobre el máximo permitido

		$tooPermissive = ($perm & $extraBits) !== 0;

		// suid/sgid/sticky
		$hasSpecial = ($stat & 0o7000) !== 0;

		return $tooPermissive || $hasSpecial;
	}

    /**
     * Obtiene los permisos de un archivo o directorio en formato octal
     *
     * @param   string             $file   				The path to the file	 
     *
     * @return  string
     *     
     */
    function file_perms($file)
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app    = Factory::getApplication();
		$server = (string) $app->getUserState('server', 'apache');

		// Si el servidor es IIS, devolvemos "0644" como hacías antes
		if (stripos($server, 'iis') !== false) {
			return '0644';
		}

		$stat = @fileperms($file);
		if ($stat === false) {
			// Si no se puede leer, devolvemos un valor neutro
			return '0000';
		}

		$perm = $stat & 0o777; // solo los bits rwx
		return sprintf('%04o', $perm);

    }

   	/**
     * Destruye y crea la tabla '#__securitycheckpro_file_permissions'
     *
     *
     * @return  void
     *     
     */
    function initialize_database()
    {
        // Creamos el nuevo objeto query
        $db = Factory::getContainer()->get(DatabaseInterface::class);
    
        // Borramos la tabla...
        $query = 'DROP TABLE IF EXISTS #__securitycheckpro_file_permissions';
        $db->setQuery($query);
        $db->execute();

        // Actualizamos los campos de la tabla '#__securitycheckpro_file_manager'
		$query = "UPDATE #__securitycheckpro_file_manager SET last_check=null,last_check_integrity=null,last_check_malwarescan=null,files_scanned=0,files_scanned_integrity=0,files_with_incorrect_permissions=0,files_scanned_malwarescan=0,files_with_bad_integrity=0,suspicious_files=0,estado='ENDED',estado_integrity='ENDED',estado_malwarescan='ENDED',cron_tasks_launched=0 where id=1";
        $db->setQuery($query);
        $db->execute();
    
        // Obtenemos el nombre de los escaneos anteriores...
        $query = $db->getQuery(true)
            ->select(array($db->quoteName('storage_value')))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote('filemanager_resume'));
        $db->setQuery($query);
        $stack = $db->loadResult();
		if ($stack !== null && $stack !== '') {
			$stack = json_decode($stack, true);
			$this->filemanager_name = $stack['filename'];
		} 
    
        $query = $db->getQuery(true)
            ->select(array($db->quoteName('storage_value')))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume'));
        $db->setQuery($query);
        $stack_integrity = $db->loadResult();
		if ($stack_integrity !== null && $stack_integrity !== '') {
			$stack_integrity = json_decode($stack_integrity, true);
			$this->fileintegrity_name = $stack_integrity['filename'];
		} 
           
        $query = $db->getQuery(true)
            ->select(array($db->quoteName('storage_value')))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume'));
        $db->setQuery($query);
        $stack_malwarescan = $db->loadResult();
		if ($stack_malwarescan !== null && $stack_malwarescan !== '') {
			$stack_malwarescan = json_decode($stack_malwarescan, true);
			$this->malwarescan_name = $stack_malwarescan['filename'];
		} 
           
        // ... y borramos los ficheros
		try{		
			if ( (!empty($this->filemanager_name)) && (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name)) ){
				$delete_permissions_file = File::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->filemanager_name);
			}
			if ( (!empty($this->fileintegrity_name)) && (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name)) ){
				$delete_integrity_file = File::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->fileintegrity_name);
			}
			if ( (!empty($this->malwarescan_name)) && (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name)) ){
				$delete_malwarescan_file = File::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name);
			}
		} catch (\Exception $e)
		{
		}       
    
        // Nos aseguramos que los permisos de la carpeta 'scans' son los correctos
        chmod($this->folder_path, 0755);
    
        // Inicializamos la tabla  '#__securitycheckpro_storage'
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__securitycheckpro_storage'))
            ->where('(' .$db->quoteName('storage_key').' = '.$db->quote('fileintegrity_resume') .') OR (' .$db->quoteName('storage_key').' = '.$db->quote('filemanager_resume') .') OR (' .$db->quoteName('storage_key').' = '.$db->quote('malwarescan_resume') .')');
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Obtiene la diferencia en horas entre dos tareas
     *
     * @param   string             $opcion   		The option 
     *
     * @return  int
     *     
     */
    function get_timediff($opcion)
    {
        (int) $interval = 0;
    
        switch ($opcion) 
        {
        case "integrity":
            $last_check_integrity_start_time = $this->GetCampoFilemanager('last_check_integrity');
            $now = $this->get_Joomla_timestamp();
			
			if (empty($last_check_integrity_start_time)) {
				$last_check_integrity_start_time = $now;
			}
		
            $seconds = strtotime($now) - strtotime($last_check_integrity_start_time);
			$days = intval($seconds/86400);
			$hours = intval($seconds/3600);
            // Extraemos el número total de días entre las dos fechas. Si es cero, no ha transcurrido ningún día, por lo que devolvemos la diferencia de horas. Si ha transcurrido un día o más, devolvemos un valor suficientemente alto para activar los disparadores necesarios
            if ($days == 0) {
                // Extraemos el número total de horas que han pasado desde el último chequeo
                $interval = $hours;
            } else
            {
                $interval = 20000;
            }    
            break;
        case "permissions":
			$last_check_start_time = $this->GetCampoFilemanager('last_check');
            $now = $this->get_Joomla_timestamp();
			
			if (empty($last_check_start_time)) {
				$last_check_start_time = $now;
			}
			
            $seconds = strtotime($now) - strtotime($last_check_start_time);
			$days = intval($seconds/86400);
			$hours = intval($seconds/3600);            
            // Extraemos el número total de días entre las dos fechas. Si es cero, no ha transcurrido ningún día, por lo que devolvemos la diferencia de horas. Si ha transcurrido un día o más, devolvemos un valor suficientemente alto para activar los disparadores necesarios
            if ($days == 0) {
                // Extraemos el número total de horas que han pasado desde el último chequeo
                $interval = $hours;
            } else
            {
                $interval = 20000;
            }    
            break;
        case "malwarescan":
			$last_check_malwarescan_start_time = $this->GetCampoFilemanager('last_check_malwarescan');
            $now = $this->get_Joomla_timestamp();
			
			if (empty($last_check_malwarescan_start_time)) {
				$last_check_malwarescan_start_time = $now;
			}
			
            $seconds = strtotime($now) - strtotime($last_check_malwarescan_start_time);
			$days = intval($seconds/86400);
			$hours = intval($seconds/3600);
			
            // Extraemos el número total de días entre las dos fechas. Si es cero, no ha transcurrido ningún día, por lo que devolvemos la diferencia de horas. Si ha transcurrido un día o más, devolvemos un valor suficientemente alto para activar los disparadores necesarios
            if ($days == 0) {
                // Extraemos el número total de horas que han pasado desde el último chequeo
                $interval = $hours;
            } else 
            {
                $interval = 20000;
            }    
            break;
        }
        return $interval;
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
	 * Comprueba si el servidor web actual es IIS.
	 *
	 *
	 * @return bool True si el servidor es Microsoft IIS, false en cualquier otro caso.
	 */
	private function onIIS(): bool
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = \Joomla\CMS\Factory::getApplication();

		// Evitamos errores si se ejecuta desde CLI
		if ($app instanceof ConsoleApplication) {
			return false;
		}

		// Validamos que la variable exista y sea string
		$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
		if (!\is_string($serverSoftware) || $serverSoftware === '') {
			return false;
		}

		// Normalizamos y comprobamos la cadena
		return \str_contains(\strtolower($serverSoftware), 'microsoft-iis');
	}


   	/**
     * Función que chequea si hay código inyectado al principio de un archivo
     *
	 * @param   string             $content  	  The content to be analyzed
	 * @param   string             $path    	  The path of the file
     *
     * @return  bool
     *     
     */
    function code_at_start($content,$path)
    {
        // Check if there is allowed content between 'php' string and '/*' string (for instance, namespace administrator\components\com_gdpr\controllers;
		$allowed_content = false;
        $ini = strpos($content, "<?php");
        $end = strpos($content, "/*");
		$allowed_content_pos = strpos($content, "namespace");
		
		$length = strlen($content);
        $number_of_spaces = substr_count($content, ' ', 0, $end-$ini);
        $number_of_new_lines = substr_count($content, PHP_EOL, 0, $end-$ini);
		
		if ( ($allowed_content_pos !== false) && ($allowed_content_pos < $end) )
		{
			$allowed_content = true;
		}
    
        // Check if we are on IIS. For some reason PHP_EOL doesn't return the number of new lines...
        $iis = $this->onIIS();    
    
        if (($ini !== false) && ($end !== false) && ($number_of_new_lines < 3) && ($end-$ini > 50) && (!$allowed_content) && (!$iis) ) {
            return true;
        }
        return false;
    }

    /**
     Scan given file for all malware patterns
    
     Based on the JAMSS - Joomla! Anti-Malware Scan Script
     *
     @version 1.0.7
    
     @author Bernard Toplak [WarpMax] <bernard@orion-web.hr>
     @link   http://www.orion-web.hr
    
     @global string $fileExt file extension list to be scanned
     @global array $patterns array of patterns to search for
     @param  string $path path of the scanned file
	 *
     * @return  list<array<int, mixed>>
     */
    private function scan_file($path)
    {

        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

        // Aadimos los strings sospechosos a la bsqueda de malware?
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $deep_scan = $params->get('deep_scan', 0);
		$suspicious_patterns = [];
		$suspicious_strings = [];
        if ($deep_scan) {

            // Cargamos los strings que se buscan como malware desde el fichero de strings
            if(@file_exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_strings.dat')) {
                // Leemos el contenido del fichero, que estará en formato base64
                $suspicious_strings = @file_get_contents(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_strings.dat');

                // Lo decodificamos
                $suspicious_strings = base64_decode($suspicious_strings);        
            } 
        } 

        // Cargamos los patrones que se buscarán como malware desde el fichero de patrones
        if(@file_exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_patterns.dat')) {

            // Leemos el contenido del fichero, que estará en formato base64
            $malware_patterns = @file_get_contents(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_securitycheckpro'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'Malware_patterns.dat');    
    
            // Lo decodificamos
            $malware_patterns = base64_decode($malware_patterns);
    
            // Creamos un array bidimensional con el contenido del fichero leído
            $suspicious_patterns  = array_map(
                function ($_) {
                    return explode('~', $_);
                },
                explode('¡', $malware_patterns)
            );    
    
        }


        $jamssFileNames = array(
        $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_OFC_UPLOAD_IMAGE')
        => 'ofc_upload_image.php',
        $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_R57')
        => 'r57.php',
        $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME_PHPINFO')
        => 'phpinfo.php',
        );

        /* * * * * Patterns End * * * * */

        // Extensiones de ficheros que serán analizadas
        // Eliminamos los espacios en blanco		
		if (empty($this->fileExt)) {
			$this->fileExt = "php,php3,php4,php5,phps,html,htaccess,js";
		}

        $this->fileExt = str_replace(' ', '', $this->fileExt);
        $ext = explode(',', $this->fileExt);
    
        // Patrones y strings a buscar
        if ($deep_scan) {
            $patterns = array_merge($suspicious_patterns, explode('|', $suspicious_strings));
        } else
        {
            $patterns = $suspicious_patterns;
        }
        
        // Inicializamos las variables
        $resultado = array(array());
        $resultado[0][0] = false;
        $count = 0;
        $total_results = 0;
        $malware_found = false;
				    
    
        if ($malic_file_descr = array_search(pathinfo($path, PATHINFO_BASENAME), $jamssFileNames)) {
            $resultado[0][0] = true;
            $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_FILENAME');
            $resultado[0][2] = $malic_file_descr;
            $resultado[0][3] = '';
            $resultado[0][4] = '0';
              
        } else 
        {
            $content = @file_get_contents($path);
            if (!$content) {
                /*$error = 'Could not check '.$path;
                echo formatError($error);*/
            } else
            { // do a search for fingerprints
                // Look for obfuscated code
                preg_match_all("/\\\x([0-9]{2})/", $content, $found);
                $pattern[1] = "Php obfuscated";
                $pattern[2] = "29";
                $pattern[3] = "Encoded representation of source code, commonly used to hide malware";
				$info = []; 
            
                $all_results = $found[0]; // remove outer array from results
                $results_count = count($all_results); // count the number of results
                $total_results += $results_count; // total results of all fingerprints   
				                                
                if ( (!empty($all_results)) && ($results_count>50) && ( substr_count(strtolower($content), strtolower("global"))) ) { 				
				    // Update the variable to stop looking for more malware patterns
                    $malware_found = true;
                    // Let's see if this seems a Joomla file, which usually forbids direct access using the JEXEC feature
                    $content_without_spaces = $this->cleanSpaces($content);
                    //if ((!strstr($content_without_spaces,"defined('_JEXEC')ordie")) && (!strstr($content_without_spaces,"defined('JPATH_BASE')ordie"))) {
                    $count++;
                    $resultado[0][0] = true;
                    $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_ENCODED_CONTENT');
                    $resultado[0][2] = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'), $pattern[2], $pattern[1], $results_count, $pattern[3]);                    
                    $resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
                    $resultado[0][4] = '0';                    
                    //}                
                }
            
                // Look for obfuscated code using conversions
                if (!$malware_found) {
                    $info = pathinfo($path);                    
                    if ((array_key_exists('extension', $info)) && ($info['extension'] == 'php')) {
                           $length = strlen($content);
                           $number_of_spaces = substr_count($content, ' ');
                           $number_of_new_lines = substr_count($content, PHP_EOL);
						   // Count the number of apostrophes ('). This is done to avoid false positives in J4 /libraries/vendor/voku/portable-ascii/src/voku/helper/data/
						   $number_of_apostrophes = substr_count($content, "'");
                           // Check if we are on IIS. For some reason PHP_EOL doesn't return the number of new lines...
                           $iis = $this->onIIS();					   
							
                        if (((($number_of_spaces/$length) < 0.001) && (($number_of_spaces/$length) > 0)) || ((($number_of_new_lines/$length) < 0.001) && (($number_of_new_lines/$length) > 0) && (($number_of_apostrophes) < 400)) || (($number_of_new_lines == 0) && (!$iis)) || ($number_of_spaces == 0)) {
                            // Update the variable to stop looking for more malware patterns
                            $malware_found = true;
                            $pattern[1] = "Obfuscated file";
                            $pattern[2] = "30";
                            $pattern[3] = "Encoded representation of source code, commonly used to hide malware";
                            $resultado[0][0] = true;
                            $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_ENCODED_CONTENT');
                            $resultado[0][2] = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'), $pattern[2], $pattern[1], 'Not applicable', $pattern[3]);
                            $resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
                            $resultado[0][4] = '0';    
                        } 
                    }                    
                }
            
                // Look for obfuscated code injected
                if (!$malware_found) {
                    if ((array_key_exists('extension', $info)) && ($info['extension'] == 'php')) {
                        $injected = $this->code_at_start($content, $path);                
                        if ($injected) {
                            $malware_found = true;
                            $count++;
                            $pattern[1] = "Obfuscated content injected";
                            $pattern[2] = "30";
                            $pattern[3] = "Code injected at the beggining of the file";
                            $resultado[0][0] = true;
                            $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_ENCODED_CONTENT_INJECTED');
                            $resultado[0][2] = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'), $pattern[2], $pattern[1], 'Not applicable', $pattern[3]);
                            $resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . 'Undefined';
                            $resultado[0][4] = '0';                
                        }
                    }
                }
                    
                // The file is not obfuscated
                if (!$malware_found) {        
                    foreach ($patterns As $pattern)
                    {
                        if (!$malware_found) {
                            if (is_array($pattern)) { // it's a pattern                    
                                // RegEx modifiers: i=case-insensitive; s=dot matches also newlines; S=optimization
                                preg_match_all('/' . $pattern[0] . '/sS', $content, $found, PREG_OFFSET_CAPTURE);                                                 
                            } else
                            { // it's a string
                                preg_match_all('/' . $pattern . '/isS', $content, $found, PREG_OFFSET_CAPTURE);
                            }
                        
                            $all_results = $found[0]; // remove outer array from results
                            $results_count = count($all_results); // count the number of results
                            $total_results += $results_count; // total results of all fingerprints    
                                                                                                    
                            if (!empty($all_results)) {    
                                // Update the variable to stop looking for more malware patterns
                                $malware_found = true;
                                // Let's see if this seems a Joomla file, which usually forbids direct access using the JEXEC feature
                                $content_without_spaces = $this->cleanSpaces($content);
                                // Check the line of the ocurrence; on modified files it's usuallly the first line
								
                                foreach ($all_results as $match)
                                {
                                        $line = $this->calculate_line_number($match[1], $content);
                                }
                            
                                if (((!strstr($content_without_spaces, "defined('_JEXEC')ordie")) && (!strstr($content_without_spaces, "defined('JPATH_BASE')ordie")) && ($line==1)) || ($line==1)) {
                                                $count++;
                                    if (is_array($pattern)) { // then it has some additional comments
                                        $resultado[0][0] = true;
                                        $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
                                        $resultado[0][2] = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'), $pattern[2], $pattern[1], $results_count, 
										mb_convert_encoding($pattern[3], 'UTF-8'));										
                                        $resultado[0][4] = '0';                                    
                                    } else
                                    { // it's a string, no comments available
                                        $resultado[0][0] = true;
                                        $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
                                        $resultado[0][2] = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO_STRING'), $results_count, $pattern);
                                        $resultado[0][4] = '2';                            
                                    }
                                    // Añadimos el código sospechoso encontrado (previamente sanitizado)
                                    foreach ($all_results as $match)
                                    {
                                        $resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . $line; 
                                        $resultado[0][3] .= "<br />";
                                        $resultado[0][3] .= htmlentities(substr($content, $match[1], 200), ENT_QUOTES);
                                    }
                                } else if (is_array($pattern)) {
                                    // Found a malware pattern; it's almost sure a malware even when it's hide into a valid Joomla file.
                                    $count++;
                                    $resultado[0][0] = true;
                                    $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
                                    $resultado[0][2] = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO'), $pattern[2], $pattern[1], $results_count, mb_convert_encoding($pattern[3], 'UTF-8'));                                
                                    $resultado[0][4] = '0';                                    
                                    // Añadimos el código sospechoso encontrado (previamente sanitizado)
                                    foreach ($all_results as $match)
                                    {
                                        $resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . $line; 
                                        $resultado[0][3] .= "<br />";
                                        $resultado[0][3] .= htmlentities(substr($content, $match[1], 200), ENT_QUOTES);
                                    }
                                } else if (!is_array($pattern)) {
                                        // Found a malware string; can't be sure this is not a false positive.
                                        $count++;
                                        $resultado[0][0] = true;
                                        $resultado[0][1] = $lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN');
                                        $resultado[0][2] = Text::sprintf($lang->_('COM_SECURITYCHECKPRO_SUSPICIOUS_PATTERN_INFO_STRING'), $results_count, $pattern);
                                        $resultado[0][4] = '2';    
                                
                                        // Añadimos el código sospechoso encontrado (previamente sanitizado)
                                    foreach ($all_results as $match)
                                        {
                                        $resultado[0][3] = $lang->_('COM_SECURITYCHECKPRO_LINE') . $line; 
                                        $resultado[0][3] .= "<br />";
                                        $resultado[0][3] .= htmlentities(substr($content, $match[1], 200), ENT_QUOTES);
                                    }
                                }
                            }
                        }
                    }
                }            
                unset($content);
            }
        }
        return $resultado;
    }

	/**
	 * Elimina todos los espacios en blanco de una cadena.
	 *
	 * @param  string $text Contenido a limpiar.
	 * @return string Cadena sin espacios ni caracteres de espacio Unicode.
	 */
	private function cleanSpaces(string $text): string
	{
		// Eliminamos todos los espacios en blanco (incluye \t, \n, \r, y caracteres Unicode)
		$cleaned = \preg_replace('/\s+/u', '', $text);

		// preg_replace puede devolver null en caso de error en la expresión regular
		return $cleaned ?? '';
	}

   	
    /**
      JAMSS - Joomla! Anti-Malware Scan Script
     *
     @version 1.0.7
    
     @author Bernard Toplak [WarpMax] <bernard@orion-web.hr>
     @link   http://www.orion-web.hr
    
     Calculates the line number where pattern match was found
    
     @param  int $offset  The offset position of found pattern match
     @param  string $file_content The file content in string format
     @return int Returns line number where the subject code was found
     */
    function calculate_line_number($offset, $file_content)
    {
        if (strlen($file_content) >= 1) {
            list($first_part) = str_split($file_content, $offset); // fetches all the text before the match
            $line_nr = strlen($first_part) - strlen(str_replace("\n", "", $first_part)) + 1;
            return $line_nr;
        } else 
        {
            return 0;
        }
    }

    /**
	 * Obtiene un array de rutas de archivos con integridad modificada para la opción "filestatus".
	 *
	 *
	 * @return array<int, string> Lista de rutas (paths) con integridad modificada.
	 */
	public function loadModifiedFiles(): array
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		// 1) memory_limit desde parámetros (permite valores tipo "256M" o "1G")
		$params       = ComponentHelper::getParams('com_securitycheckpro');
		$memoryLimit  = (string) $params->get('memory_limit', '512M');
		if (\preg_match('/^\d+[MG]$/i', $memoryLimit) === 1) {
			@ini_set('memory_limit', $memoryLimit);
		} else {
			@ini_set('memory_limit', '512M');
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_VALID_MEMORY_LIMIT'), 'error');
		}

		// 2) Recuperamos de BBDD el nombre del fichero de resumen de integridad
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote('fileintegrity_resume'));

		$db->setQuery($query);
		$stackIntegrityJson = (string) ($db->loadResult() ?? '');

		$fileIntegrityName = '';
		if ($stackIntegrityJson !== '') {
			try {
				/** @var array<string, mixed> $stackIntegrity */
				$stackIntegrity = \json_decode($stackIntegrityJson, true, 512, \JSON_THROW_ON_ERROR);
				$candidate      = $stackIntegrity['filename'] ?? '';
				// Saneamos: sólo nombre base, sin rutas
				$candidate      = \is_string($candidate) ? \basename($candidate) : '';
				if ($candidate !== '') {
					$fileIntegrityName = $candidate;
				}
			} catch (\JsonException) {
				// Param almacenado inválido; continuamos con array vacío
			}
		}

		if ($fileIntegrityName === '') {
			// No hay fichero definido => nada que cargar
			$this->Stack_Integrity = [];
			return $this->Stack_Integrity;
		}

		// 3) Construimos ruta segura y comprobamos que quede dentro de $this->folder_path
		$baseDir = rtrim((string) $this->folder_path, DIRECTORY_SEPARATOR);
		$safeRel = Path::clean($fileIntegrityName);
		$full    = Path::clean($baseDir . DIRECTORY_SEPARATOR . $safeRel);

		// Resolvemos rutas reales para evitar traversal
		$realBase = realpath($baseDir) ?: $baseDir;
		$realFull = realpath($full) ?: $full;

		// Garantizamos que el fichero está dentro de la carpeta esperada
		if (\strpos($realFull, $realBase . DIRECTORY_SEPARATOR) !== 0) {
			$this->Stack_Integrity = [];
			return $this->Stack_Integrity;
		}

		// 4) Leemos contenido del fichero (si existe)
		if (!is_file($realFull) || !\is_readable($realFull)) {
			$this->Stack_Integrity = [];
			return $this->Stack_Integrity;
		}

		// Preferimos File::read si está disponible, sino file_get_contents
		try {
			$stackRaw = File::read($realFull);
		} catch (\Throwable) {
			$stackRaw = @file_get_contents($realFull);
		}

		if (!is_string($stackRaw) || $stackRaw === '') {
			$this->Stack_Integrity = [];
			return $this->Stack_Integrity;
		}

		// 5) Eliminamos la línea de protección (por si existe)
		//    Acepta variantes con o sin comillas dobles y posibles espacios.
		$stackRaw = str_replace(["#<?php die('Forbidden.'); ?>", '#<?php die("Forbidden."); ?>'], '', $stackRaw);

		// 6) Decodificamos JSON de escaneo
		try {
			/** @var array<string, mixed> $stack */
			$stack = json_decode($stackRaw, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			$this->Stack_Integrity = [];
			return $this->Stack_Integrity;
		}

		// Estructura esperada: $stack['files_folders'] = list<array{path:string, safe_integrity:int|0|1|...}>
		$filesFolders = $stack['files_folders'] ?? null;
		if (!is_array($filesFolders)) {
			$this->Stack_Integrity = [];
			return $this->Stack_Integrity;
		}

		// 7) Filtramos sólo los elementos con integridad modificada (safe_integrity === 0)
		$modified = array_values(array_filter(
			$filesFolders,
			static function ($el): bool {
				if (!is_array($el)) {
					return false;
				}
				$integrity = $el['safe_integrity'] ?? null;
				return ($integrity === 0 || $integrity === '0'); // Admitimos string "0" por compatibilidad
			}
		));

		// 8) Mapeamos a rutas (strings) válidas
		$paths = [];
		foreach ($modified as $el) {
			$p = $el['path'] ?? null;
			if (is_string($p) && $p !== '') {
				$paths[] = $p;
			}
		}

		// 9) Normalizamos, deduplicamos y devolvemos
		/** @var array<int, string> $unique */
		$unique = array_values(array_unique($paths));

		$this->Stack_Integrity = $unique;
		return $this->Stack_Integrity;
	}

    /**
     * Función para escribir una entrada en el fichero de logs de cambio de permisos
     *
     * @param   array<string>             $log_array    The array with the logs
     *
     * @return  void
     *     
     */
    function write_permission_log($log_array)
    {    
        // Borramos los ficheros de logs antiguos
		if (file_exists(JPATH_ADMINISTRATOR. DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'change_permissions.log.php')) {
			try{		
				File::delete(JPATH_ADMINISTRATOR. DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'change_permissions.log.php');
			} catch (\Exception $e)
			{
			}
		}	
        Log::addLogger(
            array(
            'text_file' => 'change_permissions.log.php',
            'text_entry_format' => '{DATETIME} | {MESSAGE}'
            )
        );
    
        foreach($log_array as $log)
        {
            $logEntry = new LogEntry(array_pop($log_array)); 
			
            Log::add($logEntry);
        }
    }

	/**
	 * Lee el log de reparaciones y devuelve HTML formateado.
	 *
	 * Formato esperado por línea:  "timestamp|LEVEL|mensaje"
	 * Niveles: ERROR, WARNING, INFO, DEBUG, OK
	 *
	 * @return string HTML listo para pintar en la vista (nunca hace echo).
	 */
	function getRepairLog(): string
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		// 1) Resolver ruta del directorio de logs desde configuration.php
		$logDir = (string) $app->getConfig()->get('log_path', JPATH_ROOT . DIRECTORY_SEPARATOR . 'administrator' . DIRECTORY_SEPARATOR . 'logs');
		$logFile = $logDir . DIRECTORY_SEPARATOR . 'change_permissions.log.php';

		// 2) Validación robusta de ruta (evita path traversal)
		$logDirReal  = realpath($logDir) ?: '';
		$logFileReal = realpath($logFile) ?: '';
		if ($logDirReal === '' || $logFileReal === '' || strncmp($logFileReal, $logDirReal . DIRECTORY_SEPARATOR, strlen($logDirReal) + 1) !== 0) {
			// Ruta inválida o fuera del directorio de logs
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS'), 'error');
			return '<p>' . Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS') . '</p>';
		}

		// 3) Comprobaciones de existencia/legibilidad
		if (!is_file($logFileReal) || !is_readable($logFileReal)) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_UNREADABLE'), 'error');
			return '<p>' . Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_UNREADABLE') . '</p>';
		}

		// 4) Apertura en modo lectura texto
		$fp = @fopen($logFileReal, 'rb');
		if ($fp === false) {
			$app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_UNREADABLE'), 'error');
			return '<p>' . Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_UNREADABLE') . '</p>';
		}

		// 5) Mapeo de estilos por nivel (inline para no depender de CSS externo)
		$levelStyles = [
			'ERROR'   => 'color: red; font-weight: bold;',
			'WARNING' => 'color: #D8AD00; font-weight: bold;',
			'INFO'    => 'color: black;',
			'DEBUG'   => 'color: #666666; font-size: small;',
			'OK'      => 'color: green; font-weight: bold;',
		];

		$html = '';

		// 6) Lectura en streaming (sin cargar todo a memoria)
		//    Omitimos cabeceras PHP y líneas vacías.
		try {
			while (!feof($fp)) {
				$line = fgets($fp);
				if ($line === false) {
					break;
				}

				$trim = trim($line);
				if ($trim === '' || str_starts_with($trim, '<?php')) {
					continue; // cabecera del log u otras líneas no-log
				}

				// Esperamos como mínimo 3 campos: timestamp|LEVEL|mensaje
				$parts = explode('|', $line, 3);
				if (\count($parts) < 3) {
					continue; // línea no válida
				}

				$timestamp = htmlspecialchars(trim($parts[0]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
				$levelRaw  = strtoupper(trim($parts[1]));
				$message   = htmlspecialchars($parts[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

				// Validar nivel
				if (!isset($levelStyles[$levelRaw])) {
					continue; // nivel desconocido -> ignoramos la línea
				}

				$style = $levelStyles[$levelRaw];
				// Construimos la línea formateada
				$html .= '<span style="' . $style . '">[' . $timestamp . '] ' . $message . '</span><br/>' . "\n";
			}
		} finally {
			fclose($fp);
		}

		// 7) Si no hubo contenido válido, devolvemos un mensaje suave (sin echo)
		if ($html === '') {
			return '<p>' . Text::_('COM_SECURITYCHECKPRO_LOG_ERROR_LOGFILENOTEXISTS') . '</p>';
		}

		return $html;
	}

    
	/**
	 * Cambia permisos de archivos/carpetas detectados como inseguros y registra el resultado.
	 *
	 *
	 * @return void
	 */
	function repair(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		// Marcar en estado de usuario que se lanza reparación
		$app->setUserState('repair_launched', true);

		// Cargar stack desde el archivo JSON
		$stackFile = $this->folder_path . DIRECTORY_SEPARATOR . $this->filemanager_name;
		$raw = '';
		if (is_file($stackFile) && is_readable($stackFile)) {
			$raw = (string) file_get_contents($stackFile);
			// Quitar la cabecera anti-lectura
			$raw = str_replace("#<?php die('Forbidden.'); ?>", '', $raw);
		}

		if ($raw === '') {
			$this->Stack = [];
			$this->files_scanned = 0;
			$this->files_with_incorrect_permissions = 0;
			return;
		}

		$json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

		// Validación básica de estructura
		if (!is_array($json) || !isset($json['files_folders']) || !is_array($json['files_folders'])) {
			// Estructura inesperada
			$this->Stack = [];
			$this->files_scanned = 0;
			$this->files_with_incorrect_permissions = 0;
			return;
		}

		// Filtrar elementos inseguros
		$candidates = array_values(
			array_filter(
				$json['files_folders'],
				static function ($element): bool {
					return is_array($element)
						&& isset($element['safe'])
						&& (int) $element['safe'] === 0
						&& isset($element['path'])
						&& is_string($element['path'])
						&& $element['path'] !== '';
				}
			)
		);

		if ($candidates === []) {
			$this->write_permission_log([]);
			$this->setCampoFilemanager('estado_cambio_permisos', 'ENDED');
			return;
		}

		// Opción de cambio de permisos
		$params = ComponentHelper::getParams('com_securitycheckpro');
		$changeOption = (string) $params->get('change_permissions_option', 'chmod');

		$logEntries = []; // cada entrada: "LEVEL|mensaje"

		// Preparar FTP si procede
		$ftp = null;
		if ($changeOption === 'ftp') {
			$ftpOptions = ClientHelper::getCredentials('ftp');
			if (!empty($ftpOptions['enabled'])) {
				try {
					/** @var FtpClient $ftp */
					$ftp = FtpClient::getInstance(
						(string) ($ftpOptions['host'] ?? 'localhost'),
						(int) ($ftpOptions['port'] ?? 21),
						[],
						(string) ($ftpOptions['user'] ?? ''),
						(string) ($ftpOptions['pass'] ?? '')
					);
				} catch (\Throwable $e) {
					$logEntries[] = 'ERROR|' . Text::_('COM_SECURITYCHECKPRO_REPAIR_FTP_CONNECTION_FAILED') . ' ' . $e->getMessage();
					// Replegar a chmod si FTP falla
					$changeOption = 'chmod';
					$ftp = null;
				}
			} else {
				// Sin FTP habilitado -> replegar a chmod
				$changeOption = 'chmod';
			}
		}

		foreach ($candidates as $element) {
			$path = (string) $element['path'];

			// Determinar permisos destino (0644 archivos / 0755 directorios)
			// Preferimos detección real del FS a depender de una etiqueta traducida
			$isDir = is_dir($path);
			$targetPerms = $isDir ? 0755 : 0644;

			try {
				$ok = false;

				if ($changeOption === 'chmod') {
					// Evitar @; capturamos warnings como excepciones si ocurre
					set_error_handler(static function () { /* swallow to convert after */ });
					try {
						$ok = chmod($path, $targetPerms);
					} finally {
						restore_error_handler();
					}
				} else {
					// FTP
					if ($ftp instanceof FtpClient) {
						$ok = $ftp->chmod($path, $targetPerms);
					} else {
						// Defensa por si cambió changeOption en runtime
						$ok = false;
					}
				}

				if ($ok) {
					$logEntries[] = 'OK|' . $path . ' ' . Text::_('COM_SECURITYCHECKPRO_REPAIR_CHANGE_PERMISSIONS_OK');
				} else {
					$logEntries[] = 'ERROR|' . $path . ' ' . Text::_('COM_SECURITYCHECKPRO_REPAIR_CHANGE_PERMISSIONS_FAILED');
				}
			} catch (\Throwable $e) {
				$logEntries[] = 'ERROR|' . $path . ' ' . Text::_('COM_SECURITYCHECKPRO_REPAIR_CHANGE_PERMISSIONS_FAILED') . ' ' . $e->getMessage();
			}
		}

		// Escribir log y actualizar estado
		$this->write_permission_log($logEntries);
		$this->setCampoFilemanager('estado_cambio_permisos', 'ENDED');

		// Relanzar escaneo para refrescar resultados
		$this->setCampoFilemanager('estado', 'IN_PROGRESS');
		$this->scan('permissions');
		$this->setCampoFilemanager('estado', 'ENDED');
	}

    /**
     * Función para la paginación
     *
     *
     * @return  Pagination
     *     
     */
    function getPagination()
    {
        // Cargamos el contenido si es que no existe todavía
        if ($this->_pagination === null) {             
            $this->_pagination = new Pagination($this->total, $this->getState('limitstart'), $this->getState('limit'));        
        }
        return $this->_pagination;
    }

    /**
	 * Cambia a '1' el valor del campo 'safe_integrity' de todos los ficheros marcados como no seguros (0).
	 *
	 *
	 * @return void
	 */
	public function markAllUnsafeFilesAsSafe(): void
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// --- Carga del fichero de integridad ---
		$stackContent = null;
		$integrityPath = $this->folder_path . DIRECTORY_SEPARATOR . $this->fileintegrity_name;

		if (is_file($integrityPath) && is_readable($integrityPath)) {
			$stackContent = file_get_contents($integrityPath);
			if ($stackContent !== false) {
				// Eliminamos la cabecera de protección
				$stackContent = str_replace("#<?php die('Forbidden.'); ?>", '', $stackContent);
			}
		}

		if (empty($stackContent)) {
			return;
		}

		/** @var array<string,mixed>|null $stack */
		$stack = json_decode($stackContent, true);
		if (!is_array($stack) || !isset($stack['files_folders']) || !is_array($stack['files_folders'])) {
			// JSON inválido o estructura inesperada
			return;
		}

		// --- Carga del resumen desde BBDD ---
		$resumeQuery = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote('fileintegrity_resume'));

		$db->setQuery($resumeQuery);
		$stackResumeJson = (string) $db->loadResult();

		/** @var array<string,mixed>|null $stackResume */
		$stackResume = json_decode($stackResumeJson, true);
		if (!is_array($stackResume)) {
			// Si no hay resumen válido, asumimos que no hay nada que actualizar
			return;
		}

		$filesWithIncorrectIntegrity = (int) ($stackResume['files_with_incorrect_integrity'] ?? 0);
		if ($filesWithIncorrectIntegrity <= 0) {
			// Nada que marcar como seguro
			return;
		}

		// --- Carga de idioma y preparación de propiedades usadas por la clase ---
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app  = Factory::getApplication();
		$lang = $app->getLanguage();
		$lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

		// Copiamos la estructura y valores del resumen (si existen)
		$this->Stack_Integrity               = $stack['files_folders']; // array<int,array<string,mixed>>
		$this->files_scanned_integrity       = (int) ($stackResume['files_scanned_integrity'] ?? 0);
		$this->files_with_incorrect_integrity = 0; // los vamos a “sanear”
		$this->last_check_integrity          = (string) ($stackResume['last_check_integrity'] ?? '');
		$this->time_taken                    = (string) ($stackResume['time_taken'] ?? '');
		$this->last_scan_info                = (array) ($stackResume['last_scan_info'] ?? '');

		$app->setUserState('time_taken_set', $this->time_taken);

		// Evitamos límite de tiempo una sola vez (si está permitido por la configuración)
		@set_time_limit(0);

		$updated = false;
		$okNote  = Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');

		// Recorremos por referencia para modificar in-place
		foreach ($this->Stack_Integrity as &$entry) {
			$current = (int) ($entry['safe_integrity'] ?? 1);
			if ($current === 0) {
				$entry['notes']          = $okNote;
				$entry['safe_integrity'] = 1;
				$entry['new_file']       = 0;
				$updated = true;
			}
		}
		unset($entry); // romper la referencia

		// Guardamos sólo si hubo cambios
		if ($updated) {
			$this->saveStack('integrity', false);
		}

		// --- Limpieza de instalaciones previas (DELETE con query builder) ---
		try {
			$delete = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('installs'));

			$db->setQuery($delete);
			$db->execute();
		} catch (\Throwable $e) {
			// Silencioso: no impedimos el flujo principal por un fallo de limpieza
		}
	}

    /**
	 * Marca como seguros (safe_integrity = 1) los ficheros seleccionados en el formulario.
	 *
	 *
	 * @return void
	 */
	public function markCheckedFilesAsSafe(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app   = Factory::getApplication();
		$input = $app->getInput();

		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// --- 1) Leer selección del usuario (array de rutas) ---
		/** @var array<int, string> $filenames */
		$filenames = (array) $input->get('filesintegritystatus_table', [], 'array');
		if ($filenames === []) {
			return; // Nada que hacer
		}

		// Normalizamos y desduplicamos rutas (evita trabajo innecesario)
		$filenames = array_values(array_unique(array_map(static fn($v) => (string) $v, $filenames)));

		// --- 2) Carga del fichero de integridad ---
		$integrityPath = $this->folder_path . DIRECTORY_SEPARATOR . $this->fileintegrity_name;
		if (!is_file($integrityPath) || !is_readable($integrityPath)) {
			return;
		}

		$stackContent = file_get_contents($integrityPath);
		if ($stackContent === false || $stackContent === '') {
			return;
		}

		// Eliminamos la cabecera de protección
		$stackContent = str_replace("#<?php die('Forbidden.'); ?>", '', $stackContent);

		/** @var array<string,mixed>|null $stack */
		$stack = json_decode($stackContent, true);
		if (!is_array($stack) || !isset($stack['files_folders']) || !is_array($stack['files_folders'])) {
			return; // Estructura inesperada
		}

		// --- 3) Carga del resumen desde BBDD ---
		$resumeQuery = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote('fileintegrity_resume'));

		$db->setQuery($resumeQuery);
		$stackResumeJson = (string) $db->loadResult();

		/** @var array<string,mixed>|null $stackResume */
		$stackResume = json_decode($stackResumeJson, true);
		if (!is_array($stackResume)) {
			return;
		}

		$filesWithIncorrectIntegrity = (int) ($stackResume['files_with_incorrect_integrity'] ?? 0);
		if ($filesWithIncorrectIntegrity <= 0) {
			return; // Nada marcado como incorrecto
		}

		// --- 4) Preparar idioma y propiedades de clase ---
		$lang = $app->getLanguage();
		$lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

		/** @var array<int,array<string,mixed>> $entries */
		$entries = $stack['files_folders'];

		// Mapa rápido: path => índice
		$pathToIndex = [];
		foreach ($entries as $idx => $row) {
			$path = (string) ($row['path'] ?? '');
			if ($path !== '' && !isset($pathToIndex[$path])) {
				$pathToIndex[$path] = $idx;
			}
		}

		$updatedCount = 0;
		$okNote       = Text::_('COM_SECURITYCHECKPRO_FILEINTEGRITY_OK');

		// --- 5) Aplicar cambios únicamente a los seleccionados que existan en la pila ---
		foreach ($filenames as $path) {
			if ($path === '' || !isset($pathToIndex[$path])) {
				continue;
			}

			$i = $pathToIndex[$path];

			$current = (int) ($entries[$i]['safe_integrity'] ?? 1);
			if ($current === 0) {
				$entries[$i]['safe_integrity'] = 1;
				$entries[$i]['new_file']       = 0;
				$entries[$i]['notes']          = $okNote;
				$updatedCount++;
			}
		}

		if ($updatedCount === 0) {
			return; // No hubo cambios reales
		}

		// --- 6) Actualizar propiedades internas y guardar ---
		$this->Stack_Integrity                 = $entries;
		$this->files_scanned_integrity         = (int) ($stackResume['files_scanned_integrity'] ?? 0);
		$this->files_with_incorrect_integrity  = max(0, $filesWithIncorrectIntegrity - $updatedCount);
		$this->time_taken                      = (string) ($stackResume['time_taken'] ?? '');
		$this->last_scan_info                  = (array) ($stackResume['last_scan_info'] ?? '');

		$app->setUserState('time_taken_set', $this->time_taken);

		$this->saveStack('integrity', false);
	}

    /**
     * Chequea archivos contra el servicio OPWAST Metadefender Cloud
     *
     *
     * @return  bool
     *     
     */
    function online_check_files()
    {
        // Inicializamos las variables
        $this->analized_keys_array = [];
        $error = false;
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
    
        // Metadefender Cloud API V4
        $api    = 'https://api.metadefender.com/v4/file';    
    
        // Obtenemos la API key
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $apikey = $params->get('opswat_key', '');
    
        // Creamos el objeto Input para obtener las variables del formulario
		/** @var \Joomla\CMS\Application\CMSApplication $jinput */
        $jinput = Factory::getApplication();
        
        // Obtenemos las rutas de los ficheros a analizar
        $paths = $jinput->getInput()->get('malwarescan_status_table', null, 'array');
    
        // Chequeamos si la función 'curl_init' está definida. Si no lo está mostramos un error y salimos de la función
        if (!function_exists('curl_init')) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_CURL_NOT_DEFINED'));
            return $error;
        }
            
        if (!empty($paths)) {            
            // Creamos el nuevo objeto query
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
        
            // Cargamos el contenido del fichero de archivos sospechosos
            $malwarescan_data = $this->loadStack("malwarescan", "malwarescan", true);
            // Creamos un array de rutas para modificar los elementos que hayan sido escaneados
            $array_paths = array_map(
                function ($element) {
                    return $element['path']; 
                }, $malwarescan_data
            );
        
            // Obtenemos el número de archivos que podemos mandar		
			$prevention_api_remaining = $mainframe->getUserState("prevention_api_remaining", 100);
			
            // Chequeamos si sobrepasamos el límite de archivos que podemos mandar
            if ( count($paths) <= $prevention_api_remaining) {
                foreach($paths as $path) 
                {        
                    // Buscamos la clave del array a modificar
                    $array_key = array_search($path, $array_paths);
                
                    // Si tenemos un 'data_id' válido no volvemos a preguntar por uno al servicio online. Esto significa que ya hemos remitido el fichero para su analisis.
                    if (empty($malwarescan_data[$array_key]['data_id'])) {
                            
                        // Path sanitizada
                        $file = $db->escape($path);
                    
                        // Build headers array.
                        $headers = array(
                         'apikey: '.$apikey,
                         'filename: '.$file
                        );
						
						$post = array('file'=> new \CURLFile($file));

                        // Build options array.
                        $options = array(
                         CURLOPT_URL     => $api,
                         CURLOPT_HTTPHEADER  => $headers,
                         CURLOPT_POST        => true,
                         CURLOPT_POSTFIELDS  => $post,
                         CURLOPT_RETURNTRANSFER  => true,
                         CURLOPT_CAINFO    =>    SCP_CACERT_PEM,
                         CURLOPT_SSL_VERIFYHOST    => 2,
                         CURLOPT_SSL_VERIFYPEER  => true
                        );

                        // Init & execute API call.
                        $ch = curl_init();
                        curl_setopt_array($ch, $options);
                        $response = json_decode(curl_exec($ch), true);
						                   
                        // Obtenemos el resultado de la consulta. Cualquier código devuelto diferente a 200 indicará un error.
                        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						                    
                        if ($http_status == 200) {
                                    
                            // Buscamos la clave del array a modificar
                            $array_key = array_search($path, $array_paths);
                            // Almacenamos el valor encontrado para utilizarlo posteriormente
                            array_push($this->analized_keys_array, $array_key);
                        
                            // Y añadimos el campo 'data_id'
							//$data_id = 'bzI1MDIxMjRPNG02MkEyMXU0NG5EMnRYQVMy_mdaas';
                            $data_id = $response['data_id'];
                            $malwarescan_data[$array_key]['data_id'] = $data_id;                           
                        
                            // Incrementamos el valor de la variable de archivos analizados
                            $this->analized_files_last_hour++;    
                        
                        } else
                         {
                            Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ERROR_RETURNED', $http_status), 'error');
                        }
                    } else
                    {
                        // Almacenamos el valor encontrado para utilizarlo posteriormente
                        array_push($this->analized_keys_array, $array_key);                    
                    }
                }
            
                // Actualizamos los valores de los campos relacionados con el analisis online
                $this->setCampoFilemanager('online_checked_files', $this->analized_files_last_hour);
				$timestamp = $this->get_Joomla_timestamp();
                $this->setCampoFilemanager('last_online_check_malwarescan', $timestamp);     

                // Buscamos el resultado de los análisis. Para ello preguntamos al servicio Metadefender Cloud sobre cada 'result_id' devuelto.
                $this->look_for_results($apikey, $malwarescan_data, "files");    
            } else
            {
                Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_REACHED_ONLINE_FILES'), 'error');
                $error = true;
            }
        } else 
        {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_FILES_SELECTED'), 'error');    
            $error = true;
        }
		
		// Set the "get_apikey_info_and_limits" var to 1 to check for new API limits
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
		$mainframe->setUserState("get_apikey_info_and_limits", 1);
    
        return $error;
    
    }

    /**
     * Chequea hashes contra el servicio OPWAST Metadefender Cloud
     *
     *
     * @return  bool
     *     
     */
    function online_check_hashes()
    {
    
        // Inicializamos las variables
        $this->analized_keys_array = array();
        $error = false;
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
    
        // Obtenemos la API key
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $apikey = $params->get('opswat_key', '');
    
        // Creamos el objeto Input para obtener las variables del formulario
		/** @var \Joomla\CMS\Application\CMSApplication $jinput */
        $jinput = Factory::getApplication();
    
        // Obtenemos las rutas de los ficheros a analizar
        $paths = $jinput->getInput()->get('malwarescan_status_table', null, 'array');
                
        if (!empty($paths)) {    
        
            // Creamos el nuevo objeto query
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);
        
            // Cargamos el contenido del fichero de archivos sospechosos
            $malwarescan_data = $this->loadStack("malwarescan", "malwarescan", true);
                
            // Creamos un array de rutas para modificar los elementos que hayan sido escaneados
            $array_paths = array_map(
                function ($element) {
                    return $element['path']; 
                }, $malwarescan_data
            );
        
			// Obtenemos el número de hashes que podemos mandar
            $reputation_api_remaining = $mainframe->getUserState("reputation_api_remaining", 3000);
        
            // Chequeamos si sobrepasamos el límite de hashes a analizar
            if ( count($paths) <= $reputation_api_remaining ) {
                foreach($paths as $path)
                 {        
                    // Buscamos la clave del array a modificar
                    $array_key = array_search($path, $array_paths);
                    // Almacenamos el valor encontrado para utilizarlo posteriormente
                    array_push($this->analized_keys_array, $array_key);                    
                }
            
                 // Preguntamos directamente al servicio online por cada valor hash seleccionado.
                 $this->look_for_results($apikey, $malwarescan_data, "hashes");    
            } else
            {
                Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_REACHED_ONLINE_FILES'), 'error');
                $error = true;
            }
    
        } else 
        {
            Factory::getApplication()->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_FILES_SELECTED'), 'error');    
            $error = true;
        }
		
		// Set the "get_apikey_info_and_limits" var to 1 to check for new API limits
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
		$mainframe->setUserState("get_apikey_info_and_limits", 1);
    
        return $error;
        
    }

    /**
     * Obtiene nuestros límites en OPSWAT
     *
     *
     * @return  string|void
     *     
     */
    function get_apikey_info_and_limits()
    {
        // Obtenemos la API key
        $params = ComponentHelper::getParams('com_securitycheckpro');
        $apikey = $params->get('opswat_key', '');
				
		/** @var \Joomla\CMS\Application\CMSApplication $mainframe */
		$mainframe = Factory::getApplication();
				
		$response = null;
		
		if (!empty($apikey)) {
			// El escaneo del archivo se está realizando
				$api = 'https://api.metadefender.com/v4/apikey/limits/status/';
                                
                //Build headers array.
                $headers = array(
                'apikey: '.$apikey
                );

                //Build options array.
                $options = array(                    
                CURLOPT_URL     => $api,
                CURLOPT_HTTPHEADER  => $headers,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_CAINFO    =>    SCP_CACERT_PEM,
                CURLOPT_SSL_VERIFYHOST    => 2,
                CURLOPT_SSL_VERIFYPEER  => true
                );

                //Init & execute API call.
                $ch = curl_init();
                curl_setopt_array($ch, $options);
                $response = json_decode(curl_exec($ch), true);
								
				// Obtenemos el resultado de la consulta. Cualquier código devuelto diferente a 200 indicará un error.
                $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
                if ($http_status == 200) {
                    if ( (!empty($response)) && (is_array($response)) ) {
						$mainframe->setUserState("reputation_api_remaining", $response['reputation_api']);
						$mainframe->setUserState("prevention_api_remaining", $response['prevention_api']);
					}                        
                } else
                {
                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ERROR_RETURNED', $http_status), 'error');
					return;
                }             
		}
		
		// Set the "get_apikey_info_and_limits" var to 0 to avoid launching this function everytime we visit the malwarescan option		
		$mainframe->setUserState("get_apikey_info_and_limits", 0);
				
		return $response;
		
	}

   	/**
     * Función que obtiene el resultado de cada uno de los archivos o hashes escaneados online
     *
     * @param   string            			 $apikey    		   The API key
	 * @param   array<array<string>>|null 	 $malwarescan_data     The data to send to the API
	 * @param   string             			 $opcion    		   The option (files, hashes)
     *
     * @return  void
     *     
     */
    private function look_for_results($apikey,$malwarescan_data,$opcion)
    {

        /* Inicializamos las variables */
        $array_infected_files = array();
        $json_infected_files = null;
		$file_analysis_result = null;
		$threats_found = 0;
		$response = "";
    
        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);
    
        // Inicializamos las variables
        switch ($opcion)
        {
        case "files":
            $file_analysis_result = "#<?php die('Forbidden.'); ?>" . PHP_EOL . "<h3>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_FILE_SCAN') . "</h3>" . PHP_EOL;
            break;
        case "hashes":
            $file_analysis_result = "#<?php die('Forbidden.'); ?>" . PHP_EOL . "<h3>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_HASHES_SCAN') . "</h3>" . PHP_EOL;
            break;
        }
          
        foreach ($this->analized_keys_array as $array_key)
        {    
            switch ($opcion)
            {
            case "files":
				
				// El escaneo del archivo se está realizando
				$api        = 'https://api.metadefender.com/v4/file/' .$malwarescan_data[$array_key]['data_id'];
                                
                //Build headers array.
                $headers = array(
                'apikey: '.$apikey
                );

                //Build options array.
                $options = array(                    
                CURLOPT_URL     => $api,
                CURLOPT_HTTPHEADER  => $headers,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_CAINFO    =>    SCP_CACERT_PEM,
                CURLOPT_SSL_VERIFYHOST    => 2,
                CURLOPT_SSL_VERIFYPEER  => true
                );

                $response = "";
                //Init & execute API call.
                $ch = curl_init();
                curl_setopt_array($ch, $options);
                
                do
                {
                    $response = json_decode(curl_exec($ch), true);
                }
							               
				while ($response["process_info"]["progress_percentage"] != 100);
				
				/* We will get something like this:
				Array
				(
					[last_sandbox_id] => Array
						(
						)

					[last_start_time] => 2025-02-12T09:45:48.608Z
					[scan_result_history_length] => 1
					[votes] => Array
						(
							[down] => 0
							[up] => 0
						)

					[sandbox] => 
					[file_id] => bzI1MDIxMjRPNG02MkEyMXU
					[data_id] => bzI1MDIxMjRPNG02MkEyMXU0NG5EMnRYQVMy_mdaas
					[process_info] => Array
						(
							[progress_percentage] => 100
							[result] => Allowed
							[post_processing] => Array
								(
									[actions_failed] => 
									[actions_ran] => 
									[converted_destination] => 
									[converted_to] => 
									[copy_move_destination] => 
								)

							[verdicts] => Array
								(
									[0] => No Threat Detected
								)

							[blocked_reason] => 
							[profile] => multiscan
							[blocked_reasons] => Array
								(
								)

						)

					[scan_results] => Array
						(
							[scan_details] => Array
								(
									[AhnLab] => Array
										(
											[scan_time] => 14
											[def_time] => 2025-02-11T00:00:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Avira] => Array
										(
											[scan_time] => 7
											[def_time] => 2025-02-10T09:55:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Bitdefender] => Array
										(
											[scan_time] => 3
											[def_time] => 2025-02-10T08:52:06.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Bkav Pro] => Array
										(
											[scan_time] => 221
											[def_time] => 2025-02-10T15:30:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[ClamAV] => Array
										(
											[scan_time] => 27
											[def_time] => 2025-02-10T09:19:51.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[CMC] => Array
										(
											[scan_time] => 1
											[def_time] => 2025-02-10T17:43:43.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[CrowdStrike Falcon ML] => Array
										(
											[scan_result_i] => 23
											[scan_time] => 1
											[def_time] => 2025-02-10T00:00:00.000Z
											[threat_found] => 
										)

									[Emsisoft] => Array
										(
											[scan_time] => 16
											[def_time] => 2025-02-10T03:35:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[IKARUS] => Array
										(
											[scan_time] => 2
											[def_time] => 2025-02-10T08:50:16.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[K7] => Array
										(
											[scan_time] => 6
											[def_time] => 2025-02-10T01:20:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[McAfee] => Array
										(
											[scan_time] => 19
											[def_time] => 2025-02-09T00:00:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[NANOAV] => Array
										(
											[def_time] => 2025-02-10T04:26:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Quick Heal] => Array
										(
											[scan_time] => 4
											[def_time] => 2025-02-09T22:18:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[RocketCyber] => Array
										(
											[scan_result_i] => 23
											[scan_time] => 3
											[def_time] => 2025-02-10T00:00:00.000Z
											[threat_found] => 
										)

									[Sophos] => Array
										(
											[scan_time] => 66
											[def_time] => 2025-02-10T00:46:24.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[TACHYON] => Array
										(
											[scan_time] => 2
											[def_time] => 2025-02-10T00:00:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Varist] => Array
										(
											[scan_time] => 46
											[def_time] => 2025-02-10T09:49:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Webroot SMD] => Array
										(
											[scan_result_i] => 23
											[scan_time] => 3
											[def_time] => 2025-02-09T21:00:16.000Z
											[threat_found] => 
										)

									[Xvirus Anti-Malware] => Array
										(
											[scan_time] => 15
											[def_time] => 2025-02-09T19:35:03.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Zillya!] => Array
										(
											[def_time] => 2025-02-07T21:09:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Vir.IT eXplorer] => Array
										(
											[def_time] => 2025-02-07T12:45:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

									[Vir.IT ML] => Array
										(
											[def_time] => 2025-02-07T12:45:00.000Z
											[scan_result_i] => 0
											[threat_found] => 
										)

								)

							[scan_all_result_i] => 0
							[current_av_result_i] => 0
							[start_time] => 2025-02-12T09:45:48.053Z
							[total_time] => 221
							[total_avs] => 22
							[total_detected_avs] => 0
							[progress_percentage] => 100
							[scan_all_result_a] => No Threat Detected
							[current_av_result_a] => No Threat Detected
						)

					[file_info] => Array
						(
							[file_size] => 340
							[upload_timestamp] => 2025-02-12T09:45:47.521Z // No siempre viene
							[md5] => 5D279D36F6E4B0EFD571A8277EA8F8A4
							[sha1] => B0F575938E81919E280738A2A2C0A71664A1CC1F
							[sha256] => 37CE21C1E59F5AD10155E2E369B3A1C344E34D2C96CEF4CE3C18E8B7F396B78E
							[file_type_category] => T
							[file_type_description] => PHP Hypertext Preprocessor
							[file_type_extension] => php
							[display_name] => /var/www/cli/thumbnail_92.php.png
						)

					[share_file] => 1
					[private_processing] => 0
					[rest_version] => 4
					[additional_info] => Array
						(
						)

					[stored] => 1
				)*/
                
                // Una vez finalizado el escaneo, hacemos una petición más para obtener el resultado
                $api        = 'https://api.metadefender.com/v4/file/' .$malwarescan_data[$array_key]['data_id'];
                
                //Build headers array.
                $headers = array(
                'apikey: '.$apikey
                );

                //Build options array.
                $options = array(
                CURLOPT_URL     => $api,
                CURLOPT_HTTPHEADER  => $headers,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_CAINFO    =>    SCP_CACERT_PEM,
                CURLOPT_SSL_VERIFYHOST    => 2,
                CURLOPT_SSL_VERIFYPEER  => true
                );

                $response = "";
                //Init & execute API call.
                $ch = curl_init();
                curl_setopt_array($ch, $options);
                
                $response = json_decode(curl_exec($ch), true);
                break;
            case "hashes":
                     // Establecemos el valor del hash en la variable 'api'
                     $api = 'https://api.metadefender.com/v4/hash/' . $malwarescan_data[$array_key]['sha1_value'];
                            
                     // Build headers array.
                     $headers = array(
                     'apikey: '.$apikey
                     );

                     // Build options array.
                     $options = array(                    
                     CURLOPT_URL     => $api,
                     CURLOPT_HTTPHEADER  => $headers,
                     CURLOPT_RETURNTRANSFER  => true,
                     CURLOPT_CAINFO    =>    SCP_CACERT_PEM,
                     CURLOPT_SSL_VERIFYHOST    => 2,
                     CURLOPT_SSL_VERIFYPEER  => true
                     );

                     // Init & execute API call.
                     $ch = curl_init();
                     curl_setopt_array($ch, $options);
                
                     $response = json_decode(curl_exec($ch), true);
                
                     // Incrementamos el valor de la variable de archivos analizados
                     $this->analized_hashes_last_hour++;                
                
                break;
            }
			        
            if (is_array($response)) {                    
                // Guardamos el resultado del escaneo online
                $malwarescan_data[$array_key]['online_check'] = $response["scan_results"]["scan_all_result_i"];
        
                // Guardamos el resultado del escaneo online
                $malwarescan_data[$array_key]['online_check'] = $response["scan_results"]["scan_all_result_i"];
            
                if (!array_key_exists("scan_results", $response)) {
                    // El hash no se ha encontrado pero el resultado del escaneo es un array con el formato "hash = Not found"
                    // Guardamos el resultado del escaneo online (le asignamos el valor '15')
                    $malwarescan_data[$array_key]['online_check'] = 15;
                    // Añadimos el resultado a la variable que será volcada en el fichero de resultados. Pasamos los datos del fichero ya que el hash no se ha encontrado en la BBDD
                    $file_analysis_result .= $this->format_data($response, true, $malwarescan_data[$array_key]);
                } else 
                {        
                    // Actualizamos la variable de amenazas encontradas si es que se han encontrado
                    if (($response["scan_results"]["scan_all_result_i"] == 1) || ($response["scan_results"]["scan_all_result_i"] == 2)) {
                        $threats_found++;
                    
                        // Extraemos sólo el nombre del fichero. Como los valores hash pueden corresponder a ficheros con caracteres de separación (/ y \) de otros sistema operativo, hemos de buscar y reemplazar los que puedan existir por el del sistema operativo que opera (que vendrá dado por DIRECTORY_SEPARATOR)
                        $nombre = $response["file_info"]["display_name"];
                        $to_change = array("/","\\");
                        $nombre = str_replace($to_change, DIRECTORY_SEPARATOR, $nombre);
                        $nombre = basename($nombre);
                    
                        // Añadimos el nombre al array de ficheros infectados
                        $array_infected_files[] = $nombre;    
                    } 
                
                    // Añadimos el resultado a la variable que será volcada en el fichero de resultados
                    $file_analysis_result .= $this->format_data($response);
                }
            } else 
            {
                  // Guardamos el resultado del escaneo online (le asignamos el valor '15')
                  $malwarescan_data[$array_key]['online_check'] = 15;
            
                  // Añadimos el resultado a la variable que será volcada en el fichero de resultados. Pasamos los datos del fichero ya que el hash no se ha encontrado en la BBDD
                  $file_analysis_result .= $this->format_data($response, true, $malwarescan_data[$array_key]);
            }
        }
        
        // Cambiamos el formato del array a json para almacenarlo en la bbdd
        if (!empty($array_infected_files)) {
            $json_infected_files = json_encode($array_infected_files);
        }
    
        // Si la opción seleccionada es el escaneo de hashes, actualizamos las variables correspondientes en la bbdd.
        if ($opcion == "hashes") {
            // Actualizamos los valores de los campos relacionados con el analisis online
            $this->setCampoFilemanager('online_checked_hashes', $this->analized_hashes_last_hour);
			$timestamp = $this->get_Joomla_timestamp();
            $this->setCampoFilemanager('last_online_check_malwarescan', $timestamp);    
        }
    
        // Borramos el fichero del escaneo anterior...
		try{		
			if (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name)) {
				$delete_malwarescan_file = File::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name);
			}
		} catch (\Exception $e)
		{
		}
                    
        // ... y almacenamos el nuevo contenido
        try
        {
			// Convert the array to utf-8 to avoid errors with the json_encode function
			if (version_compare(PHP_VERSION, '7.2.0', 'gt')) {    
				$malwarescan_data = mb_convert_encoding($malwarescan_data,'UTF-8', 'UTF-8');			
			}
			
			$content_malwarescan = json_encode(array('files_folders'    => $malwarescan_data));
            $content_malwarescan = "#<?php die('Forbidden.'); ?>" . PHP_EOL . $content_malwarescan;
            $result_malwarescan = File::write($this->folder_path.DIRECTORY_SEPARATOR.$this->malwarescan_name, $content_malwarescan);
        } catch (\Exception $e)
        {
                
        }
    
        // Comprobamos si hay algo que escribir
        if (!is_null($file_analysis_result)) {
            // Escribimos el contenido del buffer en un fichero
            $this->writeFileOnlineScan($file_analysis_result, $threats_found, count($this->analized_keys_array), $json_infected_files);
        }
    }

    /**
     * Función que formatea los datos de entrada (en un array) para adaptarlos al del fichero
     *
     * @param   array<mixed, mixed>|mixed       $response    		The response sent by the Metadefender API
	 * @param   bool           	   				$not_found    		If the hash has been found in the database
	 * @param   array<string>     				$file_data    		The info of the file
     *
     * @return  string
     *     
     */
    private function format_data($response, $not_found = false, $file_data = null) 
    {    
        /* Cargamos el lenguaje del sitio */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

        // Inicializamos las variables
        $data = null;
        $scan_result = '';
		    
        // El hash se ha encontrado en la BBDD
        if (!$not_found) {
			$data = "<h4>" . $response["file_info"]["display_name"] . "</h4>" . PHP_EOL;
			
			// Saca el texto del lenguaje
			$label = $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_UPLOAD_TIMESTAMP');

			// Comprueba si existe y no es vacío
			$uploadTimestamp = $response['file_info']['upload_timestamp'] ?? null;

			if (!empty($uploadTimestamp)) {
				// Escapamos por seguridad (por si viene de entrada externa)
				$safeTimestamp = htmlspecialchars((string) $uploadTimestamp, ENT_QUOTES, 'UTF-8');
				$data .= "<p>{$label}: {$safeTimestamp}</p>" . PHP_EOL;
			}            
            
            $data .= "<p>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_FILE_SIZE') . ": " . $response["file_info"]["file_size"] . "</p>" . PHP_EOL;
            $data .= "<p>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_DATA_ID') . ": " . $response["data_id"] . "</p>" . PHP_EOL;
            $data .= "<p>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SHA256') . ": " . $response["file_info"]["sha256"] . "</p>" . PHP_EOL;
            
            switch ($response["scan_results"]["scan_all_result_i"])
            {
            case 0:
                $scan_result = "<span style=\"color: #008000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_CLEAN') . "</strong></span>";
                break;
            case 1:
                $scan_result = "<span style=\"color: #FF0000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_INFECTED') . "</strong></span>";
                break;
            case 2:
                $scan_result = "<span style=\"color: #FF4000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_SUSPICIOUS') . "</strong></span>";
                break;
            case 3:
                $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_FAILED_TO_SCAN') . "</strong></span>";
                break;
            case 4:
                $scan_result = "<span style=\"color: #000000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_CLEANED') . "</strong></span>";
                break;
            case 5:
                $scan_result = "<span style=\"color: #000000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_UNKNOW') . "</strong></span>";
                break;
            case 6:
                $scan_result = "<span style=\"color: #000000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_QUARANTINED') . "</strong></span>";
                break;
            case 7:
                $scan_result = "<span style=\"color: #000000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_SKIPPED_CLEAN') . "</strong></span>";
                break;
            case 8:
                $scan_result = "<span style=\"color: #000000;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_SKIPPED_DIRTY') . "</strong></span>";
                break;
            case 9:
                $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_EXCEEDED_DEPTH') . "</strong></span>";
            case 10:
                $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_NOT_SCANNED') . "</strong></span>";
                break;
            case 11:
                $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_ABORTED') . "</strong></span>";
                break;
            case 12:
                $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_ENCRYPTED') . "</strong></span>";
                break;
            case 13:
                $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_EXCEEDED_SIZE') . "</strong></span>";
                break;
            case 14:
                $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_EXCEEDED_FILE_NUMBER') . "</strong></span>";
                break;
            }
            $data .= "<p>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS') . ": " . $scan_result . "</p>" . PHP_EOL;
            $data .= "<p>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_TOTAL_AVS') . ": " . $response["scan_results"]["total_avs"] . "</p>" . PHP_EOL . PHP_EOL;
            
            // Actualizamos la variable de amenazas encontradas si es que se han encontrado
            if ($response["scan_results"]["scan_all_result_i"] == 1) {
                $data .= "<h5 style=\"color: #2E64FE;\">" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_AVS_RESULT') . "</h5>" . PHP_EOL;
                $data .= "<table border=\"1\">" . PHP_EOL;
                $data .= "<thead>" . PHP_EOL;
                $data .= "<tr>" . PHP_EOL;
                $data .= "<th>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_AV_ENGINE') . "</th>" . PHP_EOL;
                $data .= "<th>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_DEF_TIME') . "</th>" . PHP_EOL;
                $data .= "<th>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_TIME') . "</th>" . PHP_EOL;
                $data .= "<th>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_THREAT_FOUND') . "</th>" . PHP_EOL;
                $data .= "</tr>";
                $data .= "</thead>";
                $data .= "</tbody>";        
            
                // Extraemos los nombres de los motores de antivirus usados
                $av_engines =  array_keys($response["scan_results"]['scan_details']);
                $indice = 0;
                foreach ($response["scan_results"]['scan_details'] as $av)
                {
                       $data .= "<tr>" . PHP_EOL;
                    if (empty($av['threat_found'])) {
                        $data .= "<td style=\"text-align: center; vertical-align: middle;\">" . $av_engines[$indice] . "</td>" . PHP_EOL;
                    } else 
                    {
                        $data .= "<td style=\"text-align: center; vertical-align: middle;\"><font color=#5858FA>" . $av_engines[$indice] . "</font></td>" . PHP_EOL;
                    }
                    $data .= "<td style=\"text-align: center; vertical-align: middle;\">" . $av['def_time'] . "</td>" . PHP_EOL;
                    $data .= "<td style=\"text-align: center; vertical-align: middle;\">" . $av['scan_time'] . "</td>" . PHP_EOL;
                    if (empty($av['threat_found'])) {
                        $data .= "<td style=\"text-align: center; vertical-align: middle;\">" . $av['threat_found'] . "</td>" . PHP_EOL;
                    } else
                    {
                        $data .= "<td style=\"text-align: center; vertical-align: middle;\"><font color=red>" . $av['threat_found'] . "</font></td>" . PHP_EOL;
                    }
                    $data .= "</tr>" . PHP_EOL;
                    $indice++;
                }
                $data .= "</tbody>";
                $data .= "</table>" . PHP_EOL;
            }
        } else
        {
            $data = "<h4>" . $file_data["path"] . "</h4>" . PHP_EOL;
            $data .= "<p>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SHA256') . ": " . $file_data["sha1_value"] . "</p>" . PHP_EOL;
            $scan_result = "<span style=\"color: #61380B;\"><strong>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS_HASH_NOT_FOUND') . "</strong></span>";
            $data .= "<p>" . $lang->_('COM_SECURITYCHECKPRO_MALWARESCAN_SCAN_RESULTS') . ": " . $scan_result . "</p>" . PHP_EOL;
        }
    
        return $data;

    }
	
	/**
	 * Guarda el resultado del escaneo en un fichero y registra metadatos en BD.
	 *
	 * @param string              $fileAnalysisResult Contenido del análisis a guardar
	 * @param int|string          $threats            Amenazas detectadas (numérico)
	 * @param int|string          $filesChecked       Número de archivos analizados (numérico)
	 * @param array<string,mixed>|list<mixed>|string|null $infectedFiles  Lista de archivos infectados en JSON, o null.
	 *
	 * @return void
	 */
	private function writeFileOnlineScan(
		string $fileAnalysisResult,
		int|string $threats,
		int|string $filesChecked,
		array|string|null $infectedFiles
	): void {
		// 1) Recorte de histórico si aplica
		if (method_exists($this, 'checkLogsStored')) {
			$this->checkLogsStored();
		} elseif (method_exists($this, 'check_logs_stored')) {
			$this->check_logs_stored();
		}

		// 2) Generar nombre y ruta absoluta segura
		$filename = (string) $this->generateKey(); // debe ser sólo nombre
		if ($filename !== basename($filename)) {
			throw new \RuntimeException('Invalid filename generated.');
		}

		$absPath = Path::clean($this->folder_path . DIRECTORY_SEPARATOR . $filename);
		Path::check($absPath, $this->folder_path);

		// 3) Escritura atómica (tmp + rename)
		$tmp = @tempnam($this->folder_path, 'scpro_');
		if ($tmp === false) {
			throw new \RuntimeException('Unable to create temporary file for online check result.');
		}

		$bytes = @file_put_contents($tmp, $fileAnalysisResult, LOCK_EX);
		if ($bytes === false) {
			@unlink($tmp);
			throw new \RuntimeException('Failed to write online check result to temporary file.');
		}

		if (!@rename($tmp, $absPath)) {
			@unlink($tmp);
			throw new \RuntimeException('Failed to move temporary file to final destination.');
		}

		// 4) Normalización de datos para BD
		/** @var DatabaseInterface $db */
		$db  = Factory::getContainer()->get(DatabaseInterface::class);
		$now = Factory::getDate()->toSql();

		$filesCheckedInt = (int) $filesChecked;
		$threatsInt      = (int) $threats;

		// infected_files → JSON o NULL
		$infectedJson = null;
		if ($infectedFiles !== null && $infectedFiles !== '') {
			if (is_array($infectedFiles)) {
				$infectedJson = json_encode(
					$infectedFiles,
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
				);
			} elseif (is_string($infectedFiles)) {
				// Si ya es JSON válido y decodifica a array, lo conservamos.
				$decoded = json_decode($infectedFiles, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
					$infectedJson = $infectedFiles;
				} else {
					// No era JSON de array → lo envolvemos en un array y codificamos.
					$infectedJson = json_encode(
						[$infectedFiles],
						JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
					);
				}
			}
		}
		$infectedSql = $infectedJson === null ? 'NULL' : $db->quote($infectedJson);

		// 5) INSERT con tipos correctos
		$query = $db->getQuery(true)
			->insert($db->quoteName('#__securitycheckpro_online_checks'))
			->columns([
				$db->quoteName('filename'),
				$db->quoteName('files_checked'),
				$db->quoteName('threats_found'),
				$db->quoteName('scan_date'),
				$db->quoteName('infected_files'),
			])
			->values(implode(',', [
				$db->quote($filename),      // string
				(string) $filesCheckedInt,  // int
				(string) $threatsInt,       // int
				$db->quote($now),           // datetime
				$infectedSql,               // JSON o NULL
			]));

		$db->setQuery($query);
		$db->execute();
	}

	/**
     * Si han pasado ≥ 60 minutos desde el último escaneo online,
     * reinicia el contador de ficheros verificados online.
     */
    private function checkLastOnlinecheck(): void
    {
        // Último escaneo guardado (string fecha/hora) y "ahora"
        $lastCheck = (string) $this->GetCampoFilemanager('last_online_check_malwarescan');
        $nowStr    = (string) $this->get_Joomla_timestamp();

        // Normalizamos a timestamps (si falla, lo tratamos como 0 = muy antiguo)
        $nowTs      = strtotime($nowStr) ?: 0;
        $lastCheckTs= strtotime($lastCheck) ?: 0;

        // Si no había registro previo, evita división extra y re-inicia directamente
        if ($lastCheck === '' || $lastCheckTs === 0) {
            $this->setCampoFilemanager('online_checked_files', 0);
            return;
        }

        // Diferencia en segundos (no dependas de enteros negativos)
        $diff = max(0, $nowTs - $lastCheckTs);

        // ≥ 1 hora → re-inicia
        if ($diff >= 3600) {
            $this->setCampoFilemanager('online_checked_files', 0);
        }
    }


	/**
     * Mantiene el histórico de logs de escaneo online dentro del límite configurado.
     * Conserva los más recientes y elimina los más antiguos (fichero + fila en BD).
     */
    private function checkLogsStored(): void
    {
        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();

        // Límite configurado (por defecto 5); si es <1, no hacemos nada
        $params            = ComponentHelper::getParams('com_securitycheckpro');
        $logFilesToStore   = (int) $params->get('log_files_stored', 5);
        if ($logFilesToStore < 1) {
            return;
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // 1) Contar filas actuales
        $countQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__securitycheckpro_online_checks'));
        $db->setQuery($countQuery);

        $logsStored = (int) $db->loadResult();

        // Si no excede el límite, nada que hacer
        if ($logsStored <= $logFilesToStore) {
            return;
        }

        // 2) Obtener TODOS los nombres ordenados por fecha (más recientes primero)
        //    y eliminar a partir del índice $logFilesToStore (conserva los N más recientes).
        $listQuery = $db->getQuery(true)
            ->select($db->quoteName('filename'))
            ->from($db->quoteName('#__securitycheckpro_online_checks'))
            ->order($db->quoteName('scan_date') . ' DESC');
        $db->setQuery($listQuery);

        /** @var array<int,string> $filenames */
        $filenames = (array) $db->loadColumn();

        if ($filenames === []) {
            return;
        }

        $filesDeleted = 0;

        // 3) Eliminar del índice N en adelante
        $toDelete = array_slice($filenames, $logFilesToStore);

        foreach ($toDelete as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }

            // Ruta absoluta segura al fichero: limpia y fija a base
            $absPath = Path::clean($this->folder_path . DIRECTORY_SEPARATOR . $name);

            try {
                Path::check($absPath, $this->folder_path); // evita salir de la carpeta esperada
            } catch (\Throwable) {
                // Nombre/path inválido → intenta al menos borrar la fila de BD
                $this->deleteLogRowByFilename($name, $db);
                continue;
            }

            // Borrado físico: solo si existe y es archivo normal
            $deletedFile = false;
            try {
                if (is_file($absPath)) { // no uses File::exists (deprecado/retirado)
                    $deletedFile = File::delete($absPath);
                } else {
                    // Si ya no existe en disco, seguimos con borrado lógico en BD
                    $deletedFile = true;
                }
            } catch (\Throwable) {
                $deletedFile = false;
            }

            // Si ha ido bien, elimina la fila de BD
            if ($deletedFile) {
                if ($this->deleteLogRowByFilename($name, $db)) {
                    $filesDeleted++;
                }
            }
        }

        if ($filesDeleted > 0) {
            $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETED_OLD_FILES', $filesDeleted), 'message');
        }
    }

    /**
     * Elimina de la base de datos la fila del log por filename.
     *
     * @param string             $filename
     * @param DatabaseInterface  $db
     * @return bool  TRUE si se eliminó alguna fila
     */
    private function deleteLogRowByFilename(string $filename, DatabaseInterface $db): bool
    {
        try {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__securitycheckpro_online_checks'))
                ->where($db->quoteName('filename') . ' = ' . $db->quote($filename));
            $db->setQuery($query);
            $db->execute();

            return $db->getAffectedRows() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
	 * Restaura o elimina archivos en cuarentena y actualiza el estado del escaneo.
	 *
	 * @param  string $opcion  "restore"|"delete"
	 * @return void
	 */
	public function quarantinedFile(string $opcion): void
	{
		$opcion = strtolower($opcion);
		if (!in_array($opcion, ['restore', 'delete'], true)) {
			return;
		}

		// Rutas base
		$scansPath = rtrim(
			JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans',
			DIRECTORY_SEPARATOR
		);
		$quarantinePath = $scansPath . DIRECTORY_SEPARATOR . 'quarantine';

		if (!is_dir($quarantinePath) || !is_dir($scansPath)) {
			return;
		}

		// Carga el JSON del último escaneo
		$scanJsonPath = $this->getLatestMalwareScanFilename($scansPath, $this->malwarescan_name);
		if ($scanJsonPath === '' || !is_file($scanJsonPath)) {
			return;
		}

		$raw = @file_get_contents($scanJsonPath);
		if ($raw === false) {
			return;
		}

		// Quita cabecera de seguridad y decodifica
		$raw = str_replace("#<?php die('Forbidden.'); ?>", '', $raw);
		try {
			/** @var array{files_folders:list<array<string,mixed>>} $stack */
			$stack = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return;
		}

		if (!isset($stack['files_folders']) || !is_array($stack['files_folders'])) {
			return;
		}

		/** @var list<array<string,mixed>> $data */
		$data = $stack['files_folders'];

		// Obtiene selección del usuario
		$input = Factory::getApplication()->getInput();
		/** @var array<int,string> $paths */
		$paths = (array) $input->get('malwarescan_status_table', [], 'array');

		if ($paths === []) {
			return;
		}

		// Índice rápido: path => índice
		$indexByPath = [];
		foreach ($data as $i => $row) {
			if (isset($row['path']) && is_string($row['path']) && $row['path'] !== '') {
				$indexByPath[$row['path']] = $i;
			}
		}

		foreach ($paths as $requestedPath) {
			if (!is_string($requestedPath) || $requestedPath === '') {
				continue;
			}

			if (!isset($indexByPath[$requestedPath])) {
				continue;
			}

			$idx = $indexByPath[$requestedPath];
			$row = $data[$idx];

			$qfn = isset($row['quarantined_file_name']) && is_string($row['quarantined_file_name'])
				? $row['quarantined_file_name']
				: '';

			if ($qfn === '') {
				continue;
			}

			// Usa tu método de validación
			if (!$this->isPathInside($quarantinePath, $qfn)) {
				continue; // aborta si intenta acceder fuera de /quarantine
			}

			if ($opcion === 'restore') {
				$original = Path::clean($requestedPath);
				$targetDir = dirname($original);

				if (!is_dir($targetDir)) {
					Folder::create($targetDir, 0755);
				}

				$finalTarget = $original;
				if (file_exists($finalTarget)) {
					$finalTarget .= '.restored-' . date('Ymd-His');
				}

				try {
					$moved = File::move($qfn, $finalTarget);
				} catch (\Throwable) {
					$moved = false;
				}

				if ($moved) {
					$row['moved_to_quarantine'] = 0;
					$row['safe_malwarescan'] = 0;
					$row['quarantined_file_name'] = '';
					$data[$idx] = $row;
				}
			} else { // delete
				$deleted = false;
				try {
					if (file_exists($qfn)) {
						$deleted = File::delete($qfn);
					} else {
						$deleted = true;
					}
				} catch (\Throwable) {
					$deleted = false;
				}

				if ($deleted) {
					unset($data[$idx]);
				}
			}
		}

		$data = array_values($data);

		// Guarda JSON actualizado con cabecera protectora
		try {
			$payload = json_encode(['files_folders' => $data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
			if ($payload === null) {
				return;
			}

			$tmpFile = $scanJsonPath . '.tmp';
			$wrapped = "#<?php die('Forbidden.'); ?>\n" . $payload;

			if (version_compare(PHP_VERSION, '7.2.0', '>')) {
				$wrapped = mb_convert_encoding($wrapped, 'UTF-8', 'UTF-8');
			}

			if (File::write($tmpFile, $wrapped) !== false) {
				if (!@rename($tmpFile, $scanJsonPath)) {
					@copy($tmpFile, $scanJsonPath);
					@unlink($tmpFile);
				}
			}
		} catch (\Throwable) {
			return;
		}
	}

	/**
	 * Obtiene el fichero de resultados de malware vigente.
	 *
	 * @param  string $scansPath
	 * @param  string $fallbackName
	 * @return string Ruta absoluta o '' si no existe
	 */
	private function getLatestMalwareScanFilename(string $scansPath, string $fallbackName): string
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('storage_value'))
			->from($db->quoteName('#__securitycheckpro_storage'))
			->where($db->quoteName('storage_key') . ' = ' . $db->quote('malwarescan_resume'));

		$db->setQuery($query);

		try {
			$res = $db->loadResult();
		} catch (\Throwable) {
			$res = null;
		}

		$filename = '';
		if (is_string($res) && $res !== '') {
			try {
				/** @var array{filename?:string} $decoded */
				$decoded = json_decode($res, true, 16, JSON_THROW_ON_ERROR);
				if (isset($decoded['filename']) && is_string($decoded['filename']) && $decoded['filename'] !== '') {
					$filename = $decoded['filename'];
				}
			} catch (\JsonException) {
				// Ignorar y usar fallback
			}
		}

		if ($filename === '' && $fallbackName !== '') {
			$filename = $fallbackName;
		}

		if ($filename === '') {
			return '';
		}

		$full = $scansPath . DIRECTORY_SEPARATOR . $filename;
		return is_file($full) ? $full : '';
	}

   /**
     * Borra archivos marcados como sospechosos y actualiza la pila/estado
     *
     *
     * @return void
     */
    public function deleteFiles(): void
    {
        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app   = Factory::getApplication();
        $input = $app->getInput();

        // Rutas recibidas del formulario
        /** @var array<int, string> $rawPaths */
        $rawPaths = (array) $input->get('malwarescan_status_table', [], 'array');

        // Cargamos el escaneo y validamos pronto
        $this->loadStack('malwarescan', 'malwarescan');

        if ($rawPaths === [] || count($rawPaths) === 0) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_FILES_SELECTED'), 'error');
            return;
        }

        // Normaliza/limpia rutas & evita duplicados
        $normalizedPaths = [];
        foreach ($rawPaths as $p) {
            if (!is_string($p) || $p === '') {
                continue;
            }
            // Normaliza saltos/espacios raros
            $p = trim(str_replace(["\r", "\n", "\0"], '', $p));

            // Limpia y verifica que pertenece a JPATH_ROOT (evita traversal)
            try {
                $clean = Path::clean($p);
                // Lanza excepción si está fuera de JPATH_ROOT
                Path::check($clean, JPATH_ROOT);
            } catch (\Throwable) {
                // Ruta inválida o fuera de base => ignoramos y seguimos
                $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $p), 'error');
                continue;
            }

            $normalizedPaths[$clean] = true; // usamos como set para deduplicar
        }

        if ($normalizedPaths === []) {
            $app->enqueueMessage(Text::_('COM_SECURITYCHECKPRO_NO_FILES_SELECTED'), 'error');
            return;
        }

        $deletedCount = 0;
        $errors       = [];

        // Índice rápido path => keys en la pila para borrado eficiente
        // (Evita recorrer toda la pila N veces)
        /** @var array<int, array{path:string}> $stack */
        $stack = is_array($this->Stack_malwarescan ?? null) ? $this->Stack_malwarescan : [];
        $indexByPath = [];
        foreach ($stack as $k => $row) {
            $pathInRow = isset($row['path']) && is_string($row['path']) ? $row['path'] : '';
            if ($pathInRow !== '') {
                $indexByPath[$pathInRow][] = $k;
            }
        }		
		
        // Borrado físico y depuración de la pila en memoria
        foreach (array_keys($normalizedPaths) as $absPath) {
            $deletedThis = false;
			
            try {
                // Sólo intentamos borrar si existe y es un archivo
                if (is_file($absPath)) {
                    $deletedThis = File::delete($absPath);					
                } else {
                    // Si no existe, consideramos como "borrado lógico": quitamos de la lista
                    $deletedThis = true;
                }
            } catch (\Throwable) {
                $deletedThis = false;
            }

            if ($deletedThis) {
                $deletedCount++;

                // Elimina todas las entradas que apunten a esta ruta
                if (isset($indexByPath[$absPath])) {
                    foreach ($indexByPath[$absPath] as $k) {
                        unset($stack[$k]);
                    }
                    // Reindexa sólo si hemos modificado
                    $stack = array_values($stack);
                }
            } else {
                $errors[] = $absPath;
            }
        }

        // Persistimos la pila actualizada si ha cambiado
        $this->Stack_malwarescan = $stack;

        // Actualiza resumen (carga valores actuales, ajusta y guarda)
        $this->loadStack('malwarescan_resume', 'files_scanned_malwarescan');
        $this->loadStack('malwarescan_resume', 'suspicious_files');
        $this->loadStack('malwarescan_resume', 'last_check_malwarescan');

        // Asegura integridad de tipos
        $suspicious = isset($this->suspicious_files) && is_numeric($this->suspicious_files)
            ? (int) $this->suspicious_files
            : 0;

        $suspicious = max(0, $suspicious - $deletedCount);
        $this->suspicious_files = $suspicious;

        // Guarda cambios de forma atómica en tu mecanismo (asumido por saveStack)
        $this->saveStack('malwarescan_modified');

        // Mensajes al usuario
        if ($deletedCount > 0) {
            $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_ELEMENTS_DELETED_FROM_LIST', $deletedCount), 'message');
        }

        foreach ($errors as $failedPath) {
            $app->enqueueMessage(Text::sprintf('COM_SECURITYCHECKPRO_DELETE_FILE_ERROR', $failedPath), 'error');
        }
    }

    /**
	 * Escribe una línea en el log de la tarea activa.
	 *
	 * @param  string $message  Texto a escribir en el log.
	 * @param  string $level    Nivel o severidad del log (INFO, WARN, ERROR...).
	 * @return void
	 */
	public function write_log(string $message, string $level = 'INFO'): void
	{
		// Si no hay fichero abierto, no se escribe
		if (!isset($this->fp) || $this->fp === null) {
			return;
		}

		try {
			// Validamos puntero válido (resource o SplFileObject)
			if (!is_resource($this->fp) && !$this->fp instanceof \SplFileObject) {
				return;
			}

			// Normalizamos el mensaje para evitar inyección de saltos de línea
			$safeMessage = str_replace(["\r", "\n"], [' ', ' '], $message);

			// Timestamp legible
			$timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

			// Componemos línea
			$line = sprintf(
				"%-8s | %s | %s%s",
				strtoupper($level),
				$timestamp,
				$safeMessage,
				PHP_EOL
			);

			if ($this->fp instanceof \SplFileObject) {
				$this->fp->fwrite($line);
			} elseif (is_resource($this->fp)) {
				$bytes = fwrite($this->fp, $line);
				if ($bytes === false) {
					throw new \RuntimeException('fwrite() failed');
				}
			}
		} catch (\Throwable $e) {
			// Notificamos el error sin interrumpir ejecución del flujo principal
			Factory::getApplication()->enqueueMessage(
				Text::sprintf(
					'COM_SECURITYCHECKPRO_LOG_WRITE_ERROR',
					$e->getMessage()
				),
				'warning'
			);
		}
	}

	/**
	 * Prepara el archivo de log de la tarea pasada como argumento.
	 *
	 * Nota de retorno:
	 *  - Devuelve el nombre del fichero SOLO para "controlcenter".
	 *  - Para el resto de opciones devuelve null.
	 *
	 * @param  string $opcion  One of: "permissions", "integrity", "malwarescan", "controlcenter"
	 * @return string|null     El nombre del log para "controlcenter", o null en otros casos
	 */
	public function prepareLog(string $opcion): ?string
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Mapa de opciones válidas -> storage_key
		$map = [
			'permissions'   => 'filepermissions_log',
			'integrity'     => 'fileintegrity_log',
			'malwarescan'   => 'filemalware_log',
			'controlcenter' => 'controlcenter_log',
			'malwarescan_modified'   => 'filemalware_log',
		];

		if (!isset($map[$opcion])) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_SECURITYCHECKPRO_INVALID_OPTION', $opcion),
				'warning'
			);
			return null;
		}

		$storageKey  = $map[$opcion];
		$filenameLog = $this->generateKey();

		// Prepara payload JSON para guardar en la tabla
		$payload = ['filename' => $filenameLog];
		$json    = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		if (!is_string($json)) {
			Factory::getApplication()->enqueueMessage(
				Text::_('COM_SECURITYCHECKPRO_JSON_ENCODE_ERROR'),
				'error'
			);
			return null;
		}

		try {
			// Borrar registro previo e insertar uno nuevo dentro de una transacción
			$db->transactionStart();

			$query = $db->getQuery(true)
				->delete($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote($storageKey));
			$db->setQuery($query)->execute();

			$row = (object) [
				'storage_key'   => $storageKey,
				'storage_value' => $json,
			];
			$db->insertObject('#__securitycheckpro_storage', $row);

			$db->transactionCommit();
		} catch (\Throwable $e) {
			$db->transactionRollback();
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_SECURITYCHECKPRO_DB_ERROR', $e->getMessage()),
				'error'
			);
			return null;
		}

		// Si es para Control Center, devolvemos el nombre del archivo y salimos
		if ($storageKey === 'controlcenter_log') {
			return $filenameLog;
		}

		// Aseguramos la carpeta de logs
		$dir = rtrim((string) $this->folder_path, DIRECTORY_SEPARATOR);
		if ($dir === '' || (!is_dir($dir) && !Folder::create($dir))) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_SECURITYCHECKPRO_DIR_CREATE_ERROR', $dir),
				'error'
			);
			return null;
		}
		if (!is_writable($dir)) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_SECURITYCHECKPRO_DIR_NOT_WRITABLE', $dir),
				'error'
			);
			return null;
		}

		// Si hay un log abierto, ciérralo limpiamente
		if (isset($this->fp) && (is_resource($this->fp) || $this->fp instanceof \SplFileObject)) {
			// Conserva tu método existente si ya lo tienes
			if (method_exists($this, 'closeLogSCP')) {
				$this->closeLogSCP();
			}
		}

		$path = $dir . DIRECTORY_SEPARATOR . $filenameLog;

		// Crea el archivo vacío si no existe
		if (!file_exists($path)) {
			$written = File::write($path, '');
			if ($written === false) {
				Factory::getApplication()->enqueueMessage(
					Text::sprintf('COM_SECURITYCHECKPRO_FILE_CREATE_ERROR', $path),
					'error'
				);
				return null;
			}
		}

		// Abre en modo append binario
		$fp = @fopen($path, 'ab');
		if ($fp === false) {
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_SECURITYCHECKPRO_FILE_OPEN_ERROR', $path),
				'error'
			);
			$this->fp = null;
			return null;
		}

		$this->fp = $fp;
		return null;
	}

	/**
	 * Cierra el log actualmente activo.
	 *
	 *
	 * @return void
	 */
	public function closeLogSCP(): void
	{
		if (!isset($this->fp) || $this->fp === null) {
			return;
		}

		try {
			if ($this->fp instanceof \SplFileObject) {
				// SplFileObject no necesita fclose, pero cerramos el handler explícitamente
				$this->fp = null;
				return;
			}

			if (is_resource($this->fp)) {
				fclose($this->fp);
			}
		} catch (\Throwable $e) {
			// Registramos o notificamos el error sin interrumpir ejecución
			Factory::getApplication()->enqueueMessage(
				Text::sprintf(
					'COM_SECURITYCHECKPRO_LOG_CLOSE_ERROR',
					$e->getMessage()
				),
				'warning'
			);
		} finally {
			// Garantiza la limpieza del puntero en cualquier caso
			$this->fp = null;
		}
	}

    /**
	 * Extrae la información sobre las extensiones instaladas o actualizadas.
	 *
	 * @return array<string, mixed>|string|false Devuelve el array decodificado, el string original si no es JSON válido, o false si hay error de BBDD.
	 */
	public function getInstalls(): array|string|false
	{
		/** @var \Joomla\Database\DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		try {
			$query = $db->getQuery(true)
				->select($db->quoteName('storage_value'))
				->from($db->quoteName('#__securitycheckpro_storage'))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote('installs'));

			$db->setQuery($query);

			/** @var string|null $result */
			$result = $db->loadResult();

			if ($result === null || $result === '') {
				return [];
			}

			/** @var array<string, mixed>|null $decoded */
			$decoded = json_decode($result, true);

			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				return $decoded;
			}

			// Si no es JSON válido, devolvemos el valor original como string
			return $result;

		} catch (\Throwable $e) {
			// Se captura cualquier error, incluidos de BBDD
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('COM_SECURITYCHECKPRO_DB_ERROR', $e->getMessage()),
				'error'
			);

			return false;
		}
	}

	/**
	 * Borra de forma segura los archivos y directorios dentro de la carpeta temporal.
	 * Permite únicamente index.html en la raíz de tmp.
	 *
	 * @return void
	 */
	public function accionesCleanTmpDir(): void
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();
		$app->setUserState('clean_tmp_dir_state', 'start');
		$app->setUserState('clean_tmp_dir_result', '');

		@set_time_limit(0);

		// 1) Localiza y valida la ruta tmp
		$cfgTmp  = (string) $app->getConfig()->get('tmp_path', JPATH_ROOT . DIRECTORY_SEPARATOR . 'tmp');
		$tmpPath = Path::clean(rtrim($cfgTmp, DIRECTORY_SEPARATOR));

		if ($tmpPath === '' || !is_dir($tmpPath)) {
			$app->setUserState('clean_tmp_dir_state', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED'));
			return;
		}

		try {
			Path::check($tmpPath, $tmpPath);
		} catch (\Throwable) {
			$app->setUserState('clean_tmp_dir_state', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED'));
			return;
		}

		$failed = [];

		// 2) Recorre y borra (de abajo arriba)
		try {
			$directoryIterator = new \RecursiveDirectoryIterator(
				$tmpPath,
				\FilesystemIterator::SKIP_DOTS
			);

			$iterator = new \RecursiveIteratorIterator(
				$directoryIterator,
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($iterator as $info) {
				/** @var \SplFileInfo $info */
				$path = Path::clean($info->getPathname());

				// Seguridad: solo dentro de tmp
				try {
					Path::check($path, $tmpPath);
				} catch (\Throwable) {
					continue;
				}

				// No seguir ni borrar symlinks
				if ($info->isLink()) {
					continue;
				}

				// --- Archivos ---
				if ($info->isFile()) {
					$basename = $info->getBasename();

					// Permitir SOLO index.html en la raíz de tmp
					if (
						$basename === 'index.html'
						&& Path::clean($info->getPath()) === $tmpPath
					) {
						continue;
					}

					try {
						if (!File::delete($path)) {
							$failed[] = $path;
						}
					} catch (\Throwable) {
						$failed[] = $path;
					}

					continue;
				}

				// --- Directorios ---
				if ($info->isDir()) {
					// Nunca borrar la raíz tmp
					if ($path === $tmpPath) {
						continue;
					}

					try {
						if (!Folder::delete($path)) {
							$failed[] = $path;
						}
					} catch (\Throwable) {
						$failed[] = $path;
					}
				}
			}
		} catch (\Throwable) {
			$app->setUserState('clean_tmp_dir_state', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED'));
			return;
		}

		// 3) Verificación final
		$leftFiles = (array) Folder::files($tmpPath, '.', true, true);
		$leftDirs  = (array) Folder::folders($tmpPath, '.', true, true);

		// Ignora index.html SOLO si está en la raíz
		$leftFiles = array_values(array_filter(
			$leftFiles,
			static function (string $path) use ($tmpPath): bool {
				$clean = Path::clean($path);

				try {
					Path::check($clean, $tmpPath);
				} catch (\Throwable) {
					return false;
				}

				if (is_link($clean)) {
					return false;
				}

				return !(
					basename($clean) === 'index.html'
					&& Path::clean(dirname($clean)) === $tmpPath
				);
			}
		));

		if ($failed === [] && $leftFiles === [] && $leftDirs === []) {
			$app->setUserState('clean_tmp_dir_result', '');
		} else {
			$pending = array_unique(array_merge($failed, $leftFiles, $leftDirs));
			$pending = array_values(array_filter(array_map('strval', $pending), static fn (string $v): bool => $v !== ''));
			$app->setUserState('clean_tmp_dir_result', implode(PHP_EOL, $pending));
		}

		$app->setUserState('clean_tmp_dir_state', Text::_('COM_SECURITYCHECKPRO_FILEMANAGER_ENDED'));
	}

}