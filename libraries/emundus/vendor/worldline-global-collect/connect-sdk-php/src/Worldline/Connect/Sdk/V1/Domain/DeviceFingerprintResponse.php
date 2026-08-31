<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Domain;

use UnexpectedValueException;
use Worldline\Connect\Sdk\Domain\DataObject;

/**
 * @package Worldline\Connect\Sdk\V1\Domain
 */
class DeviceFingerprintResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $deviceFingerprintTransactionId = null;

    /**
     * @var string|null
     */
    public ?string $html = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->deviceFingerprintTransactionId)) {
            $object->deviceFingerprintTransactionId = $this->deviceFingerprintTransactionId;
        }
        if (!is_null($this->html)) {
            $object->html = $this->html;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DeviceFingerprintResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'deviceFingerprintTransactionId')) {
            $this->deviceFingerprintTransactionId = $object->deviceFingerprintTransactionId;
        }
        if (property_exists($object, 'html')) {
            $this->html = $object->html;
        }
        return $this;
    }
}
