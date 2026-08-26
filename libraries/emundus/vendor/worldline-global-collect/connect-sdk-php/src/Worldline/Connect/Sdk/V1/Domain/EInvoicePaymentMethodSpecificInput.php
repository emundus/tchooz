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
class EInvoicePaymentMethodSpecificInput extends AbstractEInvoicePaymentMethodSpecificInput
{
    /**
     * @var bool|null
     */
    public ?bool $acceptedTermsAndConditions = null;

    /**
     * @var EInvoicePaymentProduct9000SpecificInput|null
     */
    public ?EInvoicePaymentProduct9000SpecificInput $paymentProduct9000SpecificInput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->acceptedTermsAndConditions)) {
            $object->acceptedTermsAndConditions = $this->acceptedTermsAndConditions;
        }
        if (!is_null($this->paymentProduct9000SpecificInput)) {
            $object->paymentProduct9000SpecificInput = $this->paymentProduct9000SpecificInput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): EInvoicePaymentMethodSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'acceptedTermsAndConditions')) {
            $this->acceptedTermsAndConditions = $object->acceptedTermsAndConditions;
        }
        if (property_exists($object, 'paymentProduct9000SpecificInput')) {
            if (!is_object($object->paymentProduct9000SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct9000SpecificInput, true) . '\' is not an object');
            }
            $value = new EInvoicePaymentProduct9000SpecificInput();
            $this->paymentProduct9000SpecificInput = $value->fromObject($object->paymentProduct9000SpecificInput);
        }
        return $this;
    }
}
