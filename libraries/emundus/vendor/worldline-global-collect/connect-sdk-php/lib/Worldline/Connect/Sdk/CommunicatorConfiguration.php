<?php
namespace Worldline\Connect\Sdk;

use UnexpectedValueException;
use Worldline\Connect\Sdk\Domain\ShoppingCartExtension;

/**
 * Class CommunicatorConfiguration
 *
 * @package Worldline\Connect\Sdk
 */
class CommunicatorConfiguration
{
    /**
     * @var string
     */
    private string $authorizationId;

    /**
     * @var string
     */
    private string $authorizationSecret;

    /**
     * @var string
     */
    private string $apiEndpoint;

    /**
     * @var int
     */
    private int $connectTimeout;

    /**
     * @var int
     */
    private int $readTimeout;

    /**
     * @var ProxyConfiguration|null
     */
    private ?ProxyConfiguration $proxyConfiguration;

    /**
     * @var string
     */
    private string $integrator;

    /**
     * @var ShoppingCartExtension|null
     */
    private ?ShoppingCartExtension $shoppingCartExtension = null;

    /**
     * @param string                  $authorizationId
     * @param string                  $authorizationSecret
     * @param string                  $apiEndpoint
     * @param string                  $integrator
     * @param ProxyConfiguration|null $proxyConfiguration
     * @param int                     $connectTimeout
     * @param int                     $readTimeout
     */
    public function __construct(
        string              $authorizationId,
        string              $authorizationSecret,
        string              $apiEndpoint,
        string              $integrator,
        ?ProxyConfiguration $proxyConfiguration = null,
        int                 $connectTimeout = -1,
        int                 $readTimeout = -1
    ) {
        $this->validateApiEndpoint($apiEndpoint);
        $this->validateIntegrator($integrator);
        $this->authorizationId = $authorizationId;
        $this->authorizationSecret = $authorizationSecret;
        $this->apiEndpoint = $apiEndpoint;
        $this->integrator = $integrator;
        $this->proxyConfiguration = $proxyConfiguration;
        $this->connectTimeout = $connectTimeout;
        $this->readTimeout = $readTimeout;
    }

    private function validateApiEndpoint(string $apiEndpoint): void
    {
        $url = parse_url($apiEndpoint);
        if ($url === false) {
            throw new UnexpectedValueException('apiEndpoint is not a valid URL');
        } elseif (isset($url['path']) && $url['path'] !== '') {
            throw new UnexpectedValueException('apiEndpoint should not contain a path');
        } elseif (isset($url['user']) || isset($url['query']) || isset($url['fragment'])) {
            throw new UnexpectedValueException('apiEndpoint should not contain user info, query or fragment');
        }
    }

    private function validateIntegrator(string $integrator): void
    {
        if (strlen(trim($integrator)) == 0) {
            throw new UnexpectedValueException("integrator is required");
        }
    }

    /**
     * @return string An id used for authorization. This can be the identifier for a secret API key, or something else.
     */
    public function getAuthorizationId(): string
    {
        return $this->authorizationId;
    }

    /**
     * @param string $authorizationId
     *
     * @return void
     */
    public function setAuthorizationId(string $authorizationId): void
    {
        $this->authorizationId = $authorizationId;
    }

    /**
     * @return string A secret used for authorization. This can be a secret API key, or something else.
     */
    public function getAuthorizationSecret(): string
    {
        return $this->authorizationSecret;
    }

    /**
     * @param string $authorizationSecret
     *
     * @return void
     */
    public function setAuthorizationSecret(string $authorizationSecret): void
    {
        $this->authorizationSecret = $authorizationSecret;
    }

    /**
     * This method is an alias for getAuthorizationId.
     *
     * @return string
     */
    public function getApiKeyId(): string
    {
        return $this->getAuthorizationId();
    }

    /**
     * This method is an alias for setAuthorizationId.
     *
     * @param string $apiKeyId
     *
     * @return void
     */
    public function setApiKeyId(string $apiKeyId): void
    {
        $this->setAuthorizationId($apiKeyId);
    }

    /**
     * This method is an alias for getAuthorizationSecret.
     *
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->getAuthorizationSecret();
    }

    /**
     * This method is an alias for setAuthorizationSecret.
     *
     * @param string $apiSecret
     *
     * @return void
     */
    public function setApiSecret(string $apiSecret): void
    {
        $this->setAuthorizationSecret($apiSecret);
    }

    /**
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * @param string $apiEndpoint
     *
     * @return void
     */
    public function setApiEndpoint(string $apiEndpoint): void
    {
        $this->validateApiEndpoint($apiEndpoint);
        $this->apiEndpoint = $apiEndpoint;
    }

    /**
     * @return ProxyConfiguration|null
     */
    public function getProxyConfiguration(): ?ProxyConfiguration
    {
        return $this->proxyConfiguration;
    }

    /**
     * @param ProxyConfiguration|null $proxyConfiguration
     *
     * @return void
     */
    public function setProxyConfiguration(?ProxyConfiguration $proxyConfiguration = null): void
    {
        $this->proxyConfiguration = $proxyConfiguration;
    }

    /**
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * @param int $connectTimeout
     *
     * @return void
     */
    public function setConnectTimeout(int $connectTimeout): void
    {
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * @return int
     */
    public function getReadTimeout(): int
    {
        return $this->readTimeout;
    }

    /**
     * @param int $readTimeout
     *
     * @return void
     */
    public function setReadTimeout(int $readTimeout): void
    {
        $this->readTimeout = $readTimeout;
    }

    /**
     * @return string
     */
    public function getIntegrator(): string
    {
        return $this->integrator;
    }

    /**
     * @param string $integrator
     *
     * @return void
     */
    public function setIntegrator(string $integrator): void
    {
        $this->validateIntegrator($integrator);
        $this->integrator = $integrator;
    }

    /**
     * @return ShoppingCartExtension|null
     */
    public function getShoppingCartExtension(): ?ShoppingCartExtension
    {
        return $this->shoppingCartExtension;
    }

    /**
     * @param ShoppingCartExtension|null $shoppingCartExtension
     *
     * @return void
     */
    public function setShoppingCartExtension(?ShoppingCartExtension $shoppingCartExtension = null): void
    {
        $this->shoppingCartExtension = $shoppingCartExtension;
    }
}
