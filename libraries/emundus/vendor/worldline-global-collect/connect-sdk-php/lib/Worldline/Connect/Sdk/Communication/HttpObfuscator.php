<?php
namespace Worldline\Connect\Sdk\Communication;

use Worldline\Connect\Sdk\Logging\BodyObfuscator;
use Worldline\Connect\Sdk\Logging\HeaderObfuscator;

/**
 * Class HttpObfuscator
 *
 * @package Worldline\Connect\Sdk\Communication
 */
class HttpObfuscator
{
    const HTTP_VERSION = 'HTTP/1.1';

    /**
     * @var HeaderObfuscator
     */
    protected HeaderObfuscator $headerObfuscator;

    /**
     * @var BodyObfuscator
     */
    protected BodyObfuscator $bodyObfuscator;

    public function __construct()
    {
        $this->headerObfuscator = new HeaderObfuscator();
        $this->bodyObfuscator = new BodyObfuscator();
    }

    /**
     * @param BodyObfuscator $bodyObfuscator
     *
     * @return void
     */
    public function setBodyObfuscator(BodyObfuscator $bodyObfuscator): void
    {
        $this->bodyObfuscator = $bodyObfuscator;
    }

    /**
     * @param HeaderObfuscator $headerObfuscator
     *
     * @return void
     */
    public function setHeaderObfuscator(HeaderObfuscator $headerObfuscator): void
    {
        $this->headerObfuscator = $headerObfuscator;
    }

    /**
     * @param string $requestMethod
     * @param string $relativeRequestUri
     * @param array  $requestHeaders
     * @param string $requestBody
     *
     * @return string
     */
    public function getRawObfuscatedRequest(
        string $requestMethod,
        string $relativeRequestUri,
        array  $requestHeaders,
        string $requestBody = ''
    ): string {
        $rawObfuscatedRequest = $requestMethod . ' ' . $relativeRequestUri . ' ' . static::HTTP_VERSION;
        if ($requestHeaders) {
            $rawObfuscatedRequest .= PHP_EOL . implode(
                PHP_EOL,
                HttpHeaderHelper::generateRawHeaders($this->headerObfuscator->obfuscateHeaders($requestHeaders))
            );
        }
        if (strlen($requestBody) > 0) {
            $rawObfuscatedRequest .= PHP_EOL . PHP_EOL . $this->bodyObfuscator->obfuscateBody(
                array_key_exists('Content-Type', $requestHeaders) ? $requestHeaders['Content-Type'] : '',
                $requestBody
            );
        }
        return $rawObfuscatedRequest;
    }

    /**
     * @param ConnectionResponse $response
     *
     * @return string
     */
    public function getRawObfuscatedResponse(ConnectionResponse $response): string
    {
        $rawObfuscatedResponse = '';
        $responseHeaders = $response->getHeaders();
        if ($responseHeaders) {
            $rawObfuscatedResponse .= implode(
                PHP_EOL,
                HttpHeaderHelper::generateRawHeaders($this->headerObfuscator->obfuscateHeaders($responseHeaders))
            );
        }
        $responseBody = $response->getBody();
        if (strlen($responseBody) > 0) {
            $rawObfuscatedResponse .= PHP_EOL . PHP_EOL . $this->bodyObfuscator->obfuscateBody(
                $response->getHeaderValue('Content-Type'),
                $responseBody
            );
        }
        return $rawObfuscatedResponse;
    }
}
