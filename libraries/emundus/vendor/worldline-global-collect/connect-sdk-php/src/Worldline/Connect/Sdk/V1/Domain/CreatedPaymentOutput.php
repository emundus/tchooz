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
class CreatedPaymentOutput extends DataObject
{
    /**
     * @var DisplayedData|null
     */
    public ?DisplayedData $displayedData = null;

    /**
     * @var bool|null
     */
    public ?bool $isCheckedRememberMe = null;

    /**
     * @var Payment|null
     */
    public ?Payment $payment = null;

    /**
     * @var PaymentCreationReferences|null
     */
    public ?PaymentCreationReferences $paymentCreationReferences = null;

    /**
     * @var string|null
     *
     * @deprecated Use Payment.statusOutput.statusCategory instead
     */
    public ?string $paymentStatusCategory = null;

    /**
     * @var bool|null
     */
    public ?bool $tokenizationSucceeded = null;

    /**
     * @var string|null
     */
    public ?string $tokens = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->displayedData)) {
            $object->displayedData = $this->displayedData->toObject();
        }
        if (!is_null($this->isCheckedRememberMe)) {
            $object->isCheckedRememberMe = $this->isCheckedRememberMe;
        }
        if (!is_null($this->payment)) {
            $object->payment = $this->payment->toObject();
        }
        if (!is_null($this->paymentCreationReferences)) {
            $object->paymentCreationReferences = $this->paymentCreationReferences->toObject();
        }
        if (!is_null($this->paymentStatusCategory)) {
            $object->paymentStatusCategory = $this->paymentStatusCategory;
        }
        if (!is_null($this->tokenizationSucceeded)) {
            $object->tokenizationSucceeded = $this->tokenizationSucceeded;
        }
        if (!is_null($this->tokens)) {
            $object->tokens = $this->tokens;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CreatedPaymentOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'displayedData')) {
            if (!is_object($object->displayedData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->displayedData, true) . '\' is not an object');
            }
            $value = new DisplayedData();
            $this->displayedData = $value->fromObject($object->displayedData);
        }
        if (property_exists($object, 'isCheckedRememberMe')) {
            $this->isCheckedRememberMe = $object->isCheckedRememberMe;
        }
        if (property_exists($object, 'payment')) {
            if (!is_object($object->payment)) {
                throw new UnexpectedValueException('value \'' . print_r($object->payment, true) . '\' is not an object');
            }
            $value = new Payment();
            $this->payment = $value->fromObject($object->payment);
        }
        if (property_exists($object, 'paymentCreationReferences')) {
            if (!is_object($object->paymentCreationReferences)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentCreationReferences, true) . '\' is not an object');
            }
            $value = new PaymentCreationReferences();
            $this->paymentCreationReferences = $value->fromObject($object->paymentCreationReferences);
        }
        if (property_exists($object, 'paymentStatusCategory')) {
            $this->paymentStatusCategory = $object->paymentStatusCategory;
        }
        if (property_exists($object, 'tokenizationSucceeded')) {
            $this->tokenizationSucceeded = $object->tokenizationSucceeded;
        }
        if (property_exists($object, 'tokens')) {
            $this->tokens = $object->tokens;
        }
        return $this;
    }
}
