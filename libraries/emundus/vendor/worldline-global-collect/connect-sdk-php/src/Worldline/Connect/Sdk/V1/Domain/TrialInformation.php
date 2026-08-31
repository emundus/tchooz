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
class TrialInformation extends DataObject
{
    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $amountOfMoneyAfterTrial = null;

    /**
     * @var string|null
     */
    public ?string $endDate = null;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring = null;

    /**
     * @var string|null
     */
    public ?string $startDate = null;

    /**
     * @var TrialPeriod|null
     */
    public ?TrialPeriod $trialPeriod = null;

    /**
     * @var Frequency|null
     */
    public ?Frequency $trialPeriodRecurring = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->amountOfMoneyAfterTrial)) {
            $object->amountOfMoneyAfterTrial = $this->amountOfMoneyAfterTrial->toObject();
        }
        if (!is_null($this->endDate)) {
            $object->endDate = $this->endDate;
        }
        if (!is_null($this->isRecurring)) {
            $object->isRecurring = $this->isRecurring;
        }
        if (!is_null($this->startDate)) {
            $object->startDate = $this->startDate;
        }
        if (!is_null($this->trialPeriod)) {
            $object->trialPeriod = $this->trialPeriod->toObject();
        }
        if (!is_null($this->trialPeriodRecurring)) {
            $object->trialPeriodRecurring = $this->trialPeriodRecurring->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): TrialInformation
    {
        parent::fromObject($object);
        if (property_exists($object, 'amountOfMoneyAfterTrial')) {
            if (!is_object($object->amountOfMoneyAfterTrial)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoneyAfterTrial, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoneyAfterTrial = $value->fromObject($object->amountOfMoneyAfterTrial);
        }
        if (property_exists($object, 'endDate')) {
            $this->endDate = $object->endDate;
        }
        if (property_exists($object, 'isRecurring')) {
            $this->isRecurring = $object->isRecurring;
        }
        if (property_exists($object, 'startDate')) {
            $this->startDate = $object->startDate;
        }
        if (property_exists($object, 'trialPeriod')) {
            if (!is_object($object->trialPeriod)) {
                throw new UnexpectedValueException('value \'' . print_r($object->trialPeriod, true) . '\' is not an object');
            }
            $value = new TrialPeriod();
            $this->trialPeriod = $value->fromObject($object->trialPeriod);
        }
        if (property_exists($object, 'trialPeriodRecurring')) {
            if (!is_object($object->trialPeriodRecurring)) {
                throw new UnexpectedValueException('value \'' . print_r($object->trialPeriodRecurring, true) . '\' is not an object');
            }
            $value = new Frequency();
            $this->trialPeriodRecurring = $value->fromObject($object->trialPeriodRecurring);
        }
        return $this;
    }
}
