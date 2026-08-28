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
class ContactDetailsBase extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $emailAddress = null;

    /**
     * @var string|null
     */
    public ?string $emailMessageType = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->emailAddress)) {
            $object->emailAddress = $this->emailAddress;
        }
        if (!is_null($this->emailMessageType)) {
            $object->emailMessageType = $this->emailMessageType;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ContactDetailsBase
    {
        parent::fromObject($object);
        if (property_exists($object, 'emailAddress')) {
            $this->emailAddress = $object->emailAddress;
        }
        if (property_exists($object, 'emailMessageType')) {
            $this->emailMessageType = $object->emailMessageType;
        }
        return $this;
    }
}
