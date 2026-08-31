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
class MobilePaymentMethodSpecificInput extends AbstractMobilePaymentMethodSpecificInput
{
    /**
     * @var DecryptedPaymentData|null
     */
    public ?DecryptedPaymentData $decryptedPaymentData = null;

    /**
     * @var string|null
     */
    public ?string $encryptedPaymentData = null;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring = null;

    /**
     * @var string|null
     */
    public ?string $merchantInitiatedReasonIndicator = null;

    /**
     * @var MobilePaymentProduct320SpecificInput|null
     */
    public ?MobilePaymentProduct320SpecificInput $paymentProduct320SpecificInput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->decryptedPaymentData)) {
            $object->decryptedPaymentData = $this->decryptedPaymentData->toObject();
        }
        if (!is_null($this->encryptedPaymentData)) {
            $object->encryptedPaymentData = $this->encryptedPaymentData;
        }
        if (!is_null($this->isRecurring)) {
            $object->isRecurring = $this->isRecurring;
        }
        if (!is_null($this->merchantInitiatedReasonIndicator)) {
            $object->merchantInitiatedReasonIndicator = $this->merchantInitiatedReasonIndicator;
        }
        if (!is_null($this->paymentProduct320SpecificInput)) {
            $object->paymentProduct320SpecificInput = $this->paymentProduct320SpecificInput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MobilePaymentMethodSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'decryptedPaymentData')) {
            if (!is_object($object->decryptedPaymentData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->decryptedPaymentData, true) . '\' is not an object');
            }
            $value = new DecryptedPaymentData();
            $this->decryptedPaymentData = $value->fromObject($object->decryptedPaymentData);
        }
        if (property_exists($object, 'encryptedPaymentData')) {
            $this->encryptedPaymentData = $object->encryptedPaymentData;
        }
        if (property_exists($object, 'isRecurring')) {
            $this->isRecurring = $object->isRecurring;
        }
        if (property_exists($object, 'merchantInitiatedReasonIndicator')) {
            $this->merchantInitiatedReasonIndicator = $object->merchantInitiatedReasonIndicator;
        }
        if (property_exists($object, 'paymentProduct320SpecificInput')) {
            if (!is_object($object->paymentProduct320SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct320SpecificInput, true) . '\' is not an object');
            }
            $value = new MobilePaymentProduct320SpecificInput();
            $this->paymentProduct320SpecificInput = $value->fromObject($object->paymentProduct320SpecificInput);
        }
        return $this;
    }
}
