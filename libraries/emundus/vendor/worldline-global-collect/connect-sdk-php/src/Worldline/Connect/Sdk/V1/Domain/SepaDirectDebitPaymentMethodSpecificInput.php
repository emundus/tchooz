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
class SepaDirectDebitPaymentMethodSpecificInput extends AbstractSepaDirectDebitPaymentMethodSpecificInput
{
    /**
     * @var string|null
     */
    public ?string $dateCollect = null;

    /**
     * @var string|null
     */
    public ?string $directDebitText = null;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring = null;

    /**
     * @var SepaDirectDebitPaymentProduct771SpecificInput|null
     */
    public ?SepaDirectDebitPaymentProduct771SpecificInput $paymentProduct771SpecificInput = null;

    /**
     * @var string|null
     */
    public ?string $recurringPaymentSequenceIndicator = null;

    /**
     * @var bool|null
     */
    public ?bool $requiresApproval = null;

    /**
     * @var string|null
     */
    public ?string $token = null;

    /**
     * @var bool|null
     */
    public ?bool $tokenize = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->dateCollect)) {
            $object->dateCollect = $this->dateCollect;
        }
        if (!is_null($this->directDebitText)) {
            $object->directDebitText = $this->directDebitText;
        }
        if (!is_null($this->isRecurring)) {
            $object->isRecurring = $this->isRecurring;
        }
        if (!is_null($this->paymentProduct771SpecificInput)) {
            $object->paymentProduct771SpecificInput = $this->paymentProduct771SpecificInput->toObject();
        }
        if (!is_null($this->recurringPaymentSequenceIndicator)) {
            $object->recurringPaymentSequenceIndicator = $this->recurringPaymentSequenceIndicator;
        }
        if (!is_null($this->requiresApproval)) {
            $object->requiresApproval = $this->requiresApproval;
        }
        if (!is_null($this->token)) {
            $object->token = $this->token;
        }
        if (!is_null($this->tokenize)) {
            $object->tokenize = $this->tokenize;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): SepaDirectDebitPaymentMethodSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'dateCollect')) {
            $this->dateCollect = $object->dateCollect;
        }
        if (property_exists($object, 'directDebitText')) {
            $this->directDebitText = $object->directDebitText;
        }
        if (property_exists($object, 'isRecurring')) {
            $this->isRecurring = $object->isRecurring;
        }
        if (property_exists($object, 'paymentProduct771SpecificInput')) {
            if (!is_object($object->paymentProduct771SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct771SpecificInput, true) . '\' is not an object');
            }
            $value = new SepaDirectDebitPaymentProduct771SpecificInput();
            $this->paymentProduct771SpecificInput = $value->fromObject($object->paymentProduct771SpecificInput);
        }
        if (property_exists($object, 'recurringPaymentSequenceIndicator')) {
            $this->recurringPaymentSequenceIndicator = $object->recurringPaymentSequenceIndicator;
        }
        if (property_exists($object, 'requiresApproval')) {
            $this->requiresApproval = $object->requiresApproval;
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        if (property_exists($object, 'tokenize')) {
            $this->tokenize = $object->tokenize;
        }
        return $this;
    }
}
