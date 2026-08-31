<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Domain;

use UnexpectedValueException;

/**
 * @package Worldline\Connect\Sdk\V1\Domain
 */
class ContactDetails extends ContactDetailsBase
{
    /**
     * @var string|null
     */
    public ?string $faxNumber = null;

    /**
     * @var string|null
     */
    public ?string $mobilePhoneNumber = null;

    /**
     * @var string|null
     */
    public ?string $phoneNumber = null;

    /**
     * @var string|null
     */
    public ?string $workPhoneNumber = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->faxNumber)) {
            $object->faxNumber = $this->faxNumber;
        }
        if (!is_null($this->mobilePhoneNumber)) {
            $object->mobilePhoneNumber = $this->mobilePhoneNumber;
        }
        if (!is_null($this->phoneNumber)) {
            $object->phoneNumber = $this->phoneNumber;
        }
        if (!is_null($this->workPhoneNumber)) {
            $object->workPhoneNumber = $this->workPhoneNumber;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ContactDetails
    {
        parent::fromObject($object);
        if (property_exists($object, 'faxNumber')) {
            $this->faxNumber = $object->faxNumber;
        }
        if (property_exists($object, 'mobilePhoneNumber')) {
            $this->mobilePhoneNumber = $object->mobilePhoneNumber;
        }
        if (property_exists($object, 'phoneNumber')) {
            $this->phoneNumber = $object->phoneNumber;
        }
        if (property_exists($object, 'workPhoneNumber')) {
            $this->workPhoneNumber = $object->workPhoneNumber;
        }
        return $this;
    }
}
