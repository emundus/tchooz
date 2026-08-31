<?php
namespace Worldline\Connect\Sdk\TestUtil;

use Exception;
use Worldline\Connect\Sdk\Communication\ConnectionResponse;
use Worldline\Connect\Sdk\Communication\DefaultConnection;

class TestingConnection extends DefaultConnection
{
    private ?ConnectionResponse $response;
    private ?Exception $exception;

    public function __construct(?ConnectionResponse $response = null, ?Exception $exception = null)
    {
        parent::__construct();
        $this->response = $response;
        $this->exception = $exception;
    }

    protected function executeRequest(
        string   $httpMethod,
        string   $requestUri,
        array    $requestHeaders,
        $body,
        callable $responseHandler
    ): ?ConnectionResponse {
        if (!is_null($this->exception)) {
            throw $this->exception;
        } else {
            $statusCode = $this->response->getHttpStatusCode();
            $body = $this->response->getBody();
            $headers = $this->response->getHeaders();
            call_user_func($responseHandler, $statusCode, $body, $headers);
            return $this->response;
        }
    }
}
