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
class SdkDataInput extends DataObject
{
    /**
     * @var DeviceRenderOptions|null
     */
    public ?DeviceRenderOptions $deviceRenderOptions = null;

    /**
     * @var string|null
     */
    public ?string $sdkAppId = null;

    /**
     * @var string|null
     */
    public ?string $sdkEncryptedData = null;

    /**
     * @var string|null
     */
    public ?string $sdkEphemeralPublicKey = null;

    /**
     * @var string|null
     */
    public ?string $sdkMaxTimeout = null;

    /**
     * @var string|null
     */
    public ?string $sdkReferenceNumber = null;

    /**
     * @var string|null
     */
    public ?string $sdkTransactionId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->deviceRenderOptions)) {
            $object->deviceRenderOptions = $this->deviceRenderOptions->toObject();
        }
        if (!is_null($this->sdkAppId)) {
            $object->sdkAppId = $this->sdkAppId;
        }
        if (!is_null($this->sdkEncryptedData)) {
            $object->sdkEncryptedData = $this->sdkEncryptedData;
        }
        if (!is_null($this->sdkEphemeralPublicKey)) {
            $object->sdkEphemeralPublicKey = $this->sdkEphemeralPublicKey;
        }
        if (!is_null($this->sdkMaxTimeout)) {
            $object->sdkMaxTimeout = $this->sdkMaxTimeout;
        }
        if (!is_null($this->sdkReferenceNumber)) {
            $object->sdkReferenceNumber = $this->sdkReferenceNumber;
        }
        if (!is_null($this->sdkTransactionId)) {
            $object->sdkTransactionId = $this->sdkTransactionId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): SdkDataInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'deviceRenderOptions')) {
            if (!is_object($object->deviceRenderOptions)) {
                throw new UnexpectedValueException('value \'' . print_r($object->deviceRenderOptions, true) . '\' is not an object');
            }
            $value = new DeviceRenderOptions();
            $this->deviceRenderOptions = $value->fromObject($object->deviceRenderOptions);
        }
        if (property_exists($object, 'sdkAppId')) {
            $this->sdkAppId = $object->sdkAppId;
        }
        if (property_exists($object, 'sdkEncryptedData')) {
            $this->sdkEncryptedData = $object->sdkEncryptedData;
        }
        if (property_exists($object, 'sdkEphemeralPublicKey')) {
            $this->sdkEphemeralPublicKey = $object->sdkEphemeralPublicKey;
        }
        if (property_exists($object, 'sdkMaxTimeout')) {
            $this->sdkMaxTimeout = $object->sdkMaxTimeout;
        }
        if (property_exists($object, 'sdkReferenceNumber')) {
            $this->sdkReferenceNumber = $object->sdkReferenceNumber;
        }
        if (property_exists($object, 'sdkTransactionId')) {
            $this->sdkTransactionId = $object->sdkTransactionId;
        }
        return $this;
    }
}
