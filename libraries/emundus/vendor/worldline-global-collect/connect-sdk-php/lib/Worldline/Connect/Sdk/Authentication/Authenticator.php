<?php
namespace Worldline\Connect\Sdk\Authentication;

/**
 * Class Authenticator
 *
 * @package Worldline\Connect\Sdk\Authentication
 */
interface Authenticator
{
    /**
     * @param string                $httpMethod
     * @param string                $uriPath
     * @param array<string, string> $requestHeaders
     *
     * @return string The full value for the Authorization header
     */
    public function getAuthorization(string $httpMethod, string $uriPath, array $requestHeaders): string;
}
