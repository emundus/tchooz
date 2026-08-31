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
class DeviceFingerprintDetails extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $paymentId = null;

    /**
     * @var string|null
     */
    public ?string $rawDeviceFingerprintOutput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentId)) {
            $object->paymentId = $this->paymentId;
        }
        if (!is_null($this->rawDeviceFingerprintOutput)) {
            $object->rawDeviceFingerprintOutput = $this->rawDeviceFingerprintOutput;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DeviceFingerprintDetails
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentId')) {
            $this->paymentId = $object->paymentId;
        }
        if (property_exists($object, 'rawDeviceFingerprintOutput')) {
            $this->rawDeviceFingerprintOutput = $object->rawDeviceFingerprintOutput;
        }
        return $this;
    }
}
