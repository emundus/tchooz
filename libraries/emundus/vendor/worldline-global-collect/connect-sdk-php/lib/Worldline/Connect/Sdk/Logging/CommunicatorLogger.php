<?php
namespace Worldline\Connect\Sdk\Logging;

use Exception;

/**
 * Class CommunicatorLogger
 *
 * @package Worldline\Connect\Sdk\Logging
 */
interface CommunicatorLogger
{
    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message): void;

    /**
     * @param string    $message
     * @param Exception $exception
     *
     * @return void
     */
    public function logException(string $message, Exception $exception): void;
}
