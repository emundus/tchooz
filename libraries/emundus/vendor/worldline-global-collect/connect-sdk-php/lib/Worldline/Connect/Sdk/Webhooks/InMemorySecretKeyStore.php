<?php
namespace Worldline\Connect\Sdk\Webhooks;

use UnexpectedValueException;

/**
 * Class InMemorySecretKeyStore
 *
 * @package Worldline\Connect\Sdk\Webhooks
 */
class InMemorySecretKeyStore implements SecretKeyStore
{
    /**
     * @var array<string, string>
     */
    private array $secretKeys;

    /**
     * @param array<string, string> $secretKeys
     */
    public function __construct(array $secretKeys = array())
    {
        $this->secretKeys = $secretKeys;
    }

    /**
     * @param string $keyId
     *
     * @return string
     * @throws SecretKeyNotAvailableException
     */
    public function getSecretKey(string $keyId): string
    {
        if (!isset($this->secretKeys[$keyId]) || is_null($this->secretKeys[$keyId])) {
            throw new SecretKeyNotAvailableException($keyId, "could not find secret key for key id $keyId");
        }
        return $this->secretKeys[$keyId];
    }

    /**
     * Stores the given secret key for the given key id.
     *
     * @param string $keyId
     * @param string $secretKey
     *
     * @return void
     */
    public function storeSecretKey(string $keyId, string $secretKey): void
    {
        if (strlen(trim($keyId)) == 0) {
            throw new UnexpectedValueException("keyId is required");
        }
        if (strlen(trim($secretKey)) == 0) {
            throw new UnexpectedValueException("secretKey is required");
        }
        $this->secretKeys[$keyId] = $secretKey;
    }

    /**
     * Removes the secret key for the given key id.
     *
     * @param string $keyId
     *
     * @return void
     */
    public function removeSecretKey(string $keyId): void
    {
        unset($this->secretKeys[$keyId]);
    }

    /**
     * Removes all stored secret keys.
     *
     * @return void
     */
    public function clear(): void
    {
        unset($this->secretKeys);
        $this->secretKeys = array();
    }
}
