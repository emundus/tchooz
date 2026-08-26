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
class CreatePaymentProductSessionRequest extends DataObject
{
    /**
     * @var MobilePaymentProductSession302SpecificInput|null
     */
    public ?MobilePaymentProductSession302SpecificInput $paymentProductSession302SpecificInput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProductSession302SpecificInput)) {
            $object->paymentProductSession302SpecificInput = $this->paymentProductSession302SpecificInput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CreatePaymentProductSessionRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProductSession302SpecificInput')) {
            if (!is_object($object->paymentProductSession302SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProductSession302SpecificInput, true) . '\' is not an object');
            }
            $value = new MobilePaymentProductSession302SpecificInput();
            $this->paymentProductSession302SpecificInput = $value->fromObject($object->paymentProductSession302SpecificInput);
        }
        return $this;
    }
}
