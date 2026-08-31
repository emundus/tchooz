<?php
namespace Worldline\Connect\Sdk\Communication;

/**
 * Class ResponseBuilder
 *
 * @package Worldline\Connect\Sdk\Communication
 */
class ResponseBuilder
{
    /**
     * @var int
     */
    private int $httpStatusCode = 0;

    /**
     * @var array
     */
    private array $headers = array();

    /**
     * @var string
     */
    private string $body = '';

    /**
     * @param int $httpStatusCode
     *
     * @return void
     */
    public function setHttpStatusCode(int $httpStatusCode): void
    {
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @param string $data
     *
     * @return void
     */
    public function appendBody(string $data): void
    {
        $this->body .= $data;
    }

    /**
     * @param string $body
     *
     * @return void
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * @return ConnectionResponse
     */
    public function getResponse(): ConnectionResponse
    {
        return new ConnectionResponse($this->httpStatusCode, $this->headers, $this->body);
    }
}
