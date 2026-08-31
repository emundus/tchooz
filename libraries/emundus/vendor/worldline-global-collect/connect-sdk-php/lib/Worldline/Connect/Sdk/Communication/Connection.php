<?php
namespace Worldline\Connect\Sdk\Communication;

use Worldline\Connect\Sdk\Logging\CommunicatorLogger;

/**
 * Class Connection
 *
 * @package Worldline\Connect\Sdk\Communication
 */
interface Connection
{
    /**
     * @param string   $requestUri
     * @param string[] $requestHeaders
     * @param callable $responseHandler Callable accepting the response status code, a response body chunk and the response headers
     *
     * @return void
     */
    public function get(string $requestUri, array $requestHeaders, callable $responseHandler): void;

    /**
     * @param string   $requestUri
     * @param string[] $requestHeaders
     * @param callable $responseHandler Callable accepting the response status code, a response body chunk and the response headers
     *
     * @return void
     */
    public function delete(string $requestUri, array $requestHeaders, callable $responseHandler): void;

    /**
     * @param string                         $requestUri
     * @param string[]                       $requestHeaders
     * @param string|MultipartFormDataObject $body
     * @param callable                       $responseHandler Callable accepting the response status code,
     *                                                        a response body chunk and the response headers
     *
     * @return void
     */
    public function post(string $requestUri, array $requestHeaders, $body, callable $responseHandler): void;

    /**
     * @param string                         $requestUri
     * @param string[]                       $requestHeaders
     * @param string|MultipartFormDataObject $body
     * @param callable                       $responseHandler Callable accepting the response status code,
     *                                                        a response body chunk and the response headers
     *
     * @return void
     */
    public function put(string $requestUri, array $requestHeaders, $body, callable $responseHandler): void;

    /**
     * @param CommunicatorLogger $communicatorLogger
     *
     * @return void
     */
    public function enableLogging(CommunicatorLogger $communicatorLogger): void;

    /**
     * @return void
     */
    public function disableLogging(): void;
}
