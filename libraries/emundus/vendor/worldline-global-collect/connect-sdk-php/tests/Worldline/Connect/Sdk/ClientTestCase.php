<?php
namespace Worldline\Connect\Sdk;

abstract class ClientTestCase extends TestCase
{
    /**
     * @var Client|null
     */
    protected ?Client $client = null;

    /**
     * @var Client|null
     */
    protected ?Client $proxyClient = null;

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        if (is_null($this->client)) {
            $communicatorConfiguration = $this->getCommunicatorConfiguration();
            $communicator = new Communicator($communicatorConfiguration);
            $this->client = new Client($communicator);
        }
        return $this->client;
    }

    /**
     * @return Client
     */
    protected function getProxyClient(): Client
    {
        if (is_null($this->proxyClient)) {
            $communicatorConfiguration = $this->getCommunicatorConfiguration();
            $communicatorConfiguration->setProxyConfiguration(
                new ProxyConfiguration(
                    $this->getProxyHost(),
                    $this->getProxyPort(),
                    $this->getProxyUsername(),
                    $this->getProxyPassword()
                )
            );
            $communicator = new Communicator($communicatorConfiguration);
            $this->proxyClient = new Client($communicator);
        }
        return $this->proxyClient;
    }
}
