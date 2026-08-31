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
class TokenSepaDirectDebit extends AbstractToken
{
    /**
     * @var CustomerTokenWithContactDetails|null
     */
    public ?CustomerTokenWithContactDetails $customer = null;

    /**
     * @var MandateSepaDirectDebit|null
     */
    public ?MandateSepaDirectDebit $mandate = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->customer)) {
            $object->customer = $this->customer->toObject();
        }
        if (!is_null($this->mandate)) {
            $object->mandate = $this->mandate->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): TokenSepaDirectDebit
    {
        parent::fromObject($object);
        if (property_exists($object, 'customer')) {
            if (!is_object($object->customer)) {
                throw new UnexpectedValueException('value \'' . print_r($object->customer, true) . '\' is not an object');
            }
            $value = new CustomerTokenWithContactDetails();
            $this->customer = $value->fromObject($object->customer);
        }
        if (property_exists($object, 'mandate')) {
            if (!is_object($object->mandate)) {
                throw new UnexpectedValueException('value \'' . print_r($object->mandate, true) . '\' is not an object');
            }
            $value = new MandateSepaDirectDebit();
            $this->mandate = $value->fromObject($object->mandate);
        }
        return $this;
    }
}
