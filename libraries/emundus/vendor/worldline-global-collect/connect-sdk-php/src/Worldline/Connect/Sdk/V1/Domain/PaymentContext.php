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
class PaymentContext extends DataObject
{
    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $amountOfMoney = null;

    /**
     * @var string|null
     */
    public ?string $countryCode = null;

    /**
     * @var bool|null
     */
    public ?bool $isInstallments = null;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->amountOfMoney)) {
            $object->amountOfMoney = $this->amountOfMoney->toObject();
        }
        if (!is_null($this->countryCode)) {
            $object->countryCode = $this->countryCode;
        }
        if (!is_null($this->isInstallments)) {
            $object->isInstallments = $this->isInstallments;
        }
        if (!is_null($this->isRecurring)) {
            $object->isRecurring = $this->isRecurring;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentContext
    {
        parent::fromObject($object);
        if (property_exists($object, 'amountOfMoney')) {
            if (!is_object($object->amountOfMoney)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoney, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoney = $value->fromObject($object->amountOfMoney);
        }
        if (property_exists($object, 'countryCode')) {
            $this->countryCode = $object->countryCode;
        }
        if (property_exists($object, 'isInstallments')) {
            $this->isInstallments = $object->isInstallments;
        }
        if (property_exists($object, 'isRecurring')) {
            $this->isRecurring = $object->isRecurring;
        }
        return $this;
    }
}
