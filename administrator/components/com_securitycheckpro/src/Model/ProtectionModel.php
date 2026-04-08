<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Log\Log;
use Joomla\Input\Input;
use InvalidArgumentException;
use RuntimeException;
use Joomla\Filesystem\Path;

class ProtectionModel extends BaseDatabaseModel
{

    /**
     Configuración por defecto
     *
     @var array<string, int|list<string>|string>
     */
    protected $defaultConfig = [
		'disable_server_signature'    => 0,
		'prevent_access'    => 0,
		'prevent_unauthorized_browsing'    => 0,
		'file_injection_protection'    => 0,
		'self_environ'    => 0,
		'xframe_options'    =>    0,
		'prevent_mime_attacks'    =>    0,
		'default_banned_list'    => 0,
		'own_banned_list'    => '',
		'disallow_php_eggs'    => 0,
		'disallow_sensible_files_access' => '',
		'hide_backend_url' => '',
		'own_code'    =>    '',
		'backend_exceptions'    =>    '',
		'optimal_expiration_time'    =>    0,
		'redirect_to_www'    =>    0,
		'redirect_to_non_www'    =>    0,
		'compress_content'    =>    0,
		'backend_protection_applied'    =>    0,
		'hide_backend_url_redirection'    =>    '',
		'sts_options'    =>    0,
		'xss_options'    =>    0,
		'csp_policy'    =>    '',
		'referrer_policy'    =>    '',
		'permissions_policy'    =>    ''
    ];

	 /**
     Configuración por defecto
     *
     @var array<string, int|list<string>|string>
     */
    protected $ConfigApplied = [
		'disable_server_signature'    => 0,
		'prevent_access'    => 0,
		'prevent_unauthorized_browsing'    => 0,
		'file_injection_protection'    => 0,
		'self_environ'    => 0,
		'xframe_options'    =>    0,
		'prevent_mime_attacks'    =>    0,
		'default_banned_list'    => 0,
		'own_banned_list'    => 0,
		'disallow_php_eggs'    => 0,
		'disallow_sensible_files_access' => 0,
		'hide_backend_url' => 0,
		'own_code'    =>    '',
		'backend_exceptions'    =>    '',
		'optimal_expiration_time'    =>    0,
		'redirect_to_www'    =>    0,
		'redirect_to_non_www'    =>    0,
		'compress_content'    =>    0,
		'backend_protection_applied'    =>    0,
		'hide_backend_url_redirection'    =>    0,
		'sts_options'    =>    0,
		'xss_options'    =>    0,
		'csp_policy'    =>    '',
		'referrer_policy'    =>    '',
		'permissions_policy'    =>    ''
    ];

   /**
     Configuración aplicada
     *
     @var \Joomla\Registry\Registry
     */
    protected ?Registry $config = null;

    /**
     * Obtiene el valor de una opción de configuración
     *
     *
	 * @param   string        		   $key    The key of the element
	 * @param   string|int             $default    The default value
	 * @param   string          	   $key_name    The name of the key to load
	 * 	 
     * @return  string
     *     
     */
    public function getValue($key, $default = '', $key_name = 'cparams')
    {
		$this->load($key_name);			
               	   
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
		
		if ( (!empty($value)) && (!is_numeric($value)) ) {
			$value = trim($value);
		}
		
        $x = $this->config->set($key, $value);		
        
        if($save) { $this->save($key_name);
        }
        return $x;
    }

    /**
     * Obtiene el valor asociado a una clave desde la tabla de almacenamiento.
     *
     * @return string|null
     */
    public function load(string $keyName): ?string
    {
		$this->config = new Registry();
		
        // 1) Validación: evita claves malformadas/excesivas
        if (!preg_match('/^[A-Za-z0-9._-]{1,128}$/', $keyName)) {
            return null;
        }

        /** @var DatabaseInterface $db */
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // 2) Construcción segura con protección nativa (sin bind)
        $query
            ->select($db->quoteName('storage_value'))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where(
                $db->quoteName('storage_key') . ' = ' . $db->quote($keyName)
            )
            ->setLimit(1);

        try {
            $db->setQuery($query);
            $json = (string) $db->loadResult();

            if ($json === '') {
                return null;
            }

            // 3) Decodificación segura
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return null;
            }

            // Mantén compatibilidad con $this->config si lo usas en otros sitios
            $this->config->loadArray($data);

            if (!array_key_exists($keyName, $data)) {
                return null;
            }

            $value = $data[$keyName];

            // 4) Normaliza salida
            if (is_scalar($value) || $value === null) {
                return $value === null ? null : (string) $value;
            }

            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        } catch (\JsonException|\RuntimeException $e) {
            Log::add('ProtectionModel. load function error: ' . $e->getMessage(), Log::ERROR, 'com_securitycheckpro');
            return null;
        }
    }

    /**
	 * Guarda la configuración en la tabla de almacenamiento.
	 *
	 * @param  string $key_name  Clave bajo la que se persiste la configuración
	 * @return void
	 *
	 * @throws InvalidArgumentException Si la clave no cumple el patrón permitido
	 * @throws RuntimeException         Si falla el guardado o la codificación JSON
	 */
	public function save(string $key_name): void
	{
		// Asegura que la config esté cargada
		if ($this->config === null) {
			$this->load($key_name);
		}

		// 1) Validación estricta de la clave:
		//    - Letras, números, punto, guion, guion bajo, dos puntos
		//    - Longitud máxima 128
		if (!preg_match('/^[A-Za-z0-9._:\-]{1,128}$/', $key_name)) {
			throw new \InvalidArgumentException('Invalid storage key format.');
		}

		/** @var DatabaseInterface $db */
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// 2) Normaliza y serializa la configuración a JSON de forma segura
		$arrayData = $this->config instanceof Registry ? $this->config->toArray() : (array) $this->config;

		try {
			$json = json_encode(
				$arrayData,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
			);
		} catch (\Throwable $e) {
			throw new \RuntimeException('Failed to encode configuration to JSON.', 500, $e);
		}

		// 3) Transacción + upsert manual: UPDATE ? si no afecta, INSERT
		try {
			$db->transactionStart();

			// UPDATE
			$update = $db->getQuery(true)
				->update($db->quoteName('#__securitycheckpro_storage'))
				->set($db->quoteName('storage_value') . ' = ' . $db->quote($json))
				->where($db->quoteName('storage_key') . ' = ' . $db->quote($key_name));

			$db->setQuery($update);
			$db->execute();

			if ($db->getAffectedRows() === 0) {
				// INSERT (tabla con prefijo resuelto)
				$row = (object) [
					'storage_key'   => $key_name,
					'storage_value' => $json,
				];

				// Nota: insertObject ya aplica el quoting apropiado
				$db->insertObject($db->replacePrefix('#__securitycheckpro_storage'), $row);
			}

			$db->transactionCommit();
		} catch (\Throwable $e) {
			$db->transactionRollback();
			throw new \RuntimeException('Failed to save configuration.', 500, $e);
		}
	}

   /**
     * Obtiene la configuración de los parámetros del Firewall Web
     *
     * 	 
     * @return  array<string, string>|null
     *     
     */
    function getConfig()
    {
        $config = [];
        foreach($this->defaultConfig as $k => $v) {
            $config[$k] = $this->getValue($k, $v);
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
            $this->setValue($key, $value, '', $key_name);
        }

        $this->save($key_name);
    }

   	/**
	 * Comprueba si un fichero existe en la raíz del sitio de forma segura.
	 *
	 * @param  string $filename  Ruta relativa (respecto a JPATH_SITE) del fichero a comprobar
	 * @return bool              TRUE si existe, FALSE en caso contrario
	 *
	 * @throws InvalidArgumentException Si la ruta es inválida o apunta fuera de JPATH_SITE
	 */
	public function existsFile(string $filename): bool
	{
		// Bloquea rutas absolutas para evitar escapes directos
		if (str_starts_with($filename, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $filename)) {
			throw new \InvalidArgumentException('Absolute paths are not allowed.');
		}

		// Construimos la ruta completa y normalizamos
		$fullPath = JPATH_SITE . DIRECTORY_SEPARATOR . ltrim($filename, DIRECTORY_SEPARATOR);
		$realBase = realpath(JPATH_SITE);
		$realPath = realpath($fullPath);

		// Si el realpath falla, el fichero no existe
		if ($realPath === false) {
			return false;
		}

		// Validamos que la ruta está dentro de JPATH_SITE
		if (strpos($realPath, $realBase) !== 0) {
			throw new \InvalidArgumentException('Access outside site root is not permitted.');
		}

		return is_file($realPath);
	}

    /**
	 * Crea una copia de seguridad del archivo .htaccess si existe.
	 *
	 * @param  string $name Nombre del archivo de backup (relativo a JPATH_SITE)
	 * @return bool         TRUE si la copia se realizó, FALSE si no existía el .htaccess
	 *
	 * @throws InvalidArgumentException Si el nombre de backup es inválido
	 * @throws RuntimeException         Si la copia falla
	 */
	public function makeBackup(string $name): bool
	{
		$source = JPATH_SITE . DIRECTORY_SEPARATOR . '.htaccess';

		// 1) Comprobar que .htaccess existe y es fichero
		if (!is_file($source)) {
			return false;
		}

		// 2) Validación estricta del nombre de backup
		//    Solo letras, números, guion, guion bajo y punto. Máx. 64 caracteres.
		if (!preg_match('/^[A-Za-z0-9._-]{1,64}$/', $name)) {
			throw new \InvalidArgumentException('Invalid backup file name.');
		}

		// 3) Construir ruta destino dentro de JPATH_SITE
		$destination = JPATH_SITE . DIRECTORY_SEPARATOR . $name;
		$realBase    = realpath(JPATH_SITE);
		$realDest    = realpath(dirname($destination));

		// Evitar path traversal y rutas fuera de JPATH_SITE
		if ($realDest === false || strpos($realDest, $realBase) !== 0) {
			throw new \InvalidArgumentException('Destination outside site root is not permitted.');
		}

		// 4) Intentar copia
		if (!File::copy($source, $destination)) {
			throw new \RuntimeException('Failed to create backup file.');
		}

		return true;
	}
	
	/**
	 * Modifica los valores del array 'ConfigApplied' según las opciones detectadas en el .htaccess actual.
	 *
	 * @return array<string, int|list<string>|string>
	 */
	public function getConfigApplied(): array
	{
		$applied      = $this->ConfigApplied;
		$actualConfig = $this->getConfig();

		$htaccessPath = JPATH_SITE . DIRECTORY_SEPARATOR . '.htaccess';
		if (!is_file($htaccessPath)) {
			$applied['backend_protection_applied'] = !empty($actualConfig['backend_protection_applied']) ? 1 : 0;
			return $applied;
		}

		$rules = (string) @file_get_contents($htaccessPath);
		if ($rules === '') {
			$applied['backend_protection_applied'] = !empty($actualConfig['backend_protection_applied']) ? 1 : 0;
			return $applied;
		}
		// Normaliza EOL a \n
		$rules = $this->normalizeNewlines($rules);

		// Helpers locales seguros
		$hasBlock = static function (string $haystack, string $label): bool {
			$labelQuoted = preg_quote($label, '~');
			$re = '~^\h*##\s*Begin\s+Securitycheck\s+Pro\s+' . $labelQuoted . '\h*$'
				. '.*?'
				. '^\h*##\s*End\s+Securitycheck\s+Pro\s+' . $labelQuoted . '\h*$~ims';
			return (bool) preg_match($re, $haystack);
		};
		$hasRule = static function (string $haystack, string $regex): bool {
			// $regex SIN delimitadores; se aplica en ~…~im
			return (bool) preg_match('~' . $regex . '~im', $haystack);
		};
		$containsInsensitive = static function (string $haystack, string $needle): bool {
			return stripos($haystack, $needle) !== false;
		};

		/**
		 * Mapa unificado de detectores:
		 * - block: etiqueta del bloque Begin/End (si existe para esa opción)
		 * - rules: regex equivalentes que consideramos “aplicado” aunque estén fuera del bloque
		 */
		$detectors = [
			'prevent_access' => [
				'block' => 'Prevent access to .ht files',
				'rules' => [
					// <FilesMatch "^\.ht"> ... Deny from all (con u opcional "Order deny,allow")
					'<\s*FilesMatch\s+["\']?\^?\\\\?\.ht["\']?\s*>\s*(?:.*?\bOrder\b[^\n]*?)?.*?\bDeny\s+from\s+all\b.*?<\/\s*FilesMatch\s*>',
				],
			],
			'prevent_unauthorized_browsing' => [
				// No tiene etiqueta única siempre; detectamos bloque si lo tuvieras:
				'block' => 'Prevent Unauthorized Browsing',
				'rules' => [
					// Tolerar "Options -Indexes" y "Options All -Indexes", etc.
					'^\s*Options\b[^\n]*-Indexes',
				],
			],
			'default_banned_list' => [
				'block' => 'Default Blacklist',
				'rules' => [
					// Cualquier marca de tu snippet por defecto
					'##\s*Begin\s+Securitycheck\s+Pro\s+Default\s+Blacklist',
				],
			],
			'file_injection_protection' => [
				'block' => 'File Injection Protection',
				'rules' => [
					'^\s*RewriteCond\s+\%\{REQUEST_METHOD\}\s+GET\b',
				],
			],
			'self_environ' => [
				'block' => 'self/environ protection',
				'rules' => [
					'^\s*RewriteCond\s+\%\{QUERY_STRING\}\s+.*?proc/self/environ\b.*?(?:\[[^\]]*\])?\s*$',
				],
			],
			'xframe_options' => [
				'block' => 'Xframe-options protection',
				'rules' => [
					'^\s*Header\s+always\s+set\s+X-Frame-Options\b',
				],
			],
			'prevent_mime_attacks' => [
				'block' => 'Prevent mime based attacks',
				'rules' => [
					'^\s*Header\s+always\s+set\s+X-Content-Type-Options\s+"?nosniff"?\b',
				],
			],
			'sts_options' => [
				'block' => 'Strict Transport Security',
				'rules' => [
					'^\s*Header\s+always\s+set\s+Strict-Transport-Security\b',
				],
			],
			'xss_options' => [
				'block' => 'X-Xss-Protection',
				'rules' => [
					'^\s*Header\s+always\s+set\s+X-?XSS-?Protection\b',
				],
			],
			'csp_policy' => [
				'block' => 'Content-Security-Policy protection',
				'rules' => [
					'^\s*Header\s+always\s+set\s+Content-Security-Policy\b',
				],
			],
			'referrer_policy' => [
				'block' => 'Referrer policy protection',
				'rules' => [
					'^\s*Header\s+always\s+set\s+Referrer-Policy\b',
				],
			],
			'permissions_policy' => [
				'block' => 'Permissions policy (old Feature-Policy) protection',
				'rules' => [
					'^\s*Header\s+always\s+set\s+Permissions-Policy\b',
				],
			],
			'disable_server_signature' => [
				'block' => 'Disable Server Signature',
				'rules' => [
					'^\s*ServerSignature\s+Off\b',
				],
			],
			'disallow_php_eggs' => [
				'block' => 'Disallow Php Easter Eggs',
				'rules' => [
					'^\s*RewriteCond\s+\%\{QUERY_STRING\}\s+=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\s*\[?NC\]?',
				],
			],
			'optimal_expiration_time' => [
				'block' => 'Optimal Expiration time',
				'rules' => [
					'^\s*<IfModule\s+mod_expires\.c\s*>',
					'^\s*ExpiresActive\s+On\b',
				],
			],
			'compress_content' => [
				'block' => 'compress content',
				'rules' => [
					'^\s*<IfModule\s+mod_deflate\.c\s*>',
					'^\s*AddOutputFilterByType\s+DEFLATE\b',
				],
			],
			'redirect_to_www' => [
				// No siempre hay etiqueta única, pero muchas veces añades “Securitycheck Pro Redirect …”
				'block' => 'Redirect non-www to www',
				'rules' => [
					'^\s*RewriteCond\s+\%\{HTTP_HOST\}\s+!\^www\\\.\s*\[?NC\]?',
					'^\s*RewriteRule\s+\^\(\.\*\)\$\s+%?\{REQUEST_SCHEME\}?:?//www\.\%?\{HTTP_HOST\}/\$1\s+\[?R=301,?L\]?',
				],
			],
			'redirect_to_non_www' => [
				'block' => 'Redirect www to non-www',
				'rules' => [
					'^\s*RewriteCond\s+\%\{HTTP_HOST\}\s+\^www\\\.\(.*\)\$\s*\[?NC\]?',
					'^\s*RewriteRule\s+\^\(\.\*\)\$\s+%?\{REQUEST_SCHEME\}?:?//\%?1/\$1\s+\[?R=301,?L\]?',
				],
			],
		];

		// 1) Detección por bloque/reglas para TODAS las opciones del mapa
		foreach ($detectors as $key => $cfg) {
			$isApplied = false;

			if ($hasBlock($rules, (string) $cfg['block'])) {
				$isApplied = true;
			} else {
				foreach ($cfg['rules'] as $re) {
					if ($hasRule($rules, (string) $re)) {
						$isApplied = true;
						break;
					}
				}
			}

			if ($isApplied) {
				$applied[$key] = 1;
			}
		}

		// 2) Detectores dinámicos desde configuración del usuario (escapados)

		// own_banned_list: "RewriteCond %{HTTP_USER_AGENT} <AGENT>"
		$ownBannedList = $this->toNonEmptyLines((string) $this->getValue('own_banned_list'));
		if (!empty($ownBannedList)) {
			$allPresent = true;
			foreach ($ownBannedList as $agent) {
				$pat = '~RewriteCond\s+\%\{HTTP_USER_AGENT\}\s+' . preg_quote($agent, '~') . '~i';
				if (!preg_match($pat, $rules)) {
					$allPresent = false;
					break;
				}
			}
			if ($allPresent) {
				$applied['own_banned_list'] = 1;
			}
		}

		// own_code: cada línea/bloque debe estar incluido literalmente (case-insensitive)
		$ownCode = $this->toNonEmptyLines((string) $this->getValue('own_code'));
		if (!empty($ownCode)) {
			$allPresent = true;
			foreach ($ownCode as $codeLine) {
				$pat = '~' . preg_quote($codeLine, '~') . '~i';
				if (!preg_match($pat, $rules)) {
					$allPresent = false;
					break;
				}
			}
			if ($allPresent) {
				$applied['own_code'] = 1;
			}
		}

		// disallow_sensible_files_access: cada item presente
		$disallowFiles = $this->toNonEmptyLines((string) $this->getValue('disallow_sensible_files_access'));
		if (!empty($disallowFiles)) {
			$allPresent = true;
			foreach ($disallowFiles as $fileLine) {
				$pat = '~' . preg_quote($fileLine, '~') . '~i';
				if (!preg_match($pat, $rules)) {
					$allPresent = false;
					break;
				}
			}
			if ($allPresent) {
				$applied['disallow_sensible_files_access'] = 1;
			}
		}

		// hide_backend_url
		$hideBackend = (string) $this->getValue('hide_backend_url');
		if ($hideBackend !== '') {
			$pat = '~RewriteCond\s+\%\{QUERY_STRING\}\s+!\s*' . preg_quote($hideBackend, '~') . '(?:\s|\]|$)~i';
			if (preg_match($pat, $rules)) {
				$applied['hide_backend_url'] = 1;
			}
		}

		// hide_backend_url_redirection
		$hideBackendRedirect = (string) $this->getValue('hide_backend_url_redirection');
		if ($hideBackendRedirect !== '') {
			$redir = ltrim($hideBackendRedirect, '/');
			$pat   = '~RewriteRule\s+\^\.\*administrator/\?\s+/' . preg_quote($redir, '~') . '(?:\s|\[|$)~i';
			if (preg_match($pat, $rules)) {
				$applied['hide_backend_url_redirection'] = 1;
			}
		}

		// backend_exceptions: todas presentes
		$backendExceptions = $this->toNonEmptyLines((string) $this->getValue('backend_exceptions'));
		if (!empty($backendExceptions)) {
			$allPresent = true;
			foreach ($backendExceptions as $ex) {
				$pat = '~\%\{QUERY_STRING\}\s+!\s*' . preg_quote($ex, '~') . '(?:\s|\]|$)~i';
				if (!preg_match($pat, $rules)) {
					$allPresent = false;
					break;
				}
			}
			if ($allPresent) {
				$applied['backend_exceptions'] = 1;
			}
		}

		// backend_protection_applied se toma de la config real
		$applied['backend_protection_applied'] = !empty($actualConfig['backend_protection_applied']) ? 1 : 0;

		return $applied;
	}


	/* =========================
	 * Helpers privados
	 * ========================= */

	/**
	 * Normaliza saltos de línea a "\n".
	 */
	private function normalizeNewlines(string $text): string
	{
		return str_replace(["\r\n", "\r"], "\n", $text);
	}	

	/**
	 * Convierte texto multilínea en array de líneas no vacías, recortadas.
	 *
	 * @return list<string>
	 */
	private function toNonEmptyLines(string $text): array
	{
		$text = $this->normalizeNewlines($text);
		$lines = array_map('trim', explode("\n", $text));
		return array_values(array_filter($lines, static fn (string $v): bool => $v !== ''));
	}   

    /**
	 * Modifica o crea el archivo .htaccess según las opciones escogidas por el usuario,
	 * de forma idempotente y segura.
	 *
	 * @return bool True si el archivo queda actualizado o no requiere cambios; false si falla.
	 */
	public function protect(): bool
	{
		// Helpers internos (privados si lo prefieres en la clase)
		$nl = "\n";

		$sanitizeHeaderValue = static function (string $v, int $maxLen = 4096): string {
			// Elimina CR/LF/NUL para evitar inyección de líneas/directivas
			$v = str_replace(["\r", "\n", "\0"], '', $v);
			// Colapsa espacios repetidos
			$v = preg_replace('/[ \t]+/u', ' ', $v) ?? '';
			$v = trim($v, " \t\"");
			// Límite de longitud defensivo
			if (strlen($v) > $maxLen) {
				$v = substr($v, 0, $maxLen);
			}
			return $v;
		};

		$sanitizeHtLine = static function (string $line, int $maxLen = 4096): string {
			// Evita que un usuario cierre/inyecte nuestros marcadores o bloques
			$forbidden = [
				'## Begin Securitycheck Pro',
				'## End Securitycheck Pro',
			];
			foreach ($forbidden as $f) {
				if (stripos($line, $f) !== false) {
					// Neutraliza el marcador
					$line = str_ireplace($f, str_replace('#', '#', $f), $line);
				}
			}

			// Quita CR/LF/NUL y normaliza espacios
			$line = str_replace(["\r", "\n", "\0"], '', $line);
			$line = preg_replace('/[ \t]+/u', ' ', $line) ?? '';
			$line = trim($line);
			if (strlen($line) > $maxLen) {
				$line = substr($line, 0, $maxLen);
			}
			return $line;
		};

		// Convierte lista (textarea) en array limpio, evitando líneas vacías
		$toCleanArray = static function (?string $raw) use ($sanitizeHtLine): array {
			$items = preg_split('/\R/u', (string) $raw) ?: [];
			$items = array_map(static fn($x) => $sanitizeHtLine((string) $x), $items);
			return array_values(array_filter($items, static fn($x) => $x !== ''));
		};

		// Literaliza patrón (no regex) dentro de RewriteCond para que sea seguro
		$quoteForApacheRegex = static function (string $literal): string {
			// \Q...\E es soportado por PCRE utilizado por Apache para RewriteCond
			return '\Q' . $literal . '\E';
		};

		// 1) Cargar estado actual / plantilla base
		$siteUrl      = rtrim(str_replace(['http://', 'https://'], '', Uri::base()), '/');
		$htaccessPath = JPATH_SITE . DIRECTORY_SEPARATOR . '.htaccess';
		$hasHtaccess  = $this->ExistsFile('.htaccess');

		// Backup (best-effort) antes de tocar nada
		if ($hasHtaccess) {
			// Si ya existe el "original", copiamos a ".backup"; si no, creamos ".original"
			$backupTarget = $this->ExistsFile('.htaccess.original') ? '.htaccess.backup' : '.htaccess.original';
			if (!$this->makeBackup($backupTarget)) {
				// No abortamos por backup fallido, pero lo notificamos en logs si tienes logger
				// $this->logger->warning('Backup de .htaccess falló');
			}
		}

		// Cargar base (prioriza htaccess.txt de Joomla; si no, tu plantilla de includes)
		$baseRules = '';
		if ($hasHtaccess) {
			$baseRules = (string) @file_get_contents($htaccessPath);
		} elseif ($this->ExistsFile('htaccess.txt')) {
			$baseRules = (string) @file_get_contents(JPATH_SITE . DIRECTORY_SEPARATOR . 'htaccess.txt');
		} else {
			$filename  = 'default_joomla_htaccess.inc';
			$baseRules = (string) @file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $filename);
			// Primer volcado (best-effort); luego haremos escritura atómica al final
			@File::copy(
				JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $filename,
				$htaccessPath
			);
		}

		// Normaliza finales de línea a \n
		$baseRules = str_replace(["\r\n", "\r"], "\n", $baseRules);

		// 2) Elimina TODOS los bloques anteriores de Securitycheck Pro (idempotencia)
		// Esta regex elimina cualquier bloque entre nuestros marcadores, repetidamente.
		$cleanRules = $baseRules;
		$pattern    = '/^\h*## Begin Securitycheck Pro.*?## End Securitycheck Pro.*$/ms';
		while (preg_match($pattern, $cleanRules)) {
			$cleanRules = (string) preg_replace($pattern, '', $cleanRules);
		}
		// Limpia líneas en blanco múltiples
		$cleanRules = preg_replace('/\n{3,}/', "\n\n", $cleanRules) ?? $cleanRules;
		$cleanRules = rtrim($cleanRules) . "\n";

		// 3) Construye nuevos bloques según opciones
		$blocks = [];

		// Prevent access to .ht* files
		if ((bool) $this->getValue('prevent_access')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Prevent access to .ht files',
				'<FilesMatch "^\.ht">',
				'Order deny,allow',
				'Deny from all',
				'</FilesMatch>',
				'## End Securitycheck Pro Prevent access to .ht files',
			]);
		}

		// Directory listing off
		if ((bool) $this->getValue('prevent_unauthorized_browsing') && empty($this->ConfigApplied['prevent_unauthorized_browsing'])) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Prevent Unauthorized Browsing',
				'Options All -Indexes',
				'## End Securitycheck Pro Prevent Unauthorized Browsing',
			]);
		}

		// File Injection Protection
		if ((bool) $this->getValue('file_injection_protection')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro File Injection Protection',
				'RewriteCond %{REQUEST_METHOD} GET',
				'RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=%{REQUEST_SCHEME}:// [OR]',
				'RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=(\.\.//?)+ [OR]',
				'RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=/([a-z0-9_.]//?)+ [NC]',
				'RewriteRule .* - [F]',
				'## End Securitycheck Pro File Injection Protection',
			]);
		}

		// /proc/self/environ protection
		if ((bool) $this->getValue('self_environ')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro self/environ protection',
				'RewriteCond %{QUERY_STRING} proc/self/environ [NC,OR]',
				'## End Securitycheck Pro self/environ protection',
			]);
		}

		// X-Frame-Options
		$xfo = $sanitizeHeaderValue((string) $this->getValue('xframe_options'));
		if ($xfo !== '' && strcasecmp($xfo, 'NO') !== 0) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Xframe-options protection',
				'## Don\'t allow any pages to be framed - Defends against CSRF',
				'<IfModule mod_headers.c>',
				'Header always set X-Frame-Options "' . $xfo . '"',
				'</IfModule>',
				'## End Securitycheck Pro Xframe-options protection',
			]);
		}

		// X-Content-Type-Options
		if ((bool) $this->getValue('prevent_mime_attacks')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Prevent mime based attacks',
				'<IfModule mod_headers.c>',
				'Header always set X-Content-Type-Options "nosniff"',
				'</IfModule>',
				'## End Securitycheck Pro Prevent mime based attacks',
			]);
		}

		// Strict-Transport-Security
		if ((bool) $this->getValue('sts_options')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Strict Transport Security',
				'<IfModule mod_headers.c>',
				'Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"',
				'</IfModule>',
				'## End Securitycheck Pro Strict Transport Security',
			]);
		}

		// X-XSS-Protection (obsoleto, mantenido por compatibilidad)
		if ((bool) $this->getValue('xss_options')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro X-Xss-Protection',
				'<IfModule mod_headers.c>',
				'Header always set X-Xss-Protection "1; mode=block"',
				'</IfModule>',
				'## End Securitycheck Pro X-Xss-Protection',
			]);
		}

		// Content-Security-Policy
		$csp = $sanitizeHeaderValue((string) $this->getValue('csp_policy'), 16384);
		if ($csp !== '') {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Content-Security-Policy protection',
				'<IfModule mod_headers.c>',
				'Header always set Content-Security-Policy "' . $csp . '"',
				'</IfModule>',
				'## End Securitycheck Pro Content-Security-Policy protection',
			]);
		}

		// Referrer-Policy
		$refPol = $sanitizeHeaderValue((string) $this->getValue('referrer_policy'));
		if ($refPol !== '') {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Referrer policy protection',
				'<IfModule mod_headers.c>',
				'Header always set Referrer-Policy "' . $refPol . '"',
				'</IfModule>',
				'## End Securitycheck Pro Referrer policy protection',
			]);
		}

		// Permissions-Policy
		$permPol = $sanitizeHeaderValue((string) $this->getValue('permissions_policy'), 8192);
		if ($permPol !== '') {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Permissions policy (old Feature-Policy) protection',
				'<IfModule mod_headers.c>',
				'Header always set Permissions-Policy "' . $permPol . '"',
				'</IfModule>',
				'## End Securitycheck Pro Permissions policy protection',
			]);
		}

		// User-agent default blacklist
		if ((bool) $this->getValue('default_banned_list')) {
			$uaPath = JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'user_agent_blacklist.inc';
			$userAgentRules = (string) @file_get_contents($uaPath);
			if ($userAgentRules !== '') {
				$blocks[] = trim(str_replace(["\r\n", "\r"], "\n", $userAgentRules));
			}
		}

		// User-agent own blacklist (literalizado para evitar regex peligrosas)
		$ownAgents = $toCleanArray((string) $this->getValue('own_banned_list'));
		if ($ownAgents !== []) {
			$lines = ['## Begin Securitycheck Pro User Own Blacklist'];
			$count = count($ownAgents);
			foreach ($ownAgents as $i => $agent) {
				$cond = 'RewriteCond %{HTTP_USER_AGENT} ' . $quoteForApacheRegex($agent);
				$cond .= ($i < $count - 1) ? ' [NC,OR]' : ' [NC]';
				$lines[] = $cond;
			}
			$lines[] = 'RewriteRule ^(.*)$ - [F,L]';
			$lines[] = '## End Securitycheck Pro User Own Blacklist';
			$blocks[] = implode($nl, $lines);
		}

		// Código propio (permitimos líneas, pero saneadas y sin marcadores)
		$ownCode = $toCleanArray((string) $this->getValue('own_code'));
		if ($ownCode !== []) {
			$lines = ['## Begin Securitycheck Pro User Own Code'];
			foreach ($ownCode as $line) {
				$lines[] = $line;
			}
			$lines[] = '## End Securitycheck Pro User Own Code';
			$blocks[] = implode($nl, $lines);
		}

		// ServerSignature Off
		if ((bool) $this->getValue('disable_server_signature')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Disable Server Signature',
				'ServerSignature Off',
				'## End Securitycheck Pro Disable Server Signature',
			]);
		}

		// PHP Easter Eggs
		if ((bool) $this->getValue('disallow_php_eggs')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Disallow Php Easter Eggs',
				'RewriteCond %{QUERY_STRING} \=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12} [NC]',
				'RewriteRule .* index.php [F]',
				'## End Securitycheck Pro Disallow Php Easter Eggs',
			]);
		}

		// Sensitive files blocklist (lista de nombres/regex sencillas)
		$sensFiles = $toCleanArray((string) $this->getValue('disallow_sensible_files_access'));
		if ($sensFiles !== []) {
			// Literalizamos cada ítem para evitar metacaracteres peligrosos.
			$alts = array_map($quoteForApacheRegex, $sensFiles);
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Disallow Access To Sensitive Files',
				'RewriteRule ^(' . implode('|', $alts) . ')$ - [F]',
				'## End Securitycheck Pro Disallow Access To Sensitive Files',
			]);
		}

		// Hide backend URL (cookie de pase, sin redirecciones en el primer hit)
		$secretRaw = (string) $this->getValue('hide_backend_url');
		$secret    = $sanitizeHtLine($secretRaw);

		$redir = $sanitizeHtLine((string) $this->getValue('hide_backend_url_redirection'));
		if ($redir === '') {
			$redir = '/';
		} elseif ($redir[0] !== '/') {
			$redir = '/' . $redir;
		}

		// Lista de excepciones del usuario: CSV simple como "com_extension1,com_extension2"
		// (también tolera saltos de línea accidentales)
		$backendExceptionsRaw = (string) $this->getValue('backend_exceptions');

		// Divide por comas o saltos de línea, recorta, y filtra vacíos/duplicados
		$items = preg_split('/[,\r\n]+/', $backendExceptionsRaw) ?: [];
		$items = array_map(static fn(string $v): string => trim($v), $items);
		$items = array_values(array_filter($items, static fn(string $v): bool => $v !== ''));

		// Normaliza y de-duplica manteniendo orden
		$seen = [];
		$exceptionsList = [];
		foreach ($items as $ex) {
			// Soporte opcional de prefijos; sin prefijo => qs:
			//   - "qs:algo"   -> excepción por query string
			//   - "uri:/ruta" -> excepción por URI (patrón)
			if (stripos($ex, 'qs:') === 0 || stripos($ex, 'uri:') === 0) {
				$norm = $ex; // se sanitiza más adelante con $sanitizeHtLine
			} else {
				// Por defecto, trata como query-string key/value parcial
				// (ej.: "com_extension1" equivale a "qs:com_extension1")
				$norm = 'qs:' . $ex;
			}

			if (!isset($seen[$norm])) {
				$seen[$norm] = true;
				$exceptionsList[] = $norm;
			}

			// Límite de seguridad (igual que tu lógica)
			if (count($exceptionsList) >= 20) {
				break;
			}
		}

		if ($secret !== '') {
			// Tokens principales (usando tu quoteForApacheRegex)
			$qToken = '(?:^|&)' . $quoteForApacheRegex($secret) . '(?:&|$)';
			$segTok = '^/administrator/' . $quoteForApacheRegex($secret) . '/?$';

			$lines = [
				'## Begin Securitycheck Pro Hide Backend Url',
				'<IfModule mod_rewrite.c>',
				'RewriteEngine On',
				'',
				'# 0) Excepciones de estáticos del admin y otros conocidos',
				'RewriteRule ^administrator/(templates|modules|components|media|images|includes|com_securitycheckprocontrolcenter|com_jchoptimize)/ - [L,NC]',
				'',
			];

			// --- 0.b) Excepciones del usuario ---
			if (!empty($exceptionsList)) {
				$lines[] = '# 0.b) Excepciones del usuario (ALLOW_ADMIN si coinciden)';

				// Normaliza, de-duplica, limita
				$seen = [];
				$max  = 20;

				foreach ($exceptionsList as $ex) {
					if (isset($seen[$ex])) {
						continue;
					}
					$seen[$ex] = true;

					// Detecta prefijo
					$mode = 'qs';
					if (stripos($ex, 'qs:') === 0) {
						$mode = 'qs';
						$ex   = substr($ex, 3);
					} elseif (stripos($ex, 'uri:') === 0) {
						$mode = 'uri';
						$ex   = substr($ex, 4);
					}

					$ex = $sanitizeHtLine($ex);
					if ($ex === '') {
						continue;
					}

					// Construye patrón seguro con soporte de '*'
					//  - Para qs: '*' => [^&]*  (no atraviesa pares de query)
					//  - Para uri: '*' => .*     (libre)
					$parts = explode('*', $ex);
					$quotedParts = array_map(static fn($p) => $quoteForApacheRegex($p), $parts);
					$glue = ($mode === 'qs') ? '(?:[^&]*)' : '.*';
					$safePattern = implode($glue, $quotedParts);

					if ($mode === 'qs') {
						// Anclar a par de query
						$qsCond = '(?:^|&)' . $safePattern . '(?:&|$)';
						// Limitamos a /administrator o index.php dentro
						foreach (['^/administrator/?$', '^/administrator/index\\.php$'] as $adminTarget) {
							$lines[] = 'RewriteCond %{REQUEST_URI} ' . $adminTarget . ' [NC]';
							$lines[] = 'RewriteCond %{QUERY_STRING} ' . $qsCond . ' [NC]';
							$lines[] = 'RewriteRule ^ - [E=ALLOW_ADMIN:1,L]';
						}
					} else {
						// uri: asegura inicio en / (si el usuario no lo puso)
						if ($ex[0] !== '/') {
							$safePattern = '^/' . $safePattern;
						} elseif (strpos($safePattern, '^') !== 0) {
							$safePattern = '^' . $safePattern;
						}
						$lines[] = 'RewriteCond %{REQUEST_URI} ' . $safePattern . ' [NC]';
						$lines[] = 'RewriteRule ^ - [E=ALLOW_ADMIN:1,L]';
					}

					if (count($seen) >= $max) {
						break;
					}
				}

				$lines[] = '';
			}

			// --- 1) Clave en query (?SECRETO) -> HTTP/HTTPS ---
			$lines = array_merge($lines, [
				'# 1) Clave en query (?SECRETO) -> HTTP',
				'RewriteCond %{REQUEST_URI} ^/administrator/?$ [NC]',
				'RewriteCond %{QUERY_STRING} ' . $qToken,
				'RewriteCond %{HTTP_HOST} ^(.+)$',
				'RewriteCond %{HTTPS} !=on',
				'RewriteRule ^ - [E=ALLOW_ADMIN:1,CO=scp_admin:1:%1:900:/:httponly,L]',
				'',
				'# 1) Clave en query (?SECRETO) -> HTTPS',
				'RewriteCond %{REQUEST_URI} ^/administrator/?$ [NC]',
				'RewriteCond %{QUERY_STRING} ' . $qToken,
				'RewriteCond %{HTTP_HOST} ^(.+)$',
				'RewriteCond %{HTTPS} =on',
				'RewriteRule ^ - [E=ALLOW_ADMIN:1,CO=scp_admin:1:%1:900:/:secure:httponly,L]',
				'',				
				'# 3) Si ya hay cookie, permite',
				'SetEnvIfNoCase Cookie "(^|;\\s*)scp_admin=1(;|$)" ALLOW_ADMIN=1',
				'',
				'# 4) Bloqueo de entrada si no hay pase',
				'RewriteCond %{REQUEST_URI} ^/administrator/?$ [NC,OR]',
				'RewriteCond %{REQUEST_URI} ^/administrator/index\\.php$ [NC]',
				'RewriteCond %{ENV:ALLOW_ADMIN} !^1$',
				'RewriteRule ^.*$ ' . $redir . ' [R=302,L]',
				'</IfModule>',
				'## End Securitycheck Pro Hide Backend Url',
			]);

			$blocks[] = implode($nl, $lines);
			
			// Creamos la cookie para evitar ser redirigidos al aplicar las reglas
			$input     = new Input();
			$cookieVal = $input->cookie->get('scp_admin', null);
			if (is_null($cookieVal)) {
				$time = time() + 86400; // 1 día
				/** @var \Joomla\CMS\Application\CMSApplication $app */
				$app  = Factory::getApplication();
				$input->cookie->set('scp_admin', '1', [
					'expires'  => $time,
					'path'     => $app->get('cookie_path', '/'),
					'domain'   => $app->get('cookie_domain', ''),
					'secure'   => $app->isHttpsForced(),
					'httponly' => true,
				]);
			}
		}

		// Optimal expiration & cache-control
		if ((bool) $this->getValue('optimal_expiration_time')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro Optimal Expiration time',
				'<IfModule mod_expires.c>',
				'# Enable expiration control',
				'ExpiresActive On',
				'# Default expiration: 1 hour after request',
				'ExpiresByType text/html "now"',
				'ExpiresDefault "now plus 1 hour"',
				'# CSS and JS expiration: 1 week after request',
				'ExpiresByType text/css "now plus 1 week"',
				'ExpiresByType application/javascript "now plus 1 week"',
				'ExpiresByType application/x-javascript "now plus 1 week"',
				'# Image files expiration: 1 month after request',
				'ExpiresByType image/bmp "now plus 1 month"',
				'ExpiresByType image/gif "now plus 1 month"',
				'ExpiresByType image/jpeg "now plus 1 month"',
				'ExpiresByType image/png "now plus 1 month"',
				'ExpiresByType image/tiff "now plus 1 month"',
				'ExpiresByType image/x-icon "now plus 1 month"',
				'</IfModule>',
				'## Begin Securitycheck Pro Cache-Control Headers',
				'<IfModule mod_headers.c>',
				'<FilesMatch "\.(ico|jpe?g|png|gif|swf)$">',
				'Header set Cache-Control "public"',
				'</FilesMatch>',
				'<FilesMatch "\.(css)$">',
				'Header set Cache-Control "public"',
				'</FilesMatch>',
				'<FilesMatch "\.(js)$">',
				'Header set Cache-Control "private"',
				'</FilesMatch>',
				'<FilesMatch "\.(x?html?|php)$">',
				'Header set Cache-Control "private, must-revalidate"',
				'</FilesMatch>',
				'</IfModule>',
				'## End Securitycheck Pro Optimal Expiration time',
			]);
		}

		// Compress content
		if ((bool) $this->getValue('compress_content')) {
			$blocks[] = implode($nl, [
				'## Begin Securitycheck Pro compress content',
				'<IfModule mod_deflate.c>',
				'AddOutputFilterByType DEFLATE text/html text/xml text/css text/plain',
				'AddOutputFilterByType DEFLATE image/svg+xml application/xhtml+xml application/xml',
				'AddOutputFilterByType DEFLATE application/rdf+xml application/rss+xml application/atom+xml',
				'AddOutputFilterByType DEFLATE text/javascript application/javascript application/x-javascript application/json',
				'AddOutputFilterByType DEFLATE application/x-font-ttf application/x-font-otf',
				'AddOutputFilterByType DEFLATE font/truetype font/opentype',
				'</IfModule>',
				'## End Securitycheck Pro Redirect compress content',
			]);
		}

		// Redirecciones www/non-www (mutuamente excluyentes). Reglas basadas en Apache (sin mirar $_SERVER).
		$toWWW    = (bool) $this->getValue('redirect_to_www');
		$toNonWWW = (bool) $this->getValue('redirect_to_non_www');
		if ($toWWW && !$toNonWWW) {
			$blocks[] = implode($nl, [
				'## Securitycheck Pro Redirect non-www to www',
				'RewriteEngine On',
				'RewriteCond %{HTTP_HOST} !^www\. [NC]',
				// Mantiene protocolo actual (http/https) mediante %{REQUEST_SCHEME}
				'RewriteRule ^(.*)$ %{REQUEST_SCHEME}://www.%{HTTP_HOST}/$1 [R=301,L]',
				'## End Securitycheck Pro Redirect non-www to www',
			]);
		} elseif ($toNonWWW && !$toWWW) {
			$blocks[] = implode($nl, [
				'## Securitycheck Pro Redirect www to non-www',
				'RewriteEngine On',
				'RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]',
				'RewriteRule ^(.*)$ %{REQUEST_SCHEME}://%1/$1 [R=301,L]',
				'## End Securitycheck Pro Redirect www to non-www',
			]);
		}

		// 4) Ensambla resultado final
		$newContent = rtrim($cleanRules, "\n") . "\n";
		if ($blocks !== []) {
			$newContent .= "\n" . implode("\n\n", $blocks) . "\n";
		}

		// Si no cambia, no escribimos
		$prev = $hasHtaccess ? (string) @file_get_contents($htaccessPath) : '';
		if ($prev !== '' && str_replace(["\r\n", "\r"], "\n", $prev) === $newContent) {
			return true;
		}

		// 5) Escritura atómica (tmp ? rename) + permisos
		$tmpPath = $htaccessPath . '.tmp_' . bin2hex(random_bytes(4));
		if (@File::write($tmpPath, $newContent) !== true) {
			return false;
		}

		// Endurece permisos (ignora errores en filesystems no compatibles)
		@chmod($tmpPath, 0644);

		// Renombrado atómico
		if (!@rename($tmpPath, $htaccessPath)) {
			// Limpieza si falla
			@File::delete($tmpPath);
			return false;
		}

		return true;
	}

    /**
     * Borra el fichero .htaccess
     *
     *
     * @return  bool
     *     
     */
    function delete_htaccess()
    {
		try{		
			$res = File::delete(JPATH_SITE.DIRECTORY_SEPARATOR.'.htaccess');
			return $res;
		} catch (\Exception $e)
		{
			return false;
		}        
    }

    /**
     * Genera las reglas equivalentes a .htaccess en ficheros NGINX
     *
     *
     * @return  string
     *     
     */
    function generate_rules()
    {

        $rules = null;
		$ExistsHtaccess = $this->ExistsFile('.htaccess');  // Comprobamos si existe el archivo .htaccess
    
        /* Comprobamos si hay que proteger los archivos .ht */
        if ($this->getValue("prevent_access")) {
            $rules .= PHP_EOL . "# Begin Securitycheck Pro Prevent access to .ht files" . PHP_EOL;
            $rules .= "\tlocation ~ /\.ht { deny all; }" . PHP_EOL;
            $rules .= "# End Securitycheck Pro Prevent access to .ht files" . PHP_EOL;        
        }
    
        /* Comprobamos si hay que aplicar la lista de user-agents por defecto */
        if ($this->getValue("default_banned_list")) {
            $user_agent_rules = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'user_agent_blacklist_nginx.inc');
            // Añadimos el contenido del fichero por defecto al final del buffer
            $rules .= PHP_EOL . $user_agent_rules . PHP_EOL;
        }
    
        /* Comprobamos si hay que aplicar la lista de user-agents establecida por el usuario */
		$own_user_agents = array_filter(explode(PHP_EOL, (string) $this->getValue("own_banned_list")),static fn($item) => trim($item) !== '');
		if (!empty($own_user_agents)) {  
            $rules .= PHP_EOL . "# Begin Securitycheck Pro User Own Blacklist" . PHP_EOL;
            $count = 1;
            $nginx_list = '';
                    
            foreach ($own_user_agents as $agent)
            {                
                $nginx_list .= trim($agent);                            
                if ($count < sizeof($own_user_agents)) {
                    $nginx_list .= '|';
                    $count++;
                } 
            }
                
            $rules .= "\tif (\$http_user_agent ~* " . $nginx_list . ") { return 403; }" . PHP_EOL;
            $rules .= "# End Securitycheck Pro User Own Blacklist" . PHP_EOL;
        }
    
        /* Comprobamos si hay que deshabilitar la firma del servidor*/
        if ($this->getValue("disable_server_signature")) {
            $rules .= PHP_EOL . "# Begin Securitycheck Pro Disable Server Signature" . PHP_EOL;
            $rules .= "server_tokens off;" . PHP_EOL;
            $rules .= "# End Securitycheck Pro Disable Server Signature" . PHP_EOL;
        }
    
        /* Comprobamos si hay que prohibir los 'easter-eggs' de PHP */
        if ($this->getValue("disallow_php_eggs")) {
            $rules .= PHP_EOL . "# Begin Securitycheck Pro Disallow Php Easter Eggs" . PHP_EOL;
            $rules .= "\tset \$susquery 0;" . PHP_EOL;
            $rules .= "\tif (\$args ~* \"=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\") { set \$susquery 1; }" . PHP_EOL;
            $rules .= "\tif (\$susquery = 1) { return 403; }" . PHP_EOL;
            $rules .= "# End Securitycheck Pro Disallow Php Easter Eggs" . PHP_EOL;
        }
    
        /* Comprobamos si hay que prohibir el acceso a archivos que pueden contener información sensible o que tengan alguna vulnerabilidad */
		$disallow_sensible_files_access = array_filter(explode(PHP_EOL, (string) $this->getValue("disallow_sensible_files_access")),static fn($item) => trim($item) !== '');
		if (!empty($disallow_sensible_files_access)) { 
            if (!$ExistsHtaccess || !$this->ConfigApplied['disallow_sensible_files_access']) {
                $rules .= PHP_EOL . "## Begin Securitycheck Pro Disallow Access To Sensitive Files";
                $rules .=  "\trewrite ^/(";            
                                
                foreach ($disallow_sensible_files_access as $code)
                {                    
                    $rules .= "|" . trim($code);                
                }            
                $rules .= ")$ /not_found last;";
                $rules .= PHP_EOL . "## End Securitycheck Pro Disallow Access To Sensitive Files" . PHP_EOL;
            }
        }
    
        /* Comprobamos si hay que aplicar código del usuario */
		$own_code = array_filter(explode(PHP_EOL, (string) $this->getValue("own_code")),static fn($item) => trim($item) !== '');
		if (!empty($own_code)) { 		
			if (!$ExistsHtaccess || !$this->ConfigApplied['own_code']) {
                $rules .= PHP_EOL . "## Begin Securitycheck Pro User Own Code";
                $count = 1;
                    
                foreach ($own_code as $code)
                {                    
                    $rules .= PHP_EOL . trim($code);                
                }                
                $rules .= PHP_EOL . "## End Securitycheck Pro User Own Code" . PHP_EOL;
            }
        }
    
        /* Comprobamos si hay que proteger las cabeceras X-Frame del navegador */
        $xframe_options = $this->getValue("xframe_options");
        if ((!empty($xframe_options)) && ($this->getValue("xframe_options") != 'NO')) {        
            $rules .= PHP_EOL . "## Begin Securitycheck Pro Xframe-options protection";
            $rules .= PHP_EOL . "## Don't allow any pages to be framed - Defends against CSRF";
            $rules .= PHP_EOL . 'add_header X-Frame-Options "' . $this->getValue("xframe_options") . '";';            
            $rules .= PHP_EOL . "## End Securitycheck Pro Xframe-options protection" . PHP_EOL;            
        }
    
        /* Comprobamos si hay que establecer protección contra ataques basados en mime*/
        if ($this->getValue("prevent_mime_attacks")) {        
            $rules .= PHP_EOL . "## Begin Securitycheck Pro Prevent mime based attacks";            
            $rules .= PHP_EOL . 'add_header X-Content-Type-Options "nosniff";';    
            $rules .= PHP_EOL . "## End Securitycheck Pro Prevent mime based attacks" . PHP_EOL;        
        }
    
        /* Comprobamos si hay que establecer protección STS (Strict Transport Security) */
        $sts_options = $this->getValue("sts_options");
        if ($sts_options) {        
            $rules .= PHP_EOL . "## Begin Securitycheck Pro Strict Transport Security";
            $rules .= PHP_EOL . 'add_header Strict-Transport-Security "max-age=31536000; includeSubdomains";';    
            $rules .= PHP_EOL . "## End Securitycheck Pro Strict Transport Security" . PHP_EOL;            
        }
    
        /* Comprobamos si hay que establecer protección X-Xss-Protection */
        $xss_options = $this->getValue("xss_options");
        if ($xss_options) {        
            $rules .= PHP_EOL . "## Begin Securitycheck Pro X-Xss-Protection";
            $rules .= PHP_EOL . 'add_header X-Xss-Protection "1; mode=block"';            
            $rules .= PHP_EOL . "## End Securitycheck Pro X-Xss-Protection" . PHP_EOL;        
        }
    
        /* Comprobamos si hay que ocultar la url del backend */
        if ($this->getValue("hide_backend_url")) {
            $rules .= "# Begin Securitycheck Pro Hide Backend Url" . PHP_EOL;
            $rules .= "\tset \$rule_1 0;" . PHP_EOL;
            $rules .= "\tif (\$http_referer !~* administrator) { set \$rule_1 6\$rule_1; }" . PHP_EOL;
            $rules .= "\tif (\$args !~ \"^" . $this->getValue("hide_backend_url") . "\") { set \$rule_1 9\$rule_1; }" . PHP_EOL;
            $rules .= "\tif (\$rule_1 = 960) {" . PHP_EOL;
            $rules .= "\t\trewrite ^(.*/)?administrator /not_found redirect;" . PHP_EOL;
            $rules .= "\t\trewrite ^/administrator(.*)$ /not_found redirect;" . PHP_EOL;
            $rules .= "\t}" . PHP_EOL;
            $rules .= "# End Securitycheck Pro Hide Backend Url" . PHP_EOL;
        }
        
        return $rules;
 
    }

   /**
     * Restaura el fichero .htaccess desde .htaccess.original de forma segura.
     *
     *
     * @return bool true si se restaura correctamente; false si hay errores (y se notifica).
     */
    public function restoreHtaccess(): bool
    {
        $app      = Factory::getApplication();
        $siteRoot = rtrim((string) JPATH_SITE, DIRECTORY_SEPARATOR);

        $src     = $siteRoot . DIRECTORY_SEPARATOR . '.htaccess.original';
        $dst     = $siteRoot . DIRECTORY_SEPARATOR . '.htaccess';
        $tmp     = $siteRoot . DIRECTORY_SEPARATOR . '.htaccess.' . bin2hex(random_bytes(6)) . '.tmp';
        $backup  = $siteRoot . DIRECTORY_SEPARATOR . '.htaccess.bak.' . gmdate('YmdHis');

        try {
            // --- Normaliza y valida rutas dentro de JPATH_SITE ---
            $srcClean = Path::clean($src);
            $dstClean = Path::clean($dst);
            $tmpClean = Path::clean($tmp);
            $bakClean = Path::clean($backup);

            foreach ([$srcClean, $dstClean, $tmpClean, $bakClean] as $p) {
                // Si realpath falla (p.ej. archivo no existe aún), comprobamos prefijo con la ruta normalizada
                $real = realpath($p) ?: $p;
                if (strpos($real, $siteRoot) !== 0) {
                    throw new \RuntimeException('Ruta fuera del directorio del sitio.');
                }
            }

            // --- Comprobaciones del origen ---
            if (!is_file($srcClean) || !is_readable($srcClean)) {
                throw new \RuntimeException(Text::_('COM_SECURITYCHECKPRO_HTACCESS_ORIGINAL_NOT_FOUND_OR_UNREADABLE'));
            }
            if (is_link($srcClean)) {
                throw new \RuntimeException('El archivo .htaccess.original no puede ser un symlink.');
            }

            // --- Si existe destino, valida y crea backup ---
            if (file_exists($dstClean)) {
                if (!is_file($dstClean)) {
                    throw new \RuntimeException('El destino .htaccess existe pero no es un archivo regular.');
                }
                if (is_link($dstClean)) {
                    throw new \RuntimeException('El destino .htaccess no puede ser un symlink.');
                }
                if (!@File::copy($dstClean, $bakClean)) {
                    throw new \RuntimeException('No se pudo crear la copia de seguridad del .htaccess actual.');
                }
            }

            // --- Copia a temporal y fija permisos conservadores ---
            if (!File::copy($srcClean, $tmpClean)) {
                throw new \RuntimeException('Fallo al copiar .htaccess.original a archivo temporal.');
            }
            @chmod($tmpClean, 0644);

            // --- Reemplazo atómico: elimina destino (si existe) y mueve temporal a destino ---
            if (file_exists($dstClean) && !@File::delete($dstClean)) {
                // Limpieza del temporal antes de abortar
                @File::delete($tmpClean);
                throw new \RuntimeException('No se pudo eliminar el .htaccess actual antes del reemplazo.');
            }
            if (!File::move($tmpClean, $dstClean)) {
                // Intenta limpiar el temporal si falló el move
                @File::delete($tmpClean);
                throw new \RuntimeException('No se pudo mover el archivo temporal a .htaccess.');
            }

            return true;
        } catch (\Throwable $e) {
            // Limpieza de residuos temporales
            if (is_file($tmpClean ?? '')) {
                @File::delete($tmpClean);
            }

            // Notificación en backend (sin filtrar detalles técnicos sensibles al usuario final)
            $app->enqueueMessage(
                Text::sprintf('COM_SECURITYCHECKPRO_HTACCESS_RESTORE_FAILED', $e->getMessage()),
                'error'
            );

            return false;
        }
    }

}
