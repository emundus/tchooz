<?php
namespace Worldline\Connect\Sdk;

/**
 * Class CallContext
 *
 * @package Worldline\Connect\Sdk
 */
class CallContext
{
    /**
     * @var string
     */
    private string $idempotenceKey = '';

    /**
     * @var string
     */
    private string $idempotenceRequestTimestamp = '';

    /**
     * @return string
     */
    public function getIdempotenceKey(): string
    {
        return $this->idempotenceKey;
    }

    /**
     * @param string $idempotenceKey
     *
     * @return void
     */
    public function setIdempotenceKey(string $idempotenceKey): void
    {
        $this->idempotenceKey = $idempotenceKey;
    }

    /**
     * @return string
     */
    public function getIdempotenceRequestTimestamp(): string
    {
        return $this->idempotenceRequestTimestamp;
    }

    /**
     * @param string $idempotenceRequestTimestamp
     *
     * @return void
     */
    public function setIdempotenceRequestTimestamp(string $idempotenceRequestTimestamp): void
    {
        $this->idempotenceRequestTimestamp = $idempotenceRequestTimestamp;
    }
}
