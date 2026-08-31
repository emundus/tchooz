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
class Installments extends DataObject
{
    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $amountOfMoneyPerInstallment = null;

    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $amountOfMoneyTotal = null;

    /**
     * @var string|null
     */
    public ?string $frequencyOfInstallments = null;

    /**
     * @var int|null
     */
    public ?int $installmentPlanCode = null;

    /**
     * @var string|null
     */
    public ?string $interestRate = null;

    /**
     * @var int|null
     */
    public ?int $numberOfInstallments = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->amountOfMoneyPerInstallment)) {
            $object->amountOfMoneyPerInstallment = $this->amountOfMoneyPerInstallment->toObject();
        }
        if (!is_null($this->amountOfMoneyTotal)) {
            $object->amountOfMoneyTotal = $this->amountOfMoneyTotal->toObject();
        }
        if (!is_null($this->frequencyOfInstallments)) {
            $object->frequencyOfInstallments = $this->frequencyOfInstallments;
        }
        if (!is_null($this->installmentPlanCode)) {
            $object->installmentPlanCode = $this->installmentPlanCode;
        }
        if (!is_null($this->interestRate)) {
            $object->interestRate = $this->interestRate;
        }
        if (!is_null($this->numberOfInstallments)) {
            $object->numberOfInstallments = $this->numberOfInstallments;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): Installments
    {
        parent::fromObject($object);
        if (property_exists($object, 'amountOfMoneyPerInstallment')) {
            if (!is_object($object->amountOfMoneyPerInstallment)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoneyPerInstallment, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoneyPerInstallment = $value->fromObject($object->amountOfMoneyPerInstallment);
        }
        if (property_exists($object, 'amountOfMoneyTotal')) {
            if (!is_object($object->amountOfMoneyTotal)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoneyTotal, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoneyTotal = $value->fromObject($object->amountOfMoneyTotal);
        }
        if (property_exists($object, 'frequencyOfInstallments')) {
            $this->frequencyOfInstallments = $object->frequencyOfInstallments;
        }
        if (property_exists($object, 'installmentPlanCode')) {
            $this->installmentPlanCode = $object->installmentPlanCode;
        }
        if (property_exists($object, 'interestRate')) {
            $this->interestRate = $object->interestRate;
        }
        if (property_exists($object, 'numberOfInstallments')) {
            $this->numberOfInstallments = $object->numberOfInstallments;
        }
        return $this;
    }
}
