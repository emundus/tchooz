<?php
namespace Worldline\Connect\Sdk\Webhooks;

use Exception;

/**
 * Class SecretKeyNotAvailableException
 *
 * @package Worldline\Connect\Sdk\Webhooks
 */
class SecretKeyNotAvailableException extends SignatureValidationException
{
    /**
     * @var string
     */
    private string $keyId;

    /**
     * @param string         $keyId
     * @param string|null    $message
     * @param Exception|null $previous
     */
    public function __construct(string $keyId, ?string $message = null, ?Exception $previous = null)
    {
        parent::__construct($message, $previous);
        $this->keyId = $keyId;
    }

    /**
     * @return string
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }
}
