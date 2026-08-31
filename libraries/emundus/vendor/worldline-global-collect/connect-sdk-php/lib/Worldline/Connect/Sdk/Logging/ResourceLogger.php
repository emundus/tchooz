<?php
namespace Worldline\Connect\Sdk\Logging;

use Exception;
use UnexpectedValueException;

/**
 * Class ResourceLogger
 *
 * @package Worldline\Connect\Sdk\Logging
 */
class ResourceLogger implements CommunicatorLogger
{
    const DATE_FORMAT_STRING = DATE_ATOM;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @param resource $resource
     */
    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new UnexpectedValueException('resource expected');
        }
        $this->resource = $resource;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message): void
    {
        fwrite($this->resource, $this->getDatePrefix() . $message . PHP_EOL);
    }

    /**
     * @param string    $message
     * @param Exception $exception
     *
     * @return void
     */
    public function logException(string $message, Exception $exception): void
    {
        fwrite($this->resource, $this->getDatePrefix() . $message . PHP_EOL . $exception . PHP_EOL);
    }

    /**
     * @return string
     */
    protected function getDatePrefix(): string
    {
        return date(static::DATE_FORMAT_STRING) . ' ';
    }
}
