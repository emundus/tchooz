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
class PaymentProductFieldDataRestrictions extends DataObject
{
    /**
     * @var bool|null
     */
    public ?bool $isRequired = null;

    /**
     * @var PaymentProductFieldValidators|null
     */
    public ?PaymentProductFieldValidators $validators = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->isRequired)) {
            $object->isRequired = $this->isRequired;
        }
        if (!is_null($this->validators)) {
            $object->validators = $this->validators->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProductFieldDataRestrictions
    {
        parent::fromObject($object);
        if (property_exists($object, 'isRequired')) {
            $this->isRequired = $object->isRequired;
        }
        if (property_exists($object, 'validators')) {
            if (!is_object($object->validators)) {
                throw new UnexpectedValueException('value \'' . print_r($object->validators, true) . '\' is not an object');
            }
            $value = new PaymentProductFieldValidators();
            $this->validators = $value->fromObject($object->validators);
        }
        return $this;
    }
}
