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
class Seller extends DataObject
{
    /**
     * @var Address|null
     */
    public ?Address $address = null;

    /**
     * @var string|null
     */
    public ?string $channelCode = null;

    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * @var string|null
     */
    public ?string $externalReferenceId = null;

    /**
     * @var string|null
     */
    public ?string $geocode = null;

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var string|null
     */
    public ?string $invoiceNumber = null;

    /**
     * @var bool|null
     */
    public ?bool $isForeignRetailer = null;

    /**
     * @var string|null
     */
    public ?string $mcc = null;

    /**
     * @var string|null
     */
    public ?string $name = null;

    /**
     * @var string|null
     */
    public ?string $phoneNumber = null;

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
        if (!is_null($this->channelCode)) {
            $object->channelCode = $this->channelCode;
        }
        if (!is_null($this->description)) {
            $object->description = $this->description;
        }
        if (!is_null($this->externalReferenceId)) {
            $object->externalReferenceId = $this->externalReferenceId;
        }
        if (!is_null($this->geocode)) {
            $object->geocode = $this->geocode;
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        if (!is_null($this->invoiceNumber)) {
            $object->invoiceNumber = $this->invoiceNumber;
        }
        if (!is_null($this->isForeignRetailer)) {
            $object->isForeignRetailer = $this->isForeignRetailer;
        }
        if (!is_null($this->mcc)) {
            $object->mcc = $this->mcc;
        }
        if (!is_null($this->name)) {
            $object->name = $this->name;
        }
        if (!is_null($this->phoneNumber)) {
            $object->phoneNumber = $this->phoneNumber;
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
    public function fromObject(object $object): Seller
    {
        parent::fromObject($object);
        if (property_exists($object, 'address')) {
            if (!is_object($object->address)) {
                throw new UnexpectedValueException('value \'' . print_r($object->address, true) . '\' is not an object');
            }
            $value = new Address();
            $this->address = $value->fromObject($object->address);
        }
        if (property_exists($object, 'channelCode')) {
            $this->channelCode = $object->channelCode;
        }
        if (property_exists($object, 'description')) {
            $this->description = $object->description;
        }
        if (property_exists($object, 'externalReferenceId')) {
            $this->externalReferenceId = $object->externalReferenceId;
        }
        if (property_exists($object, 'geocode')) {
            $this->geocode = $object->geocode;
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        if (property_exists($object, 'invoiceNumber')) {
            $this->invoiceNumber = $object->invoiceNumber;
        }
        if (property_exists($object, 'isForeignRetailer')) {
            $this->isForeignRetailer = $object->isForeignRetailer;
        }
        if (property_exists($object, 'mcc')) {
            $this->mcc = $object->mcc;
        }
        if (property_exists($object, 'name')) {
            $this->name = $object->name;
        }
        if (property_exists($object, 'phoneNumber')) {
            $this->phoneNumber = $object->phoneNumber;
        }
        if (property_exists($object, 'type')) {
            $this->type = $object->type;
        }
        return $this;
    }
}
