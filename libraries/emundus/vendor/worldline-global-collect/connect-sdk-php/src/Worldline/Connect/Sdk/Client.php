<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk;

use Worldline\Connect\Sdk\Logging\CommunicatorLogger;
use Worldline\Connect\Sdk\V1\V1Client;

/**
 * Worldline Global Collect platform client.
 *
 * @package Worldline\Connect\Sdk
 */
class Client extends ApiResource
{
    /**
     * @var Communicator
     */
    private Communicator $communicator;

    /**
     * @var string
     */
    private string $clientMetaInfo;

    /**
     * Construct a new Worldline Global Collect platform API client.
     *
     * @param Communicator $communicator
     * @param string       $clientMetaInfo
     */
    public function __construct(Communicator $communicator, string $clientMetaInfo = '')
    {
        parent::__construct();
        $this->communicator = $communicator;
        $this->setClientMetaInfo($clientMetaInfo);
        $this->context = array();
    }

    /**
     * @return Communicator
     */
    protected function getCommunicator(): Communicator
    {
        return $this->communicator;
    }

    /**
     * @param CommunicatorLogger $communicatorLogger
     *
     * @return void
     */
    public function enableLogging(CommunicatorLogger $communicatorLogger): void
    {
        $this->getCommunicator()->enableLogging($communicatorLogger);
    }

    /**
     * @return void
     */
    public function disableLogging(): void
    {
        $this->getCommunicator()->disableLogging();
    }

    /**
     * @param string $clientMetaInfo
     *
     * @return $this
     */
    public function setClientMetaInfo(string $clientMetaInfo): Client
    {
        $this->clientMetaInfo = $clientMetaInfo ? base64_encode($clientMetaInfo) : '';
        return $this;
    }

    /**
     * @return string
     */
    protected function getClientMetaInfo(): string
    {
        return $this->clientMetaInfo;
    }

    public function v1(): V1Client
    {
        return new V1Client($this, $this->context);
    }
}
