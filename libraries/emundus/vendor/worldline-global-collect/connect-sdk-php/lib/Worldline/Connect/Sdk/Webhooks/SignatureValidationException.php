<?php
namespace Worldline\Connect\Sdk\Webhooks;

use Exception;
use RuntimeException;

/**
 * Class SignatureValidationException
 *
 * @package Worldline\Connect\Sdk\Webhooks
 */
class SignatureValidationException extends RuntimeException
{
    /**
     * @param string|null    $message
     * @param Exception|null $previous
     */
    public function __construct(?string $message = null, ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
