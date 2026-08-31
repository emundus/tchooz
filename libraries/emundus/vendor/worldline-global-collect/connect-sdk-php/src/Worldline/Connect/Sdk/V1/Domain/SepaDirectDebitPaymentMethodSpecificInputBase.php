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
class SepaDirectDebitPaymentMethodSpecificInputBase extends AbstractSepaDirectDebitPaymentMethodSpecificInput
{
    /**
     * @var SepaDirectDebitPaymentProduct771SpecificInputBase|null
     */
    public ?SepaDirectDebitPaymentProduct771SpecificInputBase $paymentProduct771SpecificInput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProduct771SpecificInput)) {
            $object->paymentProduct771SpecificInput = $this->paymentProduct771SpecificInput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): SepaDirectDebitPaymentMethodSpecificInputBase
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProduct771SpecificInput')) {
            if (!is_object($object->paymentProduct771SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct771SpecificInput, true) . '\' is not an object');
            }
            $value = new SepaDirectDebitPaymentProduct771SpecificInputBase();
            $this->paymentProduct771SpecificInput = $value->fromObject($object->paymentProduct771SpecificInput);
        }
        return $this;
    }
}
