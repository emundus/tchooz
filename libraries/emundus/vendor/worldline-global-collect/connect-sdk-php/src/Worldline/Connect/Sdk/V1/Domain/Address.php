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
class Address extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $additionalInfo = null;

    /**
     * @var string|null
     */
    public ?string $city = null;

    /**
     * @var string|null
     */
    public ?string $countryCode = null;

    /**
     * @var string|null
     */
    public ?string $houseNumber = null;

    /**
     * @var string|null
     */
    public ?string $state = null;

    /**
     * @var string|null
     */
    public ?string $stateCode = null;

    /**
     * @var string|null
     */
    public ?string $street = null;

    /**
     * @var string|null
     */
    public ?string $zip = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->additionalInfo)) {
            $object->additionalInfo = $this->additionalInfo;
        }
        if (!is_null($this->city)) {
            $object->city = $this->city;
        }
        if (!is_null($this->countryCode)) {
            $object->countryCode = $this->countryCode;
        }
        if (!is_null($this->houseNumber)) {
            $object->houseNumber = $this->houseNumber;
        }
        if (!is_null($this->state)) {
            $object->state = $this->state;
        }
        if (!is_null($this->stateCode)) {
            $object->stateCode = $this->stateCode;
        }
        if (!is_null($this->street)) {
            $object->street = $this->street;
        }
        if (!is_null($this->zip)) {
            $object->zip = $this->zip;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): Address
    {
        parent::fromObject($object);
        if (property_exists($object, 'additionalInfo')) {
            $this->additionalInfo = $object->additionalInfo;
        }
        if (property_exists($object, 'city')) {
            $this->city = $object->city;
        }
        if (property_exists($object, 'countryCode')) {
            $this->countryCode = $object->countryCode;
        }
        if (property_exists($object, 'houseNumber')) {
            $this->houseNumber = $object->houseNumber;
        }
        if (property_exists($object, 'state')) {
            $this->state = $object->state;
        }
        if (property_exists($object, 'stateCode')) {
            $this->stateCode = $object->stateCode;
        }
        if (property_exists($object, 'street')) {
            $this->street = $object->street;
        }
        if (property_exists($object, 'zip')) {
            $this->zip = $object->zip;
        }
        return $this;
    }
}
