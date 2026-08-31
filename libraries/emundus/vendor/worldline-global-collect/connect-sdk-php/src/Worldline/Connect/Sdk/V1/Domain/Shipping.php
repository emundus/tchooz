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
class Shipping extends DataObject
{
    /**
     * @var AddressPersonal|null
     */
    public ?AddressPersonal $address = null;

    /**
     * @var string|null
     */
    public ?string $addressIndicator = null;

    /**
     * @var string|null
     */
    public ?string $carrier = null;

    /**
     * @var string|null
     */
    public ?string $comments = null;

    /**
     * @var string|null
     */
    public ?string $emailAddress = null;

    /**
     * @var string|null
     */
    public ?string $firstUsageDate = null;

    /**
     * @var string|null
     */
    public ?string $instructions = null;

    /**
     * @var bool|null
     */
    public ?bool $isFirstUsage = null;

    /**
     * @var string|null
     */
    public ?string $shippedFromZip = null;

    /**
     * @var string|null
     */
    public ?string $trackingNumber = null;

    /**
     * @var string|null
     */
    public ?string $type = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->address)) {
            $object->address = $this->address->toObject();
        }
        if (!is_null($this->addressIndicator)) {
            $object->addressIndicator = $this->addressIndicator;
        }
        if (!is_null($this->carrier)) {
            $object->carrier = $this->carrier;
        }
        if (!is_null($this->comments)) {
            $object->comments = $this->comments;
        }
        if (!is_null($this->emailAddress)) {
            $object->emailAddress = $this->emailAddress;
        }
        if (!is_null($this->firstUsageDate)) {
            $object->firstUsageDate = $this->firstUsageDate;
        }
        if (!is_null($this->instructions)) {
            $object->instructions = $this->instructions;
        }
        if (!is_null($this->isFirstUsage)) {
            $object->isFirstUsage = $this->isFirstUsage;
        }
        if (!is_null($this->shippedFromZip)) {
            $object->shippedFromZip = $this->shippedFromZip;
        }
        if (!is_null($this->trackingNumber)) {
            $object->trackingNumber = $this->trackingNumber;
        }
        if (!is_null($this->type)) {
            $object->type = $this->type;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): Shipping
    {
        parent::fromObject($object);
        if (property_exists($object, 'address')) {
            if (!is_object($object->address)) {
                throw new UnexpectedValueException('value \'' . print_r($object->address, true) . '\' is not an object');
            }
            $value = new AddressPersonal();
            $this->address = $value->fromObject($object->address);
        }
        if (property_exists($object, 'addressIndicator')) {
            $this->addressIndicator = $object->addressIndicator;
        }
        if (property_exists($object, 'carrier')) {
            $this->carrier = $object->carrier;
        }
        if (property_exists($object, 'comments')) {
            $this->comments = $object->comments;
        }
        if (property_exists($object, 'emailAddress')) {
            $this->emailAddress = $object->emailAddress;
        }
        if (property_exists($object, 'firstUsageDate')) {
            $this->firstUsageDate = $object->firstUsageDate;
        }
        if (property_exists($object, 'instructions')) {
            $this->instructions = $object->instructions;
        }
        if (property_exists($object, 'isFirstUsage')) {
            $this->isFirstUsage = $object->isFirstUsage;
        }
        if (property_exists($object, 'shippedFromZip')) {
            $this->shippedFromZip = $object->shippedFromZip;
        }
        if (property_exists($object, 'trackingNumber')) {
            $this->trackingNumber = $object->trackingNumber;
        }
        if (property_exists($object, 'type')) {
            $this->type = $object->type;
        }
        return $this;
    }
}
