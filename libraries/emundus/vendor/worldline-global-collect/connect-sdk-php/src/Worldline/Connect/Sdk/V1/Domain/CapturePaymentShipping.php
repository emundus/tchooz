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
class CapturePaymentShipping extends DataObject
{
    /**
     * @var AddressPersonal|null
     */
    public ?AddressPersonal $address = null;

    /**
     * @var string|null
     */
    public ?string $emailAddress = null;

    /**
     * @var string|null
     */
    public ?string $shippedFromZip = null;

    /**
     * @var string|null
     */
    public ?string $trackingNumber = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->address)) {
            $object->address = $this->address->toObject();
        }
        if (!is_null($this->emailAddress)) {
            $object->emailAddress = $this->emailAddress;
        }
        if (!is_null($this->shippedFromZip)) {
            $object->shippedFromZip = $this->shippedFromZip;
        }
        if (!is_null($this->trackingNumber)) {
            $object->trackingNumber = $this->trackingNumber;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CapturePaymentShipping
    {
        parent::fromObject($object);
        if (property_exists($object, 'address')) {
            if (!is_object($object->address)) {
                throw new UnexpectedValueException('value \'' . print_r($object->address, true) . '\' is not an object');
            }
            $value = new AddressPersonal();
            $this->address = $value->fromObject($object->address);
        }
        if (property_exists($object, 'emailAddress')) {
            $this->emailAddress = $object->emailAddress;
        }
        if (property_exists($object, 'shippedFromZip')) {
            $this->shippedFromZip = $object->shippedFromZip;
        }
        if (property_exists($object, 'trackingNumber')) {
            $this->trackingNumber = $object->trackingNumber;
        }
        return $this;
    }
}
