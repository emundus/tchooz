<?php
namespace Worldline\Connect\Sdk\Webhooks;

use RuntimeException;

/**
 * Class ApiVersionMismatchException
 *
 * @package Worldline\Connect\Sdk\Webhooks
 */
class ApiVersionMismatchException extends RuntimeException
{
    /**
     * @var string
     */
    private string $eventApiVersion;

    /**
     * @var string
     */
    private string $sdkApiVersion;

    /**
     * @param string $eventApiVersion
     * @param string $sdkApiVersion
     */
    public function __construct(string $eventApiVersion, string $sdkApiVersion)
    {
        parent::__construct('event API version ' . $eventApiVersion . ' is not compatible with SDK API version ' . $sdkApiVersion);
        $this->eventApiVersion = $eventApiVersion;
        $this->sdkApiVersion = $sdkApiVersion;
    }

    /**
     * @return string
     */
    public function getEventApiVersion(): string
    {
        return $this->eventApiVersion;
    }

    /**
     * @return string
     */
    public function getSdkApiVersion(): string
    {
        return $this->sdkApiVersion;
    }
}
