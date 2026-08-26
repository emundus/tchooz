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
class InAuth extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $deviceCategory = null;

    /**
     * @var string|null
     */
    public ?string $deviceId = null;

    /**
     * @var string|null
     */
    public ?string $riskScore = null;

    /**
     * @var string|null
     */
    public ?string $trueIpAddress = null;

    /**
     * @var string|null
     */
    public ?string $trueIpAddressCountry = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->deviceCategory)) {
            $object->deviceCategory = $this->deviceCategory;
        }
        if (!is_null($this->deviceId)) {
            $object->deviceId = $this->deviceId;
        }
        if (!is_null($this->riskScore)) {
            $object->riskScore = $this->riskScore;
        }
        if (!is_null($this->trueIpAddress)) {
            $object->trueIpAddress = $this->trueIpAddress;
        }
        if (!is_null($this->trueIpAddressCountry)) {
            $object->trueIpAddressCountry = $this->trueIpAddressCountry;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): InAuth
    {
        parent::fromObject($object);
        if (property_exists($object, 'deviceCategory')) {
            $this->deviceCategory = $object->deviceCategory;
        }
        if (property_exists($object, 'deviceId')) {
            $this->deviceId = $object->deviceId;
        }
        if (property_exists($object, 'riskScore')) {
            $this->riskScore = $object->riskScore;
        }
        if (property_exists($object, 'trueIpAddress')) {
            $this->trueIpAddress = $object->trueIpAddress;
        }
        if (property_exists($object, 'trueIpAddressCountry')) {
            $this->trueIpAddressCountry = $object->trueIpAddressCountry;
        }
        return $this;
    }
}
