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
class RefundRequest extends DataObject
{
    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $amountOfMoney = null;

    /**
     * @var BankRefundMethodSpecificInput|null
     */
    public ?BankRefundMethodSpecificInput $bankRefundMethodSpecificInput = null;

    /**
     * @var RefundCustomer|null
     */
    public ?RefundCustomer $customer = null;

    /**
     * @var string|null
     */
    public ?string $refundDate = null;

    /**
     * @var string|null
     */
    public ?string $refundReason = null;

    /**
     * @var RefundReferences|null
     */
    public ?RefundReferences $refundReferences = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->amountOfMoney)) {
            $object->amountOfMoney = $this->amountOfMoney->toObject();
        }
        if (!is_null($this->bankRefundMethodSpecificInput)) {
            $object->bankRefundMethodSpecificInput = $this->bankRefundMethodSpecificInput->toObject();
        }
        if (!is_null($this->customer)) {
            $object->customer = $this->customer->toObject();
        }
        if (!is_null($this->refundDate)) {
            $object->refundDate = $this->refundDate;
        }
        if (!is_null($this->refundReason)) {
            $object->refundReason = $this->refundReason;
        }
        if (!is_null($this->refundReferences)) {
            $object->refundReferences = $this->refundReferences->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RefundRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'amountOfMoney')) {
            if (!is_object($object->amountOfMoney)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoney, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoney = $value->fromObject($object->amountOfMoney);
        }
        if (property_exists($object, 'bankRefundMethodSpecificInput')) {
            if (!is_object($object->bankRefundMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankRefundMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new BankRefundMethodSpecificInput();
            $this->bankRefundMethodSpecificInput = $value->fromObject($object->bankRefundMethodSpecificInput);
        }
        if (property_exists($object, 'customer')) {
            if (!is_object($object->customer)) {
                throw new UnexpectedValueException('value \'' . print_r($object->customer, true) . '\' is not an object');
            }
            $value = new RefundCustomer();
            $this->customer = $value->fromObject($object->customer);
        }
        if (property_exists($object, 'refundDate')) {
            $this->refundDate = $object->refundDate;
        }
        if (property_exists($object, 'refundReason')) {
            $this->refundReason = $object->refundReason;
        }
        if (property_exists($object, 'refundReferences')) {
            if (!is_object($object->refundReferences)) {
                throw new UnexpectedValueException('value \'' . print_r($object->refundReferences, true) . '\' is not an object');
            }
            $value = new RefundReferences();
            $this->refundReferences = $value->fromObject($object->refundReferences);
        }
        return $this;
    }
}
