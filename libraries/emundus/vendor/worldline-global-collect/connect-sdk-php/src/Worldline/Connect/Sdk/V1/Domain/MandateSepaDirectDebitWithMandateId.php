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
class MandateSepaDirectDebitWithMandateId extends MandateSepaDirectDebitWithoutCreditor
{
    /**
     * @var string|null
     */
    public ?string $mandateId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->mandateId)) {
            $object->mandateId = $this->mandateId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MandateSepaDirectDebitWithMandateId
    {
        parent::fromObject($object);
        if (property_exists($object, 'mandateId')) {
            $this->mandateId = $object->mandateId;
        }
        return $this;
    }
}
