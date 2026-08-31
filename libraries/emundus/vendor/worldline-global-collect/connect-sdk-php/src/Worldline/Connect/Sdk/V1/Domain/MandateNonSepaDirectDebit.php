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
class MandateNonSepaDirectDebit extends DataObject
{
    /**
     * @var TokenNonSepaDirectDebitPaymentProduct705SpecificData|null
     */
    public ?TokenNonSepaDirectDebitPaymentProduct705SpecificData $paymentProduct705SpecificData = null;

    /**
     * @var TokenNonSepaDirectDebitPaymentProduct730SpecificData|null
     */
    public ?TokenNonSepaDirectDebitPaymentProduct730SpecificData $paymentProduct730SpecificData = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProduct705SpecificData)) {
            $object->paymentProduct705SpecificData = $this->paymentProduct705SpecificData->toObject();
        }
        if (!is_null($this->paymentProduct730SpecificData)) {
            $object->paymentProduct730SpecificData = $this->paymentProduct730SpecificData->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MandateNonSepaDirectDebit
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProduct705SpecificData')) {
            if (!is_object($object->paymentProduct705SpecificData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct705SpecificData, true) . '\' is not an object');
            }
            $value = new TokenNonSepaDirectDebitPaymentProduct705SpecificData();
            $this->paymentProduct705SpecificData = $value->fromObject($object->paymentProduct705SpecificData);
        }
        if (property_exists($object, 'paymentProduct730SpecificData')) {
            if (!is_object($object->paymentProduct730SpecificData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct730SpecificData, true) . '\' is not an object');
            }
            $value = new TokenNonSepaDirectDebitPaymentProduct730SpecificData();
            $this->paymentProduct730SpecificData = $value->fromObject($object->paymentProduct730SpecificData);
        }
        return $this;
    }
}
