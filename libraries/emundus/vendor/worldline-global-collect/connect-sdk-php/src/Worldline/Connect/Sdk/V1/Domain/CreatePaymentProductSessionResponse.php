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
class CreatePaymentProductSessionResponse extends DataObject
{
    /**
     * @var MobilePaymentProductSession302SpecificOutput|null
     */
    public ?MobilePaymentProductSession302SpecificOutput $paymentProductSession302SpecificOutput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProductSession302SpecificOutput)) {
            $object->paymentProductSession302SpecificOutput = $this->paymentProductSession302SpecificOutput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CreatePaymentProductSessionResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProductSession302SpecificOutput')) {
            if (!is_object($object->paymentProductSession302SpecificOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProductSession302SpecificOutput, true) . '\' is not an object');
            }
            $value = new MobilePaymentProductSession302SpecificOutput();
            $this->paymentProductSession302SpecificOutput = $value->fromObject($object->paymentProductSession302SpecificOutput);
        }
        return $this;
    }
}
