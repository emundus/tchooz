<?php
namespace Worldline\Connect\Sdk;

use Exception;

/**
 * Class TestCase
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private string $configFilePath;

    /**
     * @var JsonValuesStore|null
     */
    private ?JsonValuesStore $jsonValuesStore = null;

    /**
     *
     */
    public function __construct(?string $name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->configFilePath = dirname(__FILE__) . '/../../../config.json';
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getMerchantId(): string
    {
        return $this->getJsonValuesStore()->getValue('merchant_id');
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getApiKey(): string
    {
        return $this->getJsonValuesStore()->getValue('api_key');
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getApiSecret(): string
    {
        return $this->getJsonValuesStore()->getValue('api_secret');
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getApiEndpoint(): string
    {
        return $this->getJsonValuesStore()->getValue('api_endpoint');
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getProxyHost(): string
    {
        return $this->getJsonValuesStore()->getValue('proxy_host', false);
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getProxyPort(): string
    {
        return $this->getJsonValuesStore()->getValue('proxy_port', false);
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getProxyUsername(): string
    {
        return $this->getJsonValuesStore()->getValue('proxy_username', false);
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getProxyPassword(): string
    {
        return $this->getJsonValuesStore()->getValue('proxy_password', false);
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getHttpBinUrl(): string
    {
        $httpBinUrl = $this->getJsonValuesStore()->getValue('httpbin_url', false);
        if (!$httpBinUrl) {
            $httpBinUrl = 'http://httpbin.org';
        }
        return $httpBinUrl;
    }

    /**
     * @return JsonValuesStore
     */
    protected function getJsonValuesStore(): JsonValuesStore
    {
        if (is_null($this->jsonValuesStore)) {
            $this->jsonValuesStore = new JsonValuesStore($this->configFilePath);
        }
        return $this->jsonValuesStore;
    }

    /**
     * @return CommunicatorConfiguration
     */
    protected function getCommunicatorConfiguration(): CommunicatorConfiguration
    {
        return new CommunicatorConfiguration(
            $this->getApiKey(),
            $this->getApiSecret(),
            $this->getApiEndpoint(),
            'Worldline'
        );
    }
}
