<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel;
use Joomla\Component\Users\Administrator\Model\UserModel;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Updater\Update;
use Joomla\CMS\Updater\Updater;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Client\FtpClient;
use Joomla\Filesystem\Path;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\AuthenticationHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Client\ClientHelper;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\CpanelModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\ProtectionModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\DatabaseupdatesModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\SecuritycheckproModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Application\CMSApplication;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Helper\OverallScoreHelper;

if (!defined('SCP_USER_AGENT')) define('SCP_USER_AGENT', 'Securitycheck Pro User agent');

class JsonModel extends BaseModel
{
	public const STATUS_OK            = 200;
    public const STATUS_NOT_AUTH      = 401;
    public const STATUS_NOT_ALLOWED   = 403;
    public const STATUS_NOT_FOUND     = 404;
    public const STATUS_INVALID_METHOD= 405;
    public const STATUS_ERROR         = 500;
    public const STATUS_NOT_IMPLEMENTED = 501;
    public const STATUS_NOT_AVAILABLE = 503;

    public const CIPHER_RAW      = 1;
    public const CIPHER_AESCBC256= 2;

    /** @var int */
    private int $status = self::STATUS_OK;

    /** @var int */
    private int $cipher = self::CIPHER_AESCBC256;

    /** @var array<string,mixed>|string */
    public array|string $data = [];

    /** @var string */
    private string $password = '';

    /** @var array{product:string,latest:string,latest_status:string,latest_type:string} */
    private array $backupinfo = [
        'product' => '',
        'latest' => '',
        'latest_status' => '',
        'latest_type' => '',
    ];

    /** @var int */
    private int $update_database_plugin_needs_update = 0;

    /** @var array<string,mixed> */
    private array $info = [];

    /** @var string URL base del CC (descifrada) */
    private string $site = '';
    private string $storedCcUrl = '';

    /** @var string */
    private string $site_id = '';

    /** @var string|null */
    public ?string $log_filename = null;

    /** @var string */
    private string $folder_path;

    /** @var array<int,mixed> */
    private array $array_result = [];

    public function __construct()
    {
        parent::__construct();

        $this->folder_path = JPATH_ADMINISTRATOR
            . DIRECTORY_SEPARATOR . 'components'
            . DIRECTORY_SEPARATOR . 'com_securitycheckpro'
            . DIRECTORY_SEPARATOR . 'scans';
    }

    /**
     * Registra tarea (igual que tu l�gica actual)
     *
     * @param  string $json
     * @return string
     */
    public function register_task(string $json): string
    {
        $task_checker_enabled = (int) $this->PluginStatus(9);

        if ($task_checker_enabled === 0) {
            return 'Error: task checker plugin is disabled';
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $object = (object) [
            'storage_key'   => 'remote_task',
            'storage_value' => $json,
        ];

        try {
            $db->insertObject('#__securitycheckpro_storage', $object);
        } catch (\Throwable $e) {
            $this->log_filename = 'error.php';
            $this->write_log($e->getMessage(), 'ERROR');
            return 'Error: Operation failed';
        }

        // Evento observado por Securitycheckpro_task_checker
        $event = AbstractEvent::create('onSCPTaskAdded', ['subject' => $this]);
        Factory::getApplication()->getDispatcher()->dispatch('onSCPTaskAdded', $event);

        return 'Task added';
    }

    /**
     * Ejecuta una tarea (entrada: JSON guardado)
     *
     * @param  string $json
     * @return void
     */
    public function execute(string $json): void
    {
        // 1) Limpia remote_task (como en tu c�digo)
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery("DELETE FROM #__securitycheckpro_storage WHERE storage_key='remote_task'");
            $db->execute();
        } catch (\Throwable $e) {
            // No abortamos por esto
            $this->log_filename = 'error.php';
            $this->write_log('execute: cleanup remote_task failed: ' . $e->getMessage(), 'ERROR');
        }

        // 2) Parseo robusto del JSON
        $request = $this->decodeRequestJson($json);

        if ($request === null) {
            $this->log_filename = 'error.php';
            $this->write_log('execute: JSON not valid', 'ERROR');
            return;
        }

        // 3) Extraer campos m�nimos (con validaci�n)
        $this->cipher  = isset($request['cipher']) ? (int) $request['cipher'] : self::CIPHER_RAW;

        $body = $request['body'] ?? null;
        if (!is_array($body)) {
            $this->failRaw('Method not configured', self::STATUS_NOT_FOUND, $request);
            return;
        }

        $this->site_id = isset($body['id']) ? (string) $body['id'] : '';
        if ($this->site_id === '') {
            // No sabemos a qui�n responder
            return;
        }

        $task = isset($body['task']) ? (string) $body['task'] : '';
        if ($task === '') {
            $this->failRaw('Method not configured', self::STATUS_NOT_FOUND, $request);
            return;
        }

        // 4) Config + secret
        $config = $this->Config('controlcenter');

        if (!is_array($config)) {
            $this->failRaw("Can't get configuration", self::STATUS_ERROR, $request);
            return;
        }

        $enabled = !empty($config['control_center_enabled']);

        $this->password = isset($config['secret_key']) && is_string($config['secret_key']) ? $config['secret_key'] : '';
        $this->storedCcUrl = isset($config['control_center_url']) && is_string($config['control_center_url']) ? $config['control_center_url'] : '';
        if ($this->password === '') {
            $this->failRaw('Remote password not configured', self::STATUS_NOT_AUTH, $request);
            return;
        }

        if (!$enabled) {
            $this->failRaw('Access denied', self::STATUS_NOT_AVAILABLE, $request);
            return;
        }

        // 5) Resolver callback base URL (site cifrado o referrer)
        $callbackBase = $this->resolveCallbackBaseUrl($request);
        if ($callbackBase === null) {
            // No podemos responder
            $this->log_filename = 'error.php';
            $this->write_log('execute: cannot resolve callback base url. Are both secret keys equal?', 'ERROR');
            return;
        }

        // Guardamos siempre control_center_url normalizada (tu requisito)
        $this->site = $callbackBase;
        $this->updateControlCenterUrlInStorage($callbackBase);

        // 6) Protege contra peticiones RAW fraudulentas (tu l�gica original)
        if ($this->cipher === self::CIPHER_RAW) {
            $rawForbidden = [
                'getStatus', 'checkVuln', 'checkLogs', 'checkPermissions', 'checkIntegrity', 'deleteBlocked',
                'checkmalware', 'UpdateExtension', 'Backup', 'unlocktables', 'locktables', 'enable_analytics', 'disable_analytics',
            ];

            if (in_array($task, $rawForbidden, true)) {
                $this->data   = 'Go away, hacker!';
                $this->status = self::STATUS_NOT_ALLOWED;
                $this->cipher = self::CIPHER_RAW;
                $this->sendResponsePostPrefer();
                return;
            }
        }

        // 7) Preparar logs (si tu c�digo ya lo hace, puedes mantenerlo o pegarlo aqu�)
        $this->prepareControlCenterLogfile($callbackBase);

        // 8) Ejecutar tarea
        switch ($task) {
            case 'getStatus':
                $this->getStatus();
                break;
            case 'checkVuln':
                $this->checkVuln();
                break;
            case 'checkLogs':
                $this->checkLogs();
                break;
            case 'checkPermissions':
                $this->checkPermissions();
                break;
            case 'checkIntegrity':
                $this->checkIntegrity();
                break;
            case 'deleteBlocked':
                $this->deleteBlocked();
                break;
            case 'checkmalware':
                $this->checkMalware();
                break;
            case 'UpdateExtension':
                /** @var mixed $arg */
                $arg = $body['data'] ?? null;
                $this->UpdateExtension($arg);
                break;
            case 'Backup':
                /** @var mixed $arg */
                $arg = $body['data'] ?? null;
                $this->Backup($arg);
                break;
            case 'Uploadinstall':
                /** @var mixed $arg */
                $arg = $body['data'] ?? null;
                $this->Upload_install($arg);
                break;
            case 'Connect':
                $this->Connect();
                break;
            case 'UpdateConnect':
                /** @var mixed $arg */
                $arg = $body['data'] ?? null;
                $this->UpdateConnect($arg);
                break;
            case 'unlocktables':
                $this->write_log('UNLOCKTABLES task received');
                $this->unlocktables();
                break;
            case 'locktables':
                $this->locktables();
                break;           
            case 'enable_analytics':
                /** @var mixed $arg */
                $arg = $body['data'] ?? null;
                $this->enable_analytics($arg);
                break;
            case 'disable_analytics':
                /** @var mixed $arg */
                $arg = $body['data'] ?? null;
                $this->disable_analytics($arg);
                break;

            default:
                $this->data   = 'Method not configured';
                $this->status = self::STATUS_NOT_FOUND;
                $this->cipher = self::CIPHER_RAW;
                $this->sendResponsePostPrefer();
                return;
        }

        // 9) Enviar respuesta al Control Center
        $this->sendResponsePostPrefer();
    }

    /**
     * SEND RESPONSE: POST preferente + fallback GET legacy.
     * Sin redirects.
     *
     * @param string|null $connect_back_url
     * @return void
     */
    public function sendResponsePostPrefer(?string $connect_back_url = null): void
    {
        if ($connect_back_url !== null) {
            $this->cipher = self::CIPHER_RAW;
			// Vamos a establecer una id para el sitio o el controlador del controlcenter rechazará la llamada
			$this->site_id = '1';
        }

        if (is_string($connect_back_url) && $connect_back_url !== '') {
            $this->site = $connect_back_url;
        }

        $baseUrl = $this->normalizeBaseUrl((string) $this->site);
        if ($baseUrl === null) {
            $this->log_filename = 'error.php';
            $this->write_log('sendResponse: invalid callback base url', 'ERROR');
            return;
        }

        $response = [
            'cipher' => $this->cipher,
            'body'   => [
                'status' => $this->status,
                'data'   => null,
                'id'     => $this->site_id,
            ],
        ];

        $dataJson = json_encode($this->data, JSON_UNESCAPED_UNICODE);
        if ($dataJson === false) {
            $dataJson = 'null';
        }

        $this->write_log('Sending response. Data: ' . $dataJson);

        if ($this->cipher === self::CIPHER_AESCBC256) {
            $enc = $this->encryptLegacy($dataJson, $this->password);
            if ($enc === null) {
                $this->log_filename = 'error.php';
                $this->write_log('sendResponse: encrypt failed', 'ERROR');
                return;
            }
            $dataJson = $enc;
        }

        $response['body']['data'] = $dataJson;

        $responseJson = json_encode($response, JSON_UNESCAPED_SLASHES);
        if ($responseJson === false) {
            $this->log_filename = 'error.php';
            $this->write_log('sendResponse: json_encode response failed', 'ERROR');
            return;
        }

        // Token (mantener trazabilidad si falta/no coincide)
        $token = $this->getTokenFromConfig();
        if ($token === '') {
            $this->log_filename = 'error.php';
            $this->write_log("Can't send the reply to the Control Center. Token is empty or doesn't match with Control Center.", 'ERROR');
            return;
        }

        /** @var list<string> $headers */
        $headers = [
            'Token: ' . $token,
        ];

        // 1) Intento POST (nuevo)
        $postUrl = $baseUrl . 'index.php?option=com_securitycheckprocontrolcenter&view=json&format=raw';
        $postOk  = $this->curlPostNoFollow($postUrl, $headers, ['json' => $responseJson]);
		
        if ($postOk === true) {
            $this->write_log('Response sent to ' . $baseUrl . ' (POST)');
            return;
        }

        // 2) Fallback GET legacy (mientras CC sea viejo)
        $this->write_log('WARNING: Using insecure GET fallback. Update Control Center to support POST.', 'WARNING');

        $encodedResponse = urlencode($responseJson);
        $maxGetLength = 8000;
        if (strlen($encodedResponse) > $maxGetLength) {
            $this->write_log('Response too large for GET fallback (' . strlen($encodedResponse) . ' bytes). Aborting.', 'ERROR');
            return;
        }

        $getUrl = $baseUrl
            . 'index.php?option=com_securitycheckprocontrolcenter&view=json&format=raw&json='
            . $encodedResponse;

        $raw = $this->curlGetNoFollow($getUrl, $headers);

        $this->write_log('Response sent to ' . $baseUrl . ' (GET fallback)');
        if ($raw !== null) {
            $this->write_log('Curl reply ' . $raw);
        }
    }

    /**
     * --- Helpers cr�ticos / seguridad ---
     */

    /**
     * @param string $json
     * @return array<string,mixed>|null
     */
    private function decodeRequestJson(string $json): ?array
    {
        $trim = rtrim($json, "\0 \t\r\n");

        if ($trim === '' || $trim[0] !== '{') {
            // Intento urldecode por compatibilidad con {%22...}
            $trim = urldecode($trim);
        }

        $req = json_decode($trim, true);
        return is_array($req) ? $req : null;
    }

    /**
     * Resuelve callback base URL:
     * - primero body.site (cifrado) -> decrypt()
     * - si falla o est� vac�o, usa referrer si existe
     *
     * @param array<string,mixed> $request
     * @return string|null Base URL normalizada con trailing slash
     */
    private function resolveCallbackBaseUrl(array $request): ?string
    {
        $ref = '';
        if (isset($request['referrer']) && is_string($request['referrer'])) {
            $ref = $request['referrer'];
        }

        $body = $request['body'] ?? null;
        if (!is_array($body)) {
            return $this->normalizeBaseUrl($ref);
        }

        $siteEnc = $body['site'] ?? '';
        if (is_string($siteEnc) && $siteEnc !== '') {
            $siteDec = $this->decrypt($siteEnc, $this->password); // legacy
            if ($siteDec !== '' && strpos($siteDec, 'Internal') === false) {
                $norm = $this->normalizeBaseUrl($siteDec);
                if ($norm !== null) {
                    return $norm;
                }
            }
        }

        // fallback a referrer
        return $this->normalizeBaseUrl($ref);
    }

    /**
     * Guarda control_center_url siempre (normalizado).
     *
     * @param string $baseUrl
     * @return void
     */
    private function updateControlCenterUrlInStorage(string $baseUrl): void
    {
        try {
            $cc = $this->Config('controlcenter');
            if (!is_array($cc)) {
                $cc = [];
            }
            $cc['control_center_url'] = $baseUrl;
            $this->SaveStorageParams($cc, 'controlcenter');
        } catch (\Throwable $e) {
            $this->log_filename = 'error.php';
            $this->write_log('updateControlCenterUrlInStorage: ' . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Prepara logfile controlcenter (rotaci�n etc.)
     * Puedes mantener tu implementaci�n original si prefieres.
     *
     * @param string $baseUrl
     * @return void
     */
    private function prepareControlCenterLogfile(string $baseUrl): void
    {
        try {
            $params = ComponentHelper::getParams('com_securitycheckpro');
            $maxKb = (int) $params->get('controlcenter_log_size', 2048);

            $filemanager_model = new FilemanagerModel();
			$this->log_filename = $filemanager_model->get_log_filename("controlcenter_log", true);
			if (empty($this->log_filename)) {
				$this->log_filename = $filemanager_model->prepareLog("controlcenter");					
			} else if ( (file_exists($this->folder_path.DIRECTORY_SEPARATOR.$this->log_filename)) && (filesize($this->folder_path.DIRECTORY_SEPARATOR.$this->log_filename) > ($maxKb * 1024)) ) {
				//Rotate log file
				File::delete($this->folder_path.DIRECTORY_SEPARATOR.$this->log_filename);
				$this->log_filename = $filemanager_model->prepareLog("controlcenter");
			}	
        } catch (\Throwable $e) {
            $this->log_filename = 'error.php';
            $this->write_log('prepareControlCenterLog: ' . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * @param string $msg
     * @param int $status
     * @param array<string,mixed> $request
     * @return void
     */
    private function failRaw(string $msg, int $status, array $request): void
    {
        $this->data   = $msg;
        $this->status = $status;
        $this->cipher = self::CIPHER_RAW;

        // Intentamos devolver al referrer si est�
        $ref = isset($request['referrer']) && is_string($request['referrer']) ? $request['referrer'] : '';
        $this->site = $ref;

        $this->log_filename = 'error.php';
        $this->write_log('execute: ' . $msg, 'ERROR');

        if ($this->normalizeBaseUrl($ref) !== null) {
            $this->sendResponsePostPrefer();
        }
    }

    /**
     * Normaliza base url: solo http/https, sin credenciales, con trailing slash.
     * No bloquea rangos privados (tu requisito).
     *
     * @param string $url
     * @return string|null
     */
    private function normalizeBaseUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Acepta URLs tipo "https://host/path" y se queda con base (path hasta /)
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        $host = isset($parts['host']) ? (string) $parts['host'] : '';
        if ($host === '') {
            return null;
        }

        // No permitir user:pass@
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : 0;
        $portPart = $port > 0 ? ':' . $port : '';

        // Queremos base URL: scheme://host[:port]/
        return $scheme . '://' . $host . $portPart . '/';
    }

    /**
     * @return string
     */
    private function getTokenFromConfig(): string
    {
        $token = '';
        $ccConfig = $this->getControlCenterConfig();
        if (isset($ccConfig['token']) && is_string($ccConfig['token'])) {
            $token = $ccConfig['token'];
        }
        return $token;
    }

    /**
     * GET sin seguir redirects.
     *
     * @param string $url
     * @param list<string> $headers
     * @return string|null
     */
    private function curlGetNoFollow(string $url, array $headers): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, SCP_USER_AGENT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $this->write_log('curlGetNoFollow error: ' . curl_error($ch), 'ERROR');
            return null;
        }

        return (string) $raw;
    }

    /**
     * POST sin redirects. Devuelve true si HTTP 2xx.
     *
     * @param string $url
     * @param list<string> $headers
     * @param array<string,string> $fields
     * @return bool
     */
    private function curlPostNoFollow(string $url, array $headers, array $fields): bool
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, SCP_USER_AGENT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&'));

        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($raw === false) {
            $this->write_log('curlPostNoFollow error: ' . curl_error($ch), 'ERROR');            
            return false;
        }

        return ($code >= 200 && $code <= 299);
    }

    /**
     * --- Storage helpers (tu c�digo original) ---
     */

    /**
     * @param string $key_name
     * @return array<string,mixed>|null
     */
    private function Config(string $key_name): ?array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('storage_value'))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key') . ' = ' . $db->quote($key_name));

        $db->setQuery($query);
        $res = $db->loadResult();
        if (!is_string($res) || $res === '') {
            return null;
        }

        $decoded = json_decode($res, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string,mixed> $params
     * @param string $key_name
     * @return void
     */
    private function SaveStorageParams(array $params, string $key_name): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $storage_value = json_encode($params);
        if ($storage_value === false) {
            $storage_value = '{}';
        }

        $object = new \stdClass();
        $object->storage_key = $key_name;
        $object->storage_value = $storage_value;

        try {
            $db->updateObject('#__securitycheckpro_storage', $object, 'storage_key');
        } catch (\Throwable $e) {
            $this->log_filename = 'error.php';
            $this->write_log('SaveStorageParams: ' . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Log simple
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    public function write_log(string $message, string $level = 'INFO'): void
    {
        if ($this->log_filename === '') {
            $this->log_filename = 'error.php';
        }

        $fp = @fopen($this->folder_path . DIRECTORY_SEPARATOR . $this->log_filename, 'ab');
        if ($fp === false) {
            return;
        }

        if (strlen($message) > 4096) {
            $message = substr($message, 0, 4096) . '...[truncated]';
        }

        $timestamp = $this->get_Joomla_timestamp();
        $line = $level . "    |   " . $timestamp . "   |   " . $message . " |\r\n";

        @fwrite($fp, $line);
        @fclose($fp);
    }

    /**
     * @return string
     */
    public function get_Joomla_timestamp(): string
    {
		/** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();
        $config = $app->getConfig();
        $offset = (string) $config->get('offset', 'UTC');

        try {
            $date = new \DateTime('now', new \DateTimeZone($offset !== '' ? $offset : 'UTC'));
        } catch (\Throwable) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return $date->format('Y-m-d H:i:s');
    }
	
	/**
     * Funci�n que verifica una fecha
     *
	 * @param   string             $date   	The date to check
	 * @param   bool             $strict  
	 * 	 
     * @return  bool
	 *     
     */
	public function verifyDate($date, $strict = true)
	{
		$dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $date);

		if ($strict)
		{
			$errors = \DateTime::getLastErrors();

			if (!empty($errors['warning_count']))
			{
				return false;
			}
		}

		return $dateTime !== false;
	}

	/**
     * Funci�n que devuelve el estado de la extensi�n remota
     *
	 * @param   bool             $opcion   	The option
	 * 	 
     * @return  void
	 *     
     */
	public function getStatus($opcion=true)
	{
		
		$this->write_log("Launching GETSTATUS task");
				
		// Inicializamos las variables
		$extension_updates = null;
		$installed_version = "0.0.0";
		$hasUpdates = 0;
		$today_logs = 0;
		$updates_to_send = '';
		$remote_data = array();

		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Buscamos la versi�n de SCP instalada
		$query = $db->getQuery(true)
			->select($db->quoteName('manifest_cache'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('name') . ' = ' . $db->quote('Securitycheck Pro'));
		$db->setQuery($query);
		$result = $db->loadResult();
		$manifest = json_decode($result);
		$installed_version = isset($manifest->version) ? $manifest->version : "0.0.0";
		
		$this->write_log("Importing models...");
				
		$cpanel_model = new CpanelModel();
		$filemanager_model = new FileManagerModel();
		$update_model = new DatabaseupdatesModel();
		
		$this->write_log("Getting update database plugin status...");
		// Comprobamos el estado del plugin Update Database
		$update_database_plugin_installed = $update_model->PluginStatus(4);
		$update_database_plugin_version = $update_model->getDatabaseVersion();
		$update_database_plugin_last_check = $update_model->lastCheck();
		
		$this->write_log("Checking vulnerable extensions...");
		// Vulnerable components
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = "SELECT COUNT(*) FROM #__securitycheckpro WHERE Vulnerable='Si'";
		$db->setQuery($query);
		$db->execute();
		$vuln_extensions = $db->loadResult();
		
		$this->write_log("Checking unread logs...");
		// Check for unread logs
		(int) $logs_pending = $cpanel_model->LogsPending();		
				
		$this->write_log("Getting info from permissions, integrity and malware scan...");
		// Get files with incorrect permissions from database
		$files_with_incorrect_permissions = $filemanager_model->loadStack("filemanager_resume", "files_with_incorrect_permissions");

		// If permissions task has not been launched, we set a '0' value.
		if (is_null($files_with_incorrect_permissions))
		{
			$files_with_incorrect_permissions = 0;
		}

		// FileManager last check
		$last_check = $filemanager_model->loadStack("filemanager_resume", "last_check");

		// Get files with incorrect integrity from database
		$files_with_bad_integrity = $filemanager_model->loadStack("fileintegrity_resume", "files_with_bad_integrity");

		// If permissions task has not been launched, whe set a '0' value.
		if (is_null($files_with_bad_integrity))
		{
			$files_with_bad_integrity = 0;
		}

		// FileIntegrity last check
		$last_check_integrity = $filemanager_model->loadStack("fileintegrity_resume", "last_check_integrity");

		// Malwarescan last check
		$last_check_malwarescan = $filemanager_model->loadStack("malwarescan_resume", "last_check_malwarescan");

		// Get suspicious files
		$suspicious_files = $filemanager_model->loadStack("malwarescan_resume", "suspicious_files");

		// �ltima optimizaci�n bbdd
		$last_check_database_optimization = $this->GetCampoFilemanager('last_check_database');

		// If malwarescan has not been launched, we set a '0' value.
		if (is_null($suspicious_files))
		{
			$suspicious_files = 0;
		}
		
		$this->write_log("Getting backup info...");
		// Comprobamos el estado del backup
		$this->getBackupInfo();

		// Verificamos si el core est� actualizado (obviando la cach�)
		// Boot del componente
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		$component = $app->bootComponent('com_joomlaupdate');

		if (!$component instanceof MVCComponent) {
			$this->log_filename = "error.php";
			$message = "Component com_joomlaupdate no es MVCComponent.";
			$this->write_log($message,"ERROR");
			
			$this->data = "Component com_joomlaupdate no es MVCComponent.";
			$this->status = self::STATUS_ERROR;
			$this->cipher = self::CIPHER_RAW;
						
			$this->sendResponsePostPrefer();
		}
		
		// @phpstan-ignore-next-line
		$factory = $component->getMVCFactory();
		/** @var \Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel $updateModel */ 
		$updateModel = $factory->createModel('Update', 'Administrator', ['ignore_request' => true]); 
		
		$updateModel->refreshUpdates(true);
		$coreInformation = $updateModel->getUpdateInformation();
				
		// Si el plugin 'Update Batabase' est� instalado, comprobamos si est� actualizado
		if ($update_database_plugin_installed)
		{
			$this->update_database_plugin_needs_update = $this->checkforUpdate();
		}
		else
		{
			$this->update_database_plugin_needs_update = 0;
		}
		
		$this->write_log("Getting system info...");
		// A�adimos la informaci�n del sistema
		$this->getInfo();
		
		$this->write_log("Getting htaccess protection config...");
		// Obtenemos las opciones de protecci�n .htaccess
		$ConfigApplied = new ProtectionModel();
		$ConfigApplied = $ConfigApplied->GetConfigApplied();

		// Si el directorio de administraci�n est� protegido con contrase�a, marcamos la opci�n de protecci�n del backend como habilitada
		if (!$ConfigApplied['hide_backend_url'])
		{
			if (file_exists(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . '.htpasswd'))
			{
				$ConfigApplied['hide_backend_url'] = '1';
			}
		}

		// Si se ha seleccionado la opci�n de "backend protected using other options" ponemos "hide_backend_url" como enable porque esta opci�n marca si el backend est� habilitado

		if ($ConfigApplied['backend_protection_applied'])
		{
			$ConfigApplied['hide_backend_url'] = '1';
		}
		
		$this->write_log("Getting firewall config...");
		// Obtenemos los par�metros del Firewall
		$FirewallOptions = new BaseModel;
		$FirewallOptions = $FirewallOptions->getConfig();
				
		$this->write_log("Checking if kickstart exists...");
		// Chequeamos si existe el fichero kickstart
		$kickstart = $this->check_kickstart();

		$this->write_log("Getting 2FA status...");
		// Chequeamos si el segundo factor de autenticaci�n est� habilitado
		(int) $two_factor = $this->get_two_factor_status();
				
		$this->write_log("Getting info about outdated extensions...");
		// A�adimos la informaci�n sobre las extensiones no actualizadas. Esta opci�n no es necesaria cuando escogemos la opci�n 'System Info'
		if ($opcion)
		{
			$extension_updates = $this->getNotUpdatedExtensions();
			$outdated_extensions = json_decode($extension_updates, true);
			$sc_to_find = "Securitycheck Pro";
			$key_sc = array_search($sc_to_find, array_column($outdated_extensions, 2));

			if ($key_sc !== false)
			{
				$installed_version = $outdated_extensions[$key_sc][4];
				$hasUpdates = 1;
			}
		}

		// Si no hay backup establecemos la fecha actual para evitar un error en la bbdd al insertar el valor
		$is_valid_date = $this->verifyDate($this->backupinfo['latest']);

		if (!$is_valid_date)
		{
			$this->backupinfo['latest'] = "0000-00-00 00:00:00";
		}
		
		$this->write_log("Getting lock tables status...");
		// Chequeamos si las tablas est�n bloqueadas
		$tables_locked = $this->check_locked_tables();
		
		$this->write_log("Getting attacks stopped today...");
		// Informaci�n sobre el n�mero de logs del d�a
		try 
        {
            $query = "SELECT COUNT(*) from #__securitycheckpro_logs WHERE DATE(time) = CURDATE()";            
            $db->setQuery($query);
            $today_logs= $db->loadResult();
        } catch (\Throwable $e)
        {            
        }  
		
		$this->write_log("Getting info about updates...");
		// Informaci�n sobre las extensiones/core actualizadas
		try 
        {
			$query = 'SELECT storage_value FROM #__securitycheckpro_storage WHERE storage_key="installs_remote"';
			$db->setQuery($query);
			$db->execute();
			$updates_to_send = $db->loadResult();                 
        } catch (\Throwable $e)
        {            
        } 
		
		if ( ($today_logs > 0) || (!empty($updates_to_send)) ) {
			if ($today_logs > 0) {
				$remote_data['logs'] = $today_logs;
			}
			if (!empty($updates_to_send)){
				// Decodificamos updates_to_send, que ya est� codificado en json
				$updates_to_send_decoded = json_decode($updates_to_send, true);
				$remote_data['updates'] = $updates_to_send;
				// Borramos la informaci�n de la tabla "installs_remote"
				try 
				{
					$query = "DELETE FROM #__securitycheckpro_storage WHERE storage_key='installs_remote'";
					$db->setQuery($query);
					$db->execute();                
				} catch (\Throwable $e)
				{            
					$this->log_filename = "error.php";
					$message = "Error deleting info from installs_remote table. Error: " . $e->getMessage();
					$this->write_log($message,"ERROR");
				} 				
			}			
			$remote_data = json_encode($remote_data);
		} else {
			$remote_data = null;
		}
		
		$this->data = array(
			'vuln_extensions'        => $vuln_extensions,
			'logs_pending'    => $logs_pending,
			'files_with_incorrect_permissions'        => $files_with_incorrect_permissions,
			'last_check' => $last_check,
			'files_with_bad_integrity'        => $files_with_bad_integrity,
			'last_check_integrity' => $last_check_integrity,
			'installed_version'    => $installed_version,
			'hasUpdates'    => $hasUpdates,
			'coreinstalled'    => $coreInformation['installed'],
			'corelatest'    => $coreInformation['latest'],
			'last_check_malwarescan' => $last_check_malwarescan,
			'suspicious_files'        => $suspicious_files,
			'update_database_plugin_installed'    => (int) $update_database_plugin_installed,
			'update_database_plugin_version'    => $update_database_plugin_version,
			'update_database_plugin_last_check'    => $update_database_plugin_last_check,
			'update_database_plugin_needs_update'    => $this->update_database_plugin_needs_update,
			'backup_info_product'    => $this->backupinfo['product'],
			'backup_info_latest'    => $this->backupinfo['latest'],
			'backup_info_latest_status'    => $this->backupinfo['latest_status'],
			'backup_info_latest_type'    => $this->backupinfo['latest_type'],
			'php_version'    => $this->info['phpversion'],
			'database_version'    => $this->info['dbversion'],
			'web_server'    => $this->info['server'],
			'extension_updates'    => $extension_updates,
			'last_check_database_optimization'    => $last_check_database_optimization,
			'overall'    => 200,
			'twofactor_enabled'    => $two_factor,
			'backend_protection'    => $ConfigApplied['hide_backend_url'],
			'forbid_new_admins'        => $FirewallOptions['forbid_new_admins'],
			'kickstart_exists'    => $kickstart,
			'tables_blocked'    => $tables_locked,
			'remote_data'	=>	$remote_data
		);

		// Obtenemos el porcentaje para 'Overall security status'
		$overall = $this->getOverall($this->data);
		$this->data['overall'] = $overall;
				
		$this->write_log("GETSTATUS task finished");

	}

	/**
     * Chequea si la opci�n "Lock tables" est� habilitada
     *
	 * 	 
     * @return  bool
	 *     
     */
	function check_locked_tables()
	{
		$locked = false;

		try
		{
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = 'SELECT storage_value FROM #__securitycheckpro_storage WHERE storage_key="locked"';
			$db->setQuery($query);
			$db->execute();
			$locked = $db->loadResult();
		}
		catch (\Exception $e)
		{
			$this->log_filename = "error.php";
			$message = "Function check_locked_tables. " . $e->getMessage();
			$this->write_log($message,"ERROR");
			return false;
		}

		return $locked;
	}

	/**
     * Chequea si el fichero kickstart.php existe en la ra�z del sitio. Esto sucede cuando se restaura un sitio y se olvida (junto con alg�n backup) eliminarlo.
     *
	 * 	 
     * @return  bool
	 *     
     */
	function check_kickstart()
	{
		$found = false;
		$akeeba_kickstart_file = JPATH_ROOT . DIRECTORY_SEPARATOR . "kickstart.php";

		if (file_exists($akeeba_kickstart_file))
		{
			if (strpos(file_get_contents($akeeba_kickstart_file), "AKEEBA") !== false)
			{
				$found = true;
			}
		}
		return $found;
	}

	/**
     * Obtiene el estado del segundo factor de autenticaci�n de Joomla (Google y Yubikey)
     *
	 * @param   bool     $overall    Si la variable "overall" es false utilizamos el m�todo getTwoFactorMethods para obtener la informaci�n de los plugins; si es true no podemos usar ese m�todo ya
	 * que necesitamos que el usuario est� logado DEPRECATED
	 * 	 
     * @return  int
	 *     
     */
	function get_two_factor_status(bool $overall = false): int
	{
		// Ignoramos $overall en J5/J6: con consultas directas no hace falta sesi�n.
		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			// 1) �Hay plugins TOTP/YubiKey habilitados? (carpeta multifactorauth)
			//    Preferimos PluginHelper::isEnabled por claridad; si falla, caemos a SQL.
			$totpEnabled    = PluginHelper::isEnabled('multifactorauth', 'totp');
			$yubiKeyEnabled = PluginHelper::isEnabled('multifactorauth', 'yubikey');

			if (!$totpEnabled && !$yubiKeyEnabled) {
				// Fallback por si PluginHelper no refleja el estado (instalaciones raras):
				$q = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->quoteName('#__extensions'))
					->where($db->quoteName('type') . '=' . $db->quote('plugin'))
					->where($db->quoteName('folder') . '=' . $db->quote('multifactorauth'))
					->where($db->quoteName('enabled') . '=1')
					->where($db->quoteName('element') . ' IN (' . $db->quote('totp') . ',' . $db->quote('yubikey') . ')');
				$db->setQuery($q);
				$enabledPlugins = (int) $db->loadResult();

				if ($enabledPlugins === 0) {
					return 0;
				}
			}

			// 2) Obtener IDs de superusuarios
			$q = $db->getQuery(true)
				->select('DISTINCT ' . $db->quoteName('user_id'))
				->from($db->quoteName('#__user_usergroup_map'))
				->where($db->quoteName('group_id') . '=8');
			$db->setQuery($q);
			$superUserIds = array_map('intval', (array) $db->loadColumn());

			if (empty($superUserIds)) {
				// No hay superusuarios asignados: hay plugins, pero nadie lo puede tener configurado
				return 1;
			}

			// 3) �Alg�n superusuario tiene TOTP o YubiKey configurado?
			//    Consultamos #__user_mfa (tabla nueva de MFA en J4+).
			//    Hacemos IN de forma segura.
			$inList = implode(',', $superUserIds);

			$q = $db->getQuery(true)
				->select('1')
				->from($db->quoteName('#__user_mfa'))
				->where($db->quoteName('user_id') . ' IN (' . $inList . ')')
				->where($db->quoteName('method') . ' IN (' . $db->quote('totp') . ',' . $db->quote('yubikey') . ')')
				->setLimit(1);
			$db->setQuery($q);
			$hasAny = (int) $db->loadResult();

			return $hasAny ? 2 : 1;
		} catch (\Throwable $e) {			
			$this->write_log("get_two_factor_status: " . $e->getMessage(), "ERROR");
			return 0; // Conservador
		}
	}

	/**
     * Obtiene el porcentaje general de cada una de las barras de progreso
     *
	 * @param   array<string,mixed>     $info    El array con la informaci�n
	 * 	 
     * @return  int
	 *     
     */
	function getOverall(array $info): int
	{
		return OverallScoreHelper::score($info, 1);
	}

	/**
     * Funci�n que comprueba si existen extensiones vulnerables
     * 	 
     * @return  void
	 *     
     */
	private function checkVuln()
	{
		$this->write_log("Launching CHECKVULN task");
		
		$this->write_log("Getting models...");
		// Import Securitycheckpros model		
		$securitycheckpros_model = new SecuritycheckproModel();
		$update_model = new DatabaseupdatesModel();
				
		$this->write_log("Looking for updates...");
		// Comprobamos si existen nuevas actualizaciones
		$update_model->tarea_comprobacion();

		// Comprobamos el estado del plugin Update Database
		$update_database_plugin_installed = $update_model->PluginStatus(4);
		$update_database_plugin_version = $update_model->getDatabaseVersion();
		$update_database_plugin_last_check = $update_model->lastCheck();
		
		$this->write_log("Looking for vulnerable extensions...");
		// Hacemos una nueva comprobaci�n de extensiones vulnerables
		$securitycheckpros_model->chequear_vulnerabilidades();

		// Vulnerable components
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = 'SELECT COUNT(*) FROM #__securitycheckpro WHERE Vulnerable="Si"';
		$db->setQuery($query);
		$db->execute();
		$vuln_extensions = $db->loadResult();

		$this->data = [
			'vuln_extensions'        => $vuln_extensions,
			'update_database_plugin_installed'    => $update_database_plugin_installed,
			'update_database_plugin_version'    => $update_database_plugin_version,
			'update_database_plugin_last_check'    => $update_database_plugin_last_check
		];
		
		$this->write_log("CHECKVULN task finished");
	}

	/**
     * Funci�n que comprueba si existen logs por leer
     * 	 
     * @return  void
	 *     
     */
	private function checkLogs()
	{
		$this->write_log("Launching CHECKLOGS task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$cpanel_model = new CpanelModel();;
		
		$this->write_log("Checking unread logs...");
		// Check for unread logs
		(int) $logs_pending = $cpanel_model->LogsPending();

		$this->data = [
			'logs_pending'    => $logs_pending
		];
		
		$this->write_log("CHECKLOGS task finished");
	}

	/**
     * Funci�n que lanza un chequeo de permisos
     * 	 
     * @return  void
	 *     
     */
	private function checkPermissions()
	{
		$this->write_log("Launching CHECKPERMISSIONS task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$filemanager_model = new FilemanagerModel();
		
		$this->write_log("Launching permissions scan...");
		
		$filemanager_model->setCampoFilemanager('files_scanned', 0);
		$timestamp = $this->get_Joomla_timestamp();
		$filemanager_model->setCampoFilemanager('last_check', $timestamp);
		$filemanager_model->setCampoFilemanager('estado', 'IN_PROGRESS');
		$filemanager_model->scan("permissions");
		
		$this->write_log("Retrieving status...");
		
		// Get files with incorrect permissions from database
		$files_with_incorrect_permissions = $filemanager_model->loadStack("filemanager_resume", "files_with_incorrect_permissions");

		// If permissions task has not been launched, we set a '0' value.
		if (is_null($files_with_incorrect_permissions))
		{
			$files_with_incorrect_permissions = 0;
		}

		// FileManager last check
		$last_check = $filemanager_model->loadStack("filemanager_resume", "last_check");

		$this->data = [
			'files_with_incorrect_permissions'        => $files_with_incorrect_permissions,
			'last_check' => $last_check
		];
		
		$this->write_log("CHECKPERMISSIONS task finished");
	}

	/**
     * Funci�n que lanza un chequeo de integridad
     * 	 
     * @return  void
	 *     
     */
	private function checkIntegrity()
	{
		$this->write_log("Launching CHECKINTEGRITY task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$filemanager_model = new FilemanagerModel();
				
		$this->write_log("Launching integrity scan...");

		$filemanager_model->setCampoFilemanager('files_scanned_integrity', 0);
		$timestamp = $this->get_Joomla_timestamp();
		$filemanager_model->setCampoFilemanager('last_check_integrity', $timestamp);
		$filemanager_model->setCampoFilemanager('estado_integrity', 'IN_PROGRESS');
		$filemanager_model->scan("integrity");
		
		$this->write_log("Retrieving status...");

		// Get files with incorrect permissions from database
		$files_with_bad_integrity = $filemanager_model->loadStack("fileintegrity_resume", "files_with_bad_integrity");

		// If permissions task has not been launched, we set a '0' value.
		if (is_null($files_with_bad_integrity))
		{
			$files_with_bad_integrity = 0;
		}

		// FileIntegrity last check
		$last_check_integrity = $filemanager_model->loadStack("fileintegrity_resume", "last_check_integrity");

		$this->data = [
			'files_with_bad_integrity'        => $files_with_bad_integrity,
			'last_check_integrity' => $last_check_integrity
		];
		
		$this->write_log("CHECKINTEGRITY task finished");
	}

	/**
     * Borra los logs pertenecientes a intentos de acceso bloqueados
     * 	 
     * @return  void
	 *     
     */
	private function deleteBlocked()
	{
		$this->write_log("Launching DELETEBLOCKED task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$cpanel_model = new CpanelModel();
		
		// Vulnerable components
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = 'DELETE FROM #__securitycheckpro_logs';
		$db->setQuery($query);
		$db->execute();

		// Check for unread logs
		(int) $logs_pending = $cpanel_model->LogsPending();

		$this->data = [
			'logs_pending'    => $logs_pending
		];
		
		$this->write_log("DELETEBLOCKED task finished");
	}
		
	/**
     * Funci�n que actualiza el Core de Joomla a la �ltima versi�n disponible. Basado en /libraries/src/Console/UpdateCoreCommand.php
     * 	 
     * @return  array<int,mixed>
	 *     
     */
	private function UpdateCore()
	{
		$this->write_log("Updating CORE...");
		
		$old_version = JVERSION;
		$this->write_log("Old core version: " . $old_version);	
			
		// Cargamos el lenguaje del componente 'com_installer'
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		$lang = $app->getLanguage();
		$lang->load('com_installer', JPATH_ADMINISTRATOR);

		// Inicializamos la variable $result, que ser� un array con el resultado y el mensaje devuelto en el proceso
		$result = array();

		// Instanciamos el modelo
		// Boot del componente
		$component = $app->bootComponent('com_joomlaupdate');

		if (!$component instanceof MVCComponent) {
			throw new \RuntimeException('Component com_joomlaupdate no es MVCComponent');
		}

		$factory = $component->getMVCFactory();
		/** @var \Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel $model */
		$model = $factory->createModel('Update', 'Administrator', ['ignore_request' => true]);
		
		// Refrescamos la informaci�n de las actualizaciones ignorando la cach�
		$model->refreshUpdates(true);
		$this->write_log("Refreshing info...");

		// Extraemos la url de descarga
		$coreInformation = $model->getUpdateInformation();
		$this->write_log("Getting url to download the file...");		
								
		try
		{
			// Descargamos el archivo
			$file = $this->download_core($coreInformation['object']->downloadurl->_data);				
			
			// Extract the downloaded package file
			/** @var \Joomla\CMS\Application\CMSApplication $app */
			$app       = Factory::getApplication();
			$config   = $app->getConfig();
			$tmp_dest = $config->get('tmp_path');

			// Basado en /components/com_installer/src/Model/UpdateModel.php
			$package = InstallerHelper::unpack($tmp_dest . '/' . $file,true);
			
			if ( empty($package) ) {
				$this->log_filename = "error.php";
				$message = "Function UpdateCore. InstallerHelper.";
				$this->write_log($message,"ERROR");				
				$result[0][1] = $message;
				$result[0][0] = 2;
			} else {				
				// Uncompress and install the package
				$this->write_log("Uncompressing and installing the new version...");
				Folder::copy($package['extractdir'], JPATH_BASE, '', true);
				$install_result = $model->finaliseUpgrade();						
				if (!$install_result)
				{
					$msg = Text::_('COM_INSTALLER_MSG_UPDATE_ERROR');
					$result[0][1] = $msg;
					$result[0][0] = 2;
				}
				else
				{
					$result[0][1] = 'Core updated';
					$result[0][0] = 1;
					$this->write_log("CORE UPDATED successfully!");
					InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
					// Trigger event after joomla update.
					/** @var \Joomla\CMS\Application\CMSApplication $app */
					$app = Factory::getApplication();
					$app->triggerEvent('onJoomlaAfterUpdate',[$old_version]);					
				}				
			}			
		}
		catch (\Exception $e)
		{
			$this->log_filename = "error.php";
			$message = "Function UpdateCore. " . $e->getMessage();
			$this->write_log($message,"ERROR");
			$result[0][1] = $e->getMessage();
			$result[0][0] = 2;
		}
		
		// Devolvemos el resultado
		return $result;
	}

	/**
	 * Install an extension from either folder, url or upload.
	 *
	 * @param   string             $url    The url
	 *
	 * @return boolean result of install
	 *
	 * @since 1.5
	 */

	public function install($url)
	{
		$this->setState('action', 'install');

		// Set FTP credentials, if given.
		ClientHelper::setCredentialsFromRequest('ftp');
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app = Factory::getApplication();

		// Load installer plugins for assistance if required:
		PluginHelper::importPlugin('installer');
		
		/** @var array<string, mixed>|null $package */
		$package = null;

		// This event allows an input pre-treatment, a custom pre-packing or custom installation (e.g. from a JSON�description)
		$results = $app->triggerEvent('onInstallerBeforeInstallation', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}
		elseif (in_array(false, $results, true))
		{
			return false;
		}

		$installType = 'url';

		if ($package === null)
		{
			switch ($installType)
			{
				case 'folder':
					// Not implemented
					return false;
				
				case 'upload':
					//Not implemented
					return false;
				
				case 'url':
					$package = $this->getPackageFromUrl($url);
					break;

				default:
					$app->setUserState('com_installer.message', Text::_('COM_INSTALLER_NO_INSTALL_TYPE_FOUND'));
					return false;				
			}
		}

		// This event allows a custom installation of the package or a customization of the package:
		$results = $app->triggerEvent('onInstallerBeforeInstaller', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}
		elseif (in_array(false, $results, true))
		{
			if (in_array($installType, array('upload', 'url')))
			{
				//InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			}

			return false;
		}

		// Was the package unpacked?
		if (!$package || !$package['type'])
		{
			if (in_array($installType, array('upload', 'url')))
			{
				//InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			}

			$app->setUserState('com_installer.message', Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'));

			return false;
		}

		// Get an installer instance
		$installer = Installer::getInstance();

		// Install the package
		if (!$installer->install($package['dir']))
		{
			// There was an error installing the package
			$msg = Text::sprintf('COM_INSTALLER_INSTALL_ERROR', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result = false;
		}
		else
		{
			// Package installed sucessfully
			$msg = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result = true;
		}

		// This event allows a custom a post-flight:
		$app->triggerEvent('onInstallerAfterInstaller', array($this, &$package, $installer, &$result, &$msg));

		// Set some model state values
		$app->enqueueMessage($msg);
		$this->setState('name', $installer->get('name'));
		$this->setState('result', $result);
		$app->setUserState('com_installer.message', $installer->message);
		$app->setUserState('com_installer.extension_message', $installer->get('extension_message'));
		$app->setUserState('com_installer.redirect_url', $installer->get('redirect_url'));

		return $result;
	}	

	/**
     * Funci�n que lanza un chequeo en busca de malware
     *
     * @return  void
     *     
     */
	private function checkMalware()
	{
		$this->write_log("Launching CHECKMALWARE task");
		
		$this->write_log("Getting models...");
		
		// Import Securitycheckpros model
		$filemanager_model = new FilemanagerModel();
		
		$this->write_log("Launching malware scan...");
		
		$filemanager_model->setCampoFilemanager('files_scanned_malwarescan', 0);
		$timestamp = $this->get_Joomla_timestamp();
		$filemanager_model->setCampoFilemanager('last_check_malwarescan', $timestamp);
		$filemanager_model->setCampoFilemanager('estado_malwarescan', 'IN_PROGRESS');
		$filemanager_model->scan("malwarescan");
		
		$this->write_log("Retrieving info...");

		// Get suspicious files
		$suspicious_files = $filemanager_model->loadStack("malwarescan_resume", "suspicious_files");

		// If malwarescan task has not been launched, we set a '0' value.
		if (is_null($suspicious_files))
		{
			$suspicious_files = 0;
		}

		// Malwarescan last check
		$last_check_malwarescan = $filemanager_model->loadStack("malwarescan_resume", "last_check_malwarescan");

		$this->data = array(
			'suspicious_files'        => $suspicious_files,
			'last_check_malwarescan' => $last_check_malwarescan
		);
		
		$this->write_log("CHECKMALWARE task finished");
	}

	/**
     * Funci�n que obtiene informaci�n del estado del backup
     *
     * @return  void
     *     
     */
	private function getBackupInfo()
	{

		// Instanciamos la consulta
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		$joomla_version = "4";
		$query = "SELECT COUNT(*) FROM #__extensions WHERE element='com_akeebabackup'";
				
		try {
			// Consultamos si Akeeba Backup est� instalado
			$db->setQuery($query);
			$db->execute();
			$akeeba_installed = $db->loadResult();			
		} catch (\Exception $e)
        {    			
            $akeeba_installed = 0;
        }     
		

		if ($akeeba_installed == 1)
		{
			$this->backupinfo['product'] = 'Akeeba Backup';
			$this->AkeebaBackupInfo($joomla_version);
		}
	}

	/**
     * Funci�n que obtiene informaci�n del estado del �ltimo backup creado por Akeeba Backup
     *
	 * @param   string             $joomla_version    The joomla version
	 *
     * @return  void
     *     
     */
	private function AkeebaBackupInfo($joomla_version)
	{
		if ($joomla_version == "3") {
			$akeeba_database = "#__ak_stats";
		} else {
			$akeeba_database = "#__akeebabackup_backups";
		}
		
		// Instanciamos la consulta
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		try{
			$query = $db->getQuery(true)
				->select('MAX(' . $db->qn('id') . ')')
				->from($db->qn('' . $akeeba_database . ''))
				->where($db->qn('origin') . ' != ' . $db->q('restorepoint'));
			$db->setQuery($query);
			$id = $db->loadResult();
		} catch (\Exception $e)
		{
			$this->write_log("Error trying to get Akeeba database id: " . $e->getMessage(),"ERROR");
		}
		
		$backup_statistics = [];
		// Hay al menos un backup creado
		if (!empty($id))
		{
			try{
				$query = $db->getQuery(true)
					->select(array('*'))
					->from($db->quoteName('' . $akeeba_database .''))
					->where('id = ' . $id);
				$db->setQuery($query);
				$backup_statistics = $db->loadAssocList();
			} catch (\Exception $e)
			{
				$this->write_log("Error trying to get Akeeba backup statistics: " . $e->getMessage(),"ERROR");
			}

			// Almacenamos el resultado
			$this->backupinfo['latest'] = $backup_statistics[0]['backupend'];
			$this->backupinfo['latest_status'] = $backup_statistics[0]['status'];
			$this->backupinfo['latest_type'] = $backup_statistics[0]['type'];
		}		
	}	

	/**
     * Funci�n que indica si el plugin 'Update Database' est� actualizado
	 *
     * @return  int
     *     
     */
	private function checkforUpdate()
	{

		// Inicializmaos las variables
		$needs_update = 0;

		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Extraemos el id de la extension..
		$query = $db->getQuery(true)
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('name') . ' = ' . $db->quote('System - Securitycheck Pro Update Database'));
		$db->setQuery($query);
		(int) $extension_id = $db->loadResult();

		// ... y hacemos una consulta a la tabla 'updates' para ver si el 'extension_id' figura como actualizable
		if (!empty($extension_id))
		{
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__updates'))
				->where($db->quoteName('extension_id') . ' = ' . (int) $extension_id);
			$db->setQuery($query);
			$result = $db->loadResult();

			if ($result == '1')
			{
				$needs_update = 1;
			}
		}

		// Devolvemos el resultado
		return $needs_update;
	}

	/**
     * Funci�n para actualizar los componentes. Extra�da del core de Joomla (administrator/components/com_installer/models/update.php |
	 * administrator\components\com_installer\src\Model\UpdateModel.php)
	 *
	 * @param   \Joomla\CMS\Updater\Update             $update    The update info
	 * @param   string|bool|array<string,mixed>             $dlid    The downoload id
	 *
     * @return  int|array<mixed,mixed>
     *     
     */
	private function install_update($update,$dlid=false)
	{
		$this->write_log("Installing update...");
								
		/* Cargamos el lenguaje del componente 'com_installer' */
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		$lang = $app->getLanguage();
		$lang->load('com_installer',JPATH_ADMINISTRATOR);
									
		// Inicializamos la variable $update_result, que ser� un array con el resultado y el mensaje devuelto en el proceso
		$update_result = array();
		$extension_name = '';
		$p_file = false;
		$install_result = false;
						
		if (isset($update->get('downloadurl')->_data)) {			
			$url = trim($update->downloadurl->_data);
			$extension_name = $update->get('name')->_data;
					
			if (!empty($dlid))
			{
				if ( is_array($dlid) ) {
					$this->write_log("Dlid is an array. Extracting values...");
					foreach($dlid as $key => $value) {
						$url .= (strpos($url, '?') === false) ? '?' : '&amp;';
						$url .= $key . '=' . $value;
				
				$this->write_log("Url: " . $url);
					}
				} else {
					$url .= (strpos($url, '?') === false) ? '?' : '&amp;';
					$url .= 'dlid=' . $dlid;
					$this->write_log("Url: " . $url);
				}
								
			}
			
				
		} else {
			$this->write_log(Text::_('COM_INSTALLER_INVALID_EXTENSION_UPDATE'));
			$update_result[0][1] = $extension_name . ' ' .  Text::_('COM_INSTALLER_INVALID_EXTENSION_UPDATE');
			$update_result[0][0] = 2;
			return $update_result;
		}
		
		try{			
			$p_file = InstallerHelper::downloadPackage($url);
		} catch (\Exception $e)
		{
			$this->write_log("Error downloading package: " . $e->getMessage(),"ERROR");
		}
		
		// Was the package downloaded?
		if (!$p_file)
		{
			$this->write_log(Text::sprintf('COM_INSTALLER_PACKAGE_DOWNLOAD_FAILED', $url),"ERROR");
			$update_result[0][1] = $extension_name . ' ' . Text::sprintf('COM_INSTALLER_PACKAGE_DOWNLOAD_FAILED', $url);
			$update_result[0][0] = 2;
						
			return $update_result;
		} 
						
		$config        = $app->getConfig();
		$tmp_dest    = $config->get('tmp_path');
		
		// Unpack the downloaded package file
		$package    = InstallerHelper::unpack($tmp_dest . '/' . $p_file);
		
		// Get an installer instance
		$installer    = Installer::getInstance();
		$update->set('type', $package['type']);
		
		// TODO: Checksum validation
								
		try {
			$install_result = $installer->update($package['dir']);
			
		} catch (\Exception $e)
		{
			$this->write_log("Error installing package: " . $e->getMessage(),"ERROR");
		}
						
		// Install the package
		if (!$install_result)
		{
			// There was an error updating the package
			if (is_null($package['type']))
			{
				$package['type'] = "COMPONENT";
			}
			
			$msg = $extension_name . ' ' . Text::sprintf('COM_INSTALLER_MSG_UPDATE_ERROR', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$this->write_log($msg,"ERROR");
			$update_result = $msg;
			$update_result = 2;
			
			return $update_result;
		}
		else
		{
			// Package updated successfully
			if (is_null($package['type']))
			{
				$package['type'] = "COMPONENT";
			}

			$msg = $extension_name . ' ' . Text::sprintf('COM_INSTALLER_MSG_UPDATE_SUCCESS', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$this->write_log($msg);
			$update_result[0][1] = $msg;
			$update_result[0][0] = 1;			
		}
		
		// Quick change
		if (array_key_exists('packagefile', $package))
		{
			// Cleanup the install files
			if (!is_file($package['packagefile']))
			{
				$config = $app->getConfig();
				$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
			}

			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
		}
		
		return $update_result;
	}

	/**
     * Funci�n que obtiene informaci�n del sistema (extra�da del core)
	 *
     * @return  void
     *     
     */
	private function getInfo()
	{
		if (empty($this->info))
		{
			$this->info = array();
			$version = new \Joomla\CMS\Version();
			$db = Factory::getContainer()->get(DatabaseInterface::class);

			if (isset($_SERVER['SERVER_SOFTWARE']))
			{
				$sf = $_SERVER['SERVER_SOFTWARE'];
			}
			else
			{
				$sf = getenv('SERVER_SOFTWARE');
			}

			$this->info['php']            = php_uname();
			$this->info['dbversion']    = $db->getVersion();
			$this->info['dbcollation']    = $db->getCollation();
			$this->info['phpversion']    = phpversion();
			$this->info['server']        = $sf;
			$this->info['sapi_name']    = php_sapi_name();
			$this->info['version']        = $version->getLongVersion();
			$this->info['platform']        = "Not defined";
			$this->info['useragent']    = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
		}
	}

	/**
     * Funci�n que devuelve informaci�n sobre las extensiones no actualizadas
	 *
     * @return  string
     *     
     */
	private function getNotUpdatedExtensions()
	{

		// Habilitamos los sitios deshabilitados
		//$enable = $this->enableSites();

		// Purgamos la cach� y lanzamos la tarea
		$find = $this->findUpdates();
		
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Grab updates ignoring new installs (extraido de \administrator\components\com_installer\models\update.php)
		$query = $db->getQuery(true)
			->select('u.update_id,u.extension_id,u.name,u.type,u.version')
			->select($db->quoteName('e.manifest_cache'))
			->from($db->quoteName('#__updates', 'u'))
			->join('LEFT', $db->quoteName('#__extensions', 'e') . ' ON ' . $db->quoteName('e.extension_id') . ' = ' . $db->quoteName('u.extension_id'))
			->where($db->quoteName('u.extension_id') . ' != ' . $db->quote(0));
		$db->setQuery($query);
		$result = $db->loadObjectList();

		// Creamos un nuevo array que contendr� arrays con a informaci�n requerida
		$extensions = array();

		foreach ($result as $i => $item)
		{
			$value = array();
			$manifest        = json_decode($item->manifest_cache);
			$current_version = isset($manifest->version) ? $manifest->version : Text::_('JLIB_UNKNOWN');
			$value[0] = $item->update_id;
			$value[1] = $item->extension_id;
			$value[2] = $item->name;
			$value[3] = $item->type;
			$value[4] = $item->version;
			$value[5] = $current_version;
			array_push($extensions, $value);
		}

		// Devolvemos el resultado en formato JSON
		return json_encode($extensions);

	}

	/**
     * Finds updates for an extension.
     *
     * @param   int  $eid               Extension identifier to look for
     * @param   int  $cacheTimeout      Cache timeout
     * @param   int  $minimumStability  Minimum stability for updates {@see Updater} (0=dev, 1=alpha, 2=beta, 3=rc, 4=stable)
     *
     * @return  boolean Result
     * original en /components/com_installer/src/Model/UpdateModel.php
     */
    public function findUpdates($eid = 0, $cacheTimeout = 0, $minimumStability = Updater::STABILITY_STABLE)
    {
		try{
			Updater::getInstance()->findUpdates($eid, $cacheTimeout, $minimumStability);
		} catch (\Throwable $e) {  			            
        }
       
        return true;
    }

	/**
	* Truncates de updates tablef
	*
	* @return void
	*
	* Original en /administrator/components/com_installer/models/update.php
	*/
	public function purge()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Note: TRUNCATE is a DDL operation
		// This may or may not mean depending on your database
		$db->setQuery('TRUNCATE TABLE #__updates');

		if ($db->execute())
		{
			// Reset the last update check timestamp
			$query = $db->getQuery(true)
				->update($db->quoteName('#__update_sites'))
				->set($db->quoteName('last_check_timestamp') . ' = ' . $db->quote(0));
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	* Enables any disabled rows in #__update_sites table
	*
	* @return void
	*
	* Original en /administrator/components/com_installer/models/update.php
	*/
	public function enableSites()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->update('#__update_sites')
			->set('enabled = 1')
			->where('enabled = 0');
		$db->setQuery($query);
		$db->execute();
	}

	/**
     * Funci�n que busca si una extensi�n pasada como argumento utiliza una versi�n de pago
	 *
	 * @param   string|int             			$extension_id    	The id ot the extension
	 * @param   string             				$extension_name    	The name of the extension
	 * @param   \Joomla\CMS\Updater\Update       $update    			The update object
	 *
     * @return  void
     *     
     */
	private function LookForPro($extension_id,$extension_name,$update) {
				
		// Inicializamos las variables
		$dlid = '';
		
		// Seg�n el campo buscamos el campo 'dlid'
		switch($extension_name)
		{
			case "pkg_akeeba":
				$params = ComponentHelper::getParams('com_akeeba');
				$dlid   = $params->get('update_dlid', '');
				break;
			case "pkg_admintools":
				$params = ComponentHelper::getParams('com_admintools');
				$dlid = $params->get('downloadid','');				
				break;
			case "com_rstbox":
				$plugin = PluginHelper::getPlugin('system', 'nrframework');
				if (!$plugin->isEmpty()) {
					$params = new Registry($plugin->params);
					$dlid = $params->get('key','');
				}
				break;
			case "com_jch_optimize":
				$plugin = PluginHelper::getPlugin('system', 'jch_optimize');
							
				if (!$plugin->isEmpty()) {				
					$params = new Registry($plugin->params);
					$dlid = $params->get('pro_downloadid','');
				}
				break;
			// Version 7 of Jch optimize
			case "pkg_jchoptimize":
				$params = ComponentHelper::getParams('com_jchoptimize');							
				$dlid = $params->get('pro_downloadid','');				
				break;			
			case "com_sppagebuilder":
				$params = ComponentHelper::getParams('com_sppagebuilder');							
				$dlid = array();
				$dlid['joomshaper_email'] = $params->get('joomshaper_email','');
				$dlid['joomshaper_license_key'] = $params->get('joomshaper_license_key','');
				break;
		}		
				
		if (!empty($dlid))
		{
			$msg = "Found Pro version of " . $extension_name . " with a valid dlid.";
			$this->write_log($msg);
			$update_result = $this->install_update($update,$dlid);
			// Guardamos el id de la extensi�n junto con el resultado
			array_push($this->array_result, array($extension_id,$extension_name,$update_result));
		} else {
			$msg = "Found Pro version of " . $extension_name . " but not a valid dlid. Is the extension/plugin enabled and have a valid download id?";
			$this->write_log($msg);
			$update_result = array();
			$update_result[0][1] = $msg;
			$update_result[0][0] = 2;
			// Guardamos el id de la extensi�n junto con el resultado
			array_push($this->array_result, array($extension_id,$extension_name,$update_result));			
		}	
	}	

	/**
     * Funci�n que actualiza un array de extensiones (en formato json) pasado como argumento
	 *
	 * @param   array<int>             $extension_id_array    The array with the ids of the extensions to update
	 *
     * @return  void
     *     
     */
	private function UpdateExtension($extension_id_array)
	{
		$this->write_log("Launching UPDATEEXTENSIONS task");
				
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		
		$extension_data = '';
		
		// Si las tablas est�n bloqueadas abortamos la instalaci�n
		$locked_tables = $this->check_locked_tables();

		if ($locked_tables)
		{
			$msg = Text::_('COM_SECURITYCHECKPRO_LOCKED_MESSAGE');

			array_push($this->array_result, array($msg,$msg));

			// Devolvemos el resultado
			$this->data = [
				'update_result'        => $this->array_result
			];
		}
		else
		{
			// Para cada extensi�n, realizamos su actualizaci�n
			foreach ($extension_id_array as $extension_id)
			{
				// Extraemos los datos la extensi�n, que contendr�n la informaci�n de actualizaci�n
				try{
					$query = $db->getQuery(true)
						->select($db->quoteName(['name', 'detailsurl', 'element', 'extra_query']))
						->from($db->quoteName('#__updates'))
						->where($db->quoteName('extension_id') . ' = ' . (int) $extension_id);
					$db->setQuery($query);
					$extension_data = $db->loadAssoc();
				} catch (\Exception $e)
				{

				}			
																		
				if ( is_array($extension_data) ) {					
					$extension_name = $extension_data['name'];
					$detailsurl = $extension_data['detailsurl'];
					$extension_element = $extension_data['element'];
					$extra_query = $extension_data['extra_query'];					
									
					if (strtolower($extension_element) == "joomla")
					{						
						// Core de Joomla. Lo tratamos de forma diferente.
						$result_core = $this->UpdateCore();
						array_push($this->array_result, array($extension_id,'Core',$result_core));
					}else
					{	
						// Instanciamos el objeto Update y cargamos los detalles de la actualizaci�n
						$update = new Update;
						$update->loadFromXML($detailsurl);					

						// Le pasamos a la funci�n de actualizaci�n el objeto con los detalles de la actualizaci�n
						if (!empty($extra_query)) {
							// Quitamos el texto "dlid="
							$extra_query = str_replace("dlid=", "",$extra_query);
							$update_result = $this->install_update($update,$extra_query);
						} else {
							$update_result = $this->install_update($update);
						}
						
						// Update failed
						if ( (!$update_result) || ($update_result[0][0] == 2) )
						{
							$pro_versions_to_look_for = array('pkg_akeeba','pkg_admintools','com_rstbox','com_jch_optimize','pkg_jchoptimize','com_sppagebuilder');
							
							if (in_array($extension_element, $pro_versions_to_look_for)) {
								// Se ha producido un error y la extensi�n puede ser de pago. Intentamos actualizarla buscando su dlid
								$this->LookForPro($extension_id,$extension_element,$update);
							}												
						}
						else
						{
							// Guardamos el id de la extensi�n junto con el resultado
							array_push($this->array_result, array($extension_id,$extension_name,$update_result));
						}
					}
				} else {
					// Guardamos el id de la extensi�n junto con el resultado
					array_push($this->array_result, array($extension_id,"","Error retrieving extension data"));
				}				
			}
			
			// Devolvemos el resultado
			$this->data = [
				'update_result'        => $this->array_result
			];
		}

	}

	/**
     * Funci�n que realiza una copia de seguridad usando Akeeba y su funci�n de copias de seguridad v�a frontend. La clave usada se pasa como argumento
	 *
	 * @param   string             $data    The key of Akeeba
	 *
     * @return  void
     *     
     */
	private function Backup($data)
	{
		$this->write_log("Launching BACKUP task");
		
		// URI del sitio
		$uri = Uri::root();
		
		$this->write_log("Decrypting Akeeba public key...");

		$response = $this->decrypt($data, $this->password);

		if (strpos($response, 'Internal error') === 0) {
			$this->write_log("Decryption failed for backup task");
			$this->data = ['result' => false, 'msg' => 'Decryption failed'];
			return;
		}

		$response = json_decode($response, true);

		if (!is_array($response) || !isset($response['frontend_key']) || !isset($response['akeeba_profile'])) {
			$this->write_log("Invalid payload structure for backup task");
			$this->data = ['result' => false, 'msg' => 'Invalid payload'];
			return;
		}

		$akeeba_key = $response['frontend_key'];
		$akeeba_profile = (int) $response['akeeba_profile'];
		
		// Componente (com_akeeba para J3 y com_akeebackup para J4)
		$akeeba_component = "com_akeebabackup";
		
		$this->write_log("Launching curl: " . $uri . "?option=" . $akeeba_component . "&view=backup&key=removed_for_security&profile=" . $akeeba_profile);
		
		// Inicializamos la tarea
		$ch = curl_init($uri . "?option=" . $akeeba_component . "&view=backup&key=" . $akeeba_key . "&profile=" . $akeeba_profile);
		
		// Configuraci�n extra�da de https://www.akeebabackup.com/documentation/akeeba-backup-documentation/automating-your-backup.html
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);

		$response = curl_exec($ch);
		
		$this->write_log("Akeeba response: " . $response);

		// Devolvemos el resultado
		$this->data = [
			'backup'        => $response
		];
	}

	/**
     * Funci�n que instala una extensi�n desde una url. La url se pasa como argumento
	 *
	 * @param   string             $data    The path to the file
	 *
     * @return  void
     *     
     */
	private function Upload_install($data)
	{
		$this->write_log("Launching UPLOADINSTALL task");
		
		// Inicialiamos las variables
		$result = true;
		$enqueued_messages = "";

		// Cargamos el lenguaje del componente 'com_installer'
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		$lang = $app->getLanguage();
		$lang->load('com_installer', JPATH_ADMINISTRATOR);
		
		$this->write_log("Decrypting data...");

		// Desencriptamos los datos recibidos, que vendran como un array (vease data[0]) y en formato json
		$response = $this->decrypt($data[0], $this->password);

		// Validate decryption result
		if (strpos($response, 'Internal error') === 0) {
			$this->write_log("Decryption failed");
			$this->data = ['result' => false, 'msg' => 'Decryption failed'];
			return;
		}

		$response = json_decode($response, true);

		// Validate JSON structure
		if (!is_array($response) || !isset($response['path_to_file'])) {
			$this->write_log("Invalid payload structure");
			$this->data = ['result' => false, 'msg' => 'Invalid payload'];
			return;
		}

		// Url del paquete a instalar
		$url = $response['path_to_file'];

		// Validate URL format and protocol (only allow http/https)
		if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $url)) {
			$this->write_log("Invalid URL rejected: " . $url);
			$this->data = ['result' => false, 'msg' => 'Invalid URL'];
			return;
		}

		// Security: installation file must be hosted on the known Control Center
		if ($this->storedCcUrl === '') {
			$this->write_log('Upload_install: ALERT - control_center_url not set. Run a site refresh first.', 'WARNING');
			$this->data = ['result' => false, 'msg' => 'Security alert: Control Center identity not established. Run a site refresh task first.'];
			return;
		}
		$ccHost   = strtolower((string) parse_url($this->storedCcUrl, PHP_URL_HOST));
		$fileHost = strtolower((string) parse_url($url, PHP_URL_HOST));
		if ($ccHost === '' || $fileHost === '' || $ccHost !== $fileHost) {
			$this->write_log('Upload_install: SECURITY ALERT - file host [' . $fileHost . '] does not match Control Center host [' . $ccHost . ']. REJECTED.', 'ERROR');
			$this->data = ['result' => false, 'msg' => 'Security alert: installation file must be hosted on the Control Center.'];
			$this->status = self::STATUS_NOT_ALLOWED;
			return;
		}
		$this->write_log("Upload_install: file host [" . $fileHost . "] matches Control Center - OK");

				$this->write_log("Url: " . $url);

		$package = null;

		// Si las tablas estan bloqueadas abortamos la instalacion
		$locked_tables = $this->check_locked_tables();

		if ($locked_tables)
		{
			$this->write_log("Tables are blocked. Can't install the extension.");
			$msg = Text::_('COM_SECURITYCHECKPRO_LOCKED_MESSAGE');
			$result = false;
		}
		else
		{
			// Extraemos el paquete desde la url pasada
			$package = $this->getPackageFromUrl($url);

			// Was the package unpacked?
			if (!$package || !$package['type'])
			{				
				$msg = Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE');
				$this->write_log($msg);
			}

			// Get an installer instance
			$installer = Installer::getInstance();

			// Install the package
			if (!$installer->install($package['dir']))
			{
				// There was an error installing the package
				$msg = Text::sprintf('COM_INSTALLER_INSTALL_ERROR', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
				$this->write_log($msg);
				$result = false;				
			}
			else
			{
				// Package installed sucessfully
				$msg = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS', Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
				$this->write_log($msg);
			}

			// Recogemos los mensajes encolados para mostrar m�s informaci�n
			$enqueued_messages = $app->getMessageQueue();
		}
		
		
		// Devolvemos el resultado
		$this->data = [
			'upload_install'        => $result,
			'message'    => $msg,
			'enqueued_messages'    => $enqueued_messages
		];
	}

	/**
	* Install an extension from a URL
	*
	* @param   string             $url    The url of the package
	*
	* @return array<string,mixed>|bool
	*/
	protected function getPackageFromUrl($url)
	{
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();

		// Did you give us a URL?
		if (!$url)
		{
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), 'warning');
			return false;
		}

		// Handle updater XML file case:
		if (preg_match('/\.xml\s*$/', $url))
		{
			$update = new Update;
			$update->loadFromXML($url);
			$package_url = trim($update->get('downloadurl', false)->_data);

			if ($package_url)
			{
				$url = $package_url;
			}

			unset($update);
		}

		// Download the package at the URL given
		$p_file = InstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL'), 'warning');
			return false;
		}

		$config   = $app->getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file
		$package = InstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		return $package;
	}

	/**
	* Downloads the update package to the site.
	*
	* @param   string             $packageURL    The url of the package
	*
	* @return boolean|string False on failure, basename of the file in any other case.
	*
	*/
	public function download_core($packageURL)
	{
		$basename = basename($packageURL);
		
		// Find the path to the temp directory and the local package.
		/** @var \Joomla\CMS\Application\CMSApplication $app */
		$app       = Factory::getApplication();
		$config = $app->getConfig();
		$tempdir = $config->get('tmp_path');
		$target = $tempdir . '/' . $basename;

		// Do we have a cached file?
		$exists = file_exists($target);

		if (!$exists)
		{
			// Not there, let's fetch it.
			return $this->downloadPackage($packageURL, $target);
		}
		else
		{
			// Is it a 0-byte file? If so, re-download please.
			$filesize = @filesize($target);

			if (empty($filesize))
			{
				return $this->downloadPackage($packageURL, $target);
			}

			// Yes, it's there, skip downloading.
			return $basename;
		}
	}

	/**
	 * Downloads a package file to a specific directory
	 *
	 * @param   string $url    The URL to download from
	 * @param   string $target The directory to store the file
	 *
	 * @return string|bool
	 *
	 */
	protected function downloadPackage($url, $target)
	{	
				
		// Make sure the target does not exist.
		if (file_exists($target)) {
			File::delete($target);
		}
		
		// Download the package
		try
		{
			$result = HttpFactory::getHttp([], ['curl', 'stream'])->get($url);				
		}
		catch (\RuntimeException $e)
		{			
			return false;
		}
		
		$statusCode = $result->getStatusCode();

		if ($statusCode !== 200 && $statusCode !== 310) {
			return false;
		}

		// Fix Indirect Modification of Overloaded Property
        $body = $result->getBody();

        // Write the file to disk
        File::write($target, $body);

		return basename($target);
	}		

	/**
     * Funci�n que devuelve informaci�n sobre ips a a�adir y ataques detenidos para el plugin "Connect"
     *
     * @param   string             $url    The url to send the reply
     *
     * @return  void
     *     
     */
	public function Connect($url=null)
	{
		$cpanel_model = new CpanelModel;

		$attacks_today = $cpanel_model->LogsByDate('today');
		$attacks_yesterday = $cpanel_model->LogsByDate('yesterday');
		$attacks_last_7_days = $cpanel_model->LogsByDate('last_7_days');
		$attacks_last_month = $cpanel_model->LogsByDate('last_month');
		$attacks_this_month = $cpanel_model->LogsByDate('this_month');
		$attacks_last_year = $cpanel_model->LogsByDate('last_year');
		$attacks_this_year = $cpanel_model->LogsByDate('this_year');

		$attacks = [
			'today'    => $attacks_today,
			'yesterday'        => $attacks_yesterday,
			'last_7_days'        => $attacks_last_7_days,
			'this_month'        => $attacks_this_month,
			'last_month'        => $attacks_last_month,
			'this_year'        => $attacks_this_year,
			'last_year'        => $attacks_last_year
		];

		// Ruta al fichero de informaci�n
		$file_path = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_securitycheckpro' . DIRECTORY_SEPARATOR . 'scans' . DIRECTORY_SEPARATOR . 'cc_info.php';

		// Hay informaci�n que consumir
		if (file_exists($file_path))
		{
			$str = (string) file_get_contents($file_path);

			// Eliminamos la parte del fichero que evita su lectura al acceder directamente
			$ips = str_replace("#<?php die('Forbidden.'); ?>", '', $str);

			// Una vez extraida la informaci�n eliminamos el fichero
			unlink($file_path);
		}
		else
		{
			$ips = null;
		}
		
		$this->data = [
			'ips'        => $ips,
			'attacks'    => $attacks
		];
		
		if (!empty($url)) {
			$this->sendResponsePostPrefer($url);
		}		
		
	}
	
	/**
     * Funci�n que a�ade una IP a la lista negra din�mica
     *
     * @param   string             $attack_ip    The IP address to add to the list
     *
     * @return  void|string
     *     
     */
	function actualizar_lista_dinamica($attack_ip)
	{

		// Creamos el nuevo objeto query
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);

		// Chequeamos si la IP tiene un formato v�lido
		$ip_valid = filter_var($attack_ip, FILTER_VALIDATE_IP);

		// Validamos si el valor devuelto es una direccion IP valida
		if ((!empty($attack_ip)) && ($ip_valid))
		{
			try
			{
				$query = $db->getQuery(true)
					->insert($db->quoteName('#__securitycheckpro_dynamic_blacklist'))
					->columns($db->quoteName(['ip', 'timeattempt']))
					->values($db->quote($attack_ip) . ', NOW()');
				$query .= ' ON DUPLICATE KEY UPDATE ' . $db->quoteName('timeattempt') . ' = NOW(), ' . $db->quoteName('counter') . ' = ' . $db->quoteName('counter') . ' + 1';

				$db->setQuery($query);
				$result = $db->execute();
			}
			catch (\Exception $e)
			{
			}
		}
		else
		{
			return Text::_('COM_SECURITYCHECKPRO_INVALID_FORMAT');			
		}
	}

	/**
     * Funci�n que a�ade ips a la listas pasados por el plugin "Connect"
     *
     * @param   string             $data    The data to add
     *
     * @return  void
     *     
     */
	private function UpdateConnect($data)
	{
		$response = $this->decrypt($data, $this->password);
		if (strpos($response, 'Internal error') === 0) {
			$this->write_log("Decryption failed for UpdateConnect");
			$this->data = ['result' => false]; return;
		}
		$ips_passed = json_decode($response, true);

		$message = "";

		$firewall_config_model = new FirewallconfigModel();

		try {
			if ( is_array($ips_passed) )
			{
				if ( array_key_exists('whitelist', $ips_passed) ) {
					if (count($ips_passed['whitelist']))
					{
						$message .= Text::_('COM_SECURITYCHECKPRO_WHITELIST') . " ";
					}

					foreach ($ips_passed['whitelist'] as $whitelist)
					{
						$returned_message = $firewall_config_model->manage_list('whitelist', 'add', $whitelist, true, true);

						if (!empty($returned_message))
						{
							$message .= $whitelist . ": " . $returned_message . " ";
						}
						else
						{
							$message .= $whitelist . ": OK ";
						}
					}
				}
				
				
				if ( array_key_exists('blacklist', $ips_passed) ) {
					if (count($ips_passed['blacklist']))
					{
						$message .= Text::_('COM_SECURITYCHECKPRO_BLACKLIST') . " ";
					}

					foreach ($ips_passed['blacklist'] as $blacklist)
					{
						$returned_message = $firewall_config_model->manage_list('blacklist', 'add', $blacklist, true, true);

						if (!empty($returned_message))
						{
							$message .= $blacklist . ": " . $returned_message . " ";
						}
						else
						{
							$message .= $blacklist . ": OK ";
						}
					}
				}
				
				if ( array_key_exists('dynamic_blacklist', $ips_passed) ) {
					if (count($ips_passed['dynamic_blacklist']))
					{
						$message .= Text::_('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST') . " ";
					}

					foreach ($ips_passed['dynamic_blacklist'] as $dynamic_blacklist)
					{
						$returned_message = $this->actualizar_lista_dinamica($dynamic_blacklist);

						if (!empty($returned_message))
						{
							$message .= $dynamic_blacklist . ": " . $returned_message . " ";
						}
						else
						{
							$message .= $dynamic_blacklist . ": OK ";
						}
					}
				}
			}
			
		} catch (\Exception $e) {					
			$message = $e->getMessage();
		} 
		
		// Devolvemos el resultado
		$this->data = [
			'UpdateConnect'        => $message
		];
	}		

	/**
     * Funci�n para desbloquear las tablas (Lock tables feature)
     *
     *
     * @return  void
     *     
     */
	private function unlocktables()
	{		
		$this->write_log("Launching UNLOCKTABLES task");
		
		$cpanel_model = new CpanelModel();

		$cpanel_model->unlockAll();

		$this->data = [
			'tables_blocked'        => 0
		];
		$this->write_log("UNLOCKTABLES task finished");

	}

	/**
     * Funci�n para bloquear las tablas (Lock tables feature)
     *
     *
     * @return  void
     *     
     */
	private function locktables()
	{
		$this->write_log("Launching LOCKTABLES task");
		
		$cpanel_model = new CpanelModel();

		$cpanel_model->lockSelectedTables();

		$this->data = [
			'tables_blocked'        => 1
		];
		
		$this->write_log("LOCKTABLES task finished");

	}
	
	/**
     * Funci�n para formatear un entero en unidades de almacenamiento
     *
	 * @param   int             $size    	The size
	 * @param   int             $precision  The precision
     *
     * @return  int|string
     *     
     */
	function formatBytes($size, $precision = 2)
	{
		$base = log($size, 1024);
		$suffixes = array('', 'K', 'M', 'G', 'T');   

		return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	}
	
	/**
     * Funci�n para devolver un color seg�n el n�mero pasado como argumento
     *
	 * @param   float             $p    	The percentage
     *
     * @return  string
     *     
     */
	function percent_to_color($p){
		if($p < 30) return 'success';
		if($p < 45) return 'info';
		if($p < 60) return 'primary';
		if($p < 75) return 'warning';
		return 'danger';
	}
		
	/**
     * Funci�n para habilitar las estad�sticas
     *
     * @param   string             $data    The data of analytics
     *
     * @return void
     *     
     */
	private function enable_analytics($data)
	{		
		$this->write_log("Launching ENABLE_ANALYTICS task");
		
		if (!file_exists(JPATH_ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'securitycheckproanalytics'))
		{
			$this->write_log("Analytics is not installed.");
			$this->data = 'Analytics is not installed';
			$this->status = self::STATUS_ERROR;
			$this->cipher = self::CIPHER_RAW;
		} else {
			$response = $this->decrypt($data, $this->password);
			if (strpos($response, 'Internal error') === 0) {
				$this->write_log("Decryption failed for enable_analytics");
				$this->data = ['result' => false]; return;
			}
			$response = json_decode($response, true);
			if (!is_array($response) || !isset($response['website_code'])) {
				$this->write_log("Invalid payload for enable_analytics");
				$this->data = ['result' => false]; return;
			}

			$website_code = $response['website_code'];
			$cpanel_model = new CpanelModel();

			$success = $cpanel_model->enable_analytics($website_code,$this->site);

			$this->data = [
				'analytics_enabled'        => $success
			];
			$this->write_log("ENABLE_ANALYTICS task finished");
		}
	}
	
	/**
     * Funci�n para deshabilitar las estad�sticas
     *
     * @param   string             $data    The data of analytics
     *
     * @return  void
     *     
     */
	private function disable_analytics($data)
	{		
		$this->write_log("Launching DISABLE_ANALYTICS task");
		
		$response = $this->decrypt($data, $this->password);
		if (strpos($response, 'Internal error') === 0) {
			$this->write_log("Decryption failed for disable_analytics");
			$this->data = ['result' => false]; return;
		}
		$response = json_decode($response, true);
		if (!is_array($response) || !isset($response['website_code'])) {
			$this->write_log("Invalid payload for disable_analytics");
			$this->data = ['result' => false]; return;
		}

		$website_code = $response['website_code'];
		$cpanel_model = new CpanelModel();

		$success = $cpanel_model->disable_analytics($website_code,$this->site);

		$this->data = [
			'analytics_disabled'        => $success
		];
		$this->write_log("ENABLE_ANALYTICS task finished");

	}
}
