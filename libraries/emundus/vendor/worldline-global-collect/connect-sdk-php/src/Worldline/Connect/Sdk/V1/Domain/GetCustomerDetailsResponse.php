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
class GetCustomerDetailsResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $city = null;

    /**
     * @var string|null
     */
    public ?string $country = null;

    /**
     * @var string|null
     */
    public ?string $emailAddress = null;

    /**
     * @var string|null
     */
    public ?string $firstName = null;

    /**
     * @var string|null
     */
    public ?string $fiscalNumber = null;

    /**
     * @var string|null
     */
    public ?string $languageCode = null;

    /**
     * @var string|null
     */
    public ?string $phoneNumber = null;

    /**
     * @var string|null
     */
    public ?string $street = null;

    /**
     * @var string|null
     */
    public ?string $surname = null;

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
        if (!is_null($this->city)) {
            $object->city = $this->city;
        }
        if (!is_null($this->country)) {
            $object->country = $this->country;
        }
        if (!is_null($this->emailAddress)) {
            $object->emailAddress = $this->emailAddress;
        }
        if (!is_null($this->firstName)) {
            $object->firstName = $this->firstName;
        }
        if (!is_null($this->fiscalNumber)) {
            $object->fiscalNumber = $this->fiscalNumber;
        }
        if (!is_null($this->languageCode)) {
            $object->languageCode = $this->languageCode;
        }
        if (!is_null($this->phoneNumber)) {
            $object->phoneNumber = $this->phoneNumber;
        }
        if (!is_null($this->street)) {
            $object->street = $this->street;
        }
        if (!is_null($this->surname)) {
            $object->surname = $this->surname;
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
    public function fromObject(object $object): GetCustomerDetailsResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'city')) {
            $this->city = $object->city;
        }
        if (property_exists($object, 'country')) {
            $this->country = $object->country;
        }
        if (property_exists($object, 'emailAddress')) {
            $this->emailAddress = $object->emailAddress;
        }
        if (property_exists($object, 'firstName')) {
            $this->firstName = $object->firstName;
        }
        if (property_exists($object, 'fiscalNumber')) {
            $this->fiscalNumber = $object->fiscalNumber;
        }
        if (property_exists($object, 'languageCode')) {
            $this->languageCode = $object->languageCode;
        }
        if (property_exists($object, 'phoneNumber')) {
            $this->phoneNumber = $object->phoneNumber;
        }
        if (property_exists($object, 'street')) {
            $this->street = $object->street;
        }
        if (property_exists($object, 'surname')) {
            $this->surname = $object->surname;
        }
        if (property_exists($object, 'zip')) {
            $this->zip = $object->zip;
        }
        return $this;
    }
}
