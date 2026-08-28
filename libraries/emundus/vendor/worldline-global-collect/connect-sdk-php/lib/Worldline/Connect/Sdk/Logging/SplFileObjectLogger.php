<?php
namespace Worldline\Connect\Sdk\Logging;

use Exception;
use SplFileObject;

/**
 * Class SplFileObjectLogger
 *
 * @package Worldline\Connect\Sdk\Logging
 */
class SplFileObjectLogger implements CommunicatorLogger
{
    const DATE_FORMAT_STRING = DATE_ATOM;

    /**
     * @var SplFileObject
     */
    private SplFileObject $splFileObject;

    /**
     * @param SplFileObject $splFileObject
     */
    public function __construct(SplFileObject $splFileObject)
    {
        $this->splFileObject = $splFileObject;
    }

    /**
     * @return SplFileObject
     */
    public function getSplFileObject(): SplFileObject
    {
        return $this->splFileObject;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message): void
    {
        $this->splFileObject->fwrite($this->getDatePrefix() . $message . PHP_EOL);
    }

    /**
     * @param string    $message
     * @param Exception $exception
     *
     * @return void
     */
    public function logException(string $message, Exception $exception): void
    {
        $this->splFileObject->fwrite($this->getDatePrefix() . $message . PHP_EOL . $exception . PHP_EOL);
    }

    /**
     * @return string
     */
    protected function getDatePrefix(): string
    {
        return date(static::DATE_FORMAT_STRING) . ' ';
    }
}
