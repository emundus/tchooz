<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque
 * @license   GNU GPL v3 or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Site\Model\JsonModel;

final class DisplayController extends BaseController
{
    /**
     * Constructor
     *
     * @param  array<string,mixed> $config
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->input = Factory::getApplication()->getInput();
    }

    /**
     * Fuerza el task json (compatibilidad)
     *
     * @param  string $task
     * @return mixed
     */
    public function execute($task)
    {
        return parent::execute('json');
    }

    /**
     * Endpoint API: acepta POST (nuevo) + GET legacy
     *
     * @return void
     */
    public function json(): void
    {
        $headers = $this->getRequestHeadersLower();

        // Token
        $tokenHeader = (string) ($headers['token'] ?? '');

        $baseModel = new BaseModel();
        $ccConfig  = $baseModel->getControlCenterConfig();

        $expectedToken = '';
        if (isset($ccConfig['token']) && is_string($ccConfig['token'])) {
            $expectedToken = $ccConfig['token'];
        }

        $model = new JsonModel();

        if ($expectedToken === '' || $tokenHeader === '' || !hash_equals($expectedToken, $tokenHeader)) {
            $model->log_filename = 'error.php';
            $model->write_log("Token is empty or doesn't match with Control Center", 'ERROR');
            return;
        }

        // JSON de entrada: POST preferente, luego GET legacy, luego raw body
        $clientJSON = $this->readIncomingJsonPayload();
        if ($clientJSON === null || $clientJSON === '') {
            $model->log_filename = 'error.php';
            $model->write_log('Empty JSON payload', 'ERROR');
            return;
        }

        // Decodificamos para añadir referrer (fallback en errores de claves)
        $request = json_decode($clientJSON, true);
        if (!is_array($request)) {
            // Caso legacy que llega con %22 etc.
            $request = json_decode(urldecode($clientJSON), true);
        }

        // Referer: mejor leer cabecera que $_SERVER directo
        $ref = (string) ($headers['referer'] ?? '');
        if ($ref !== '' && is_array($request)) {
            $request['referrer'] = $ref;
        }

        $payload = is_array($request) ? json_encode($request, JSON_UNESCAPED_SLASHES) : $clientJSON;
        if ($payload === false) {
            $payload = $clientJSON; // fallback
        }

        $json = $model->register_task($payload);

        // Respuesta directa mínima (el flujo real responde por callback después)
        header('Content-Type: text/plain; charset=utf-8');
        echo (string) $json;
    }

    /**
     * Lee payload JSON desde:
     * 1) POST param "json"
     * 2) GET param "json" (legacy)
     * 3) raw body (si viene como application/json)
     *
     * @return string|null
     */
    private function readIncomingJsonPayload(): ?string
    {
        // POST (nuevo)
        $postJson = $this->input->post->get('json', null, 'raw');
        if (is_string($postJson) && $postJson !== '') {
            return $postJson;
        }

        // GET (legacy)
        $getJson = $this->input->get('json', null, 'raw');
        if (is_string($getJson) && $getJson !== '') {
            return $getJson;
        }

        // Raw body (application/json)
        $raw = file_get_contents('php://input');
		$raw = is_string($raw) ? trim($raw) : '';
        if ($raw !== '' && ($raw[0] === '{' || $raw[0] === '[')) {
            return $raw;
        }

        return null;
    }

    /**
     * Obtiene headers con keys en minúscula.
     *
     * @return array<string,string>
     */
    private function getRequestHeadersLower(): array
    {
        $out = [];

        // getallheaders() no siempre existe (FastCGI, etc.)
        if (function_exists('getallheaders')) {
            /** @var array<string,string> $h */
            $h = (array) getallheaders();
            foreach ($h as $k => $v) {
                $out[strtolower((string) $k)] = (string) $v;
            }
            return $out;
        }

        // Fallback: parse $_SERVER
        foreach ($_SERVER as $k => $v) {
            if (!is_string($v)) {
                continue;
            }
            if (str_starts_with($k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($k, 5)));
                $out[$name] = $v;
            } elseif ($k === 'CONTENT_TYPE') {
                $out['content-type'] = $v;
            } elseif ($k === 'HTTP_REFERER') {
                $out['referer'] = $v;
            }
        }

        return $out;
    }
}