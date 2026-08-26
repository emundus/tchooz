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
class RecurringPaymentsData extends DataObject
{
    /**
     * @var PaymentProduct302SpecificInput|null
     */
    public ?PaymentProduct302SpecificInput $paymentProduct302SpecificInput = null;

    /**
     * @var string|null
     */
    public ?string $recurringEndDate = null;

    /**
     * @var Frequency|null
     */
    public ?Frequency $recurringInterval = null;

    /**
     * @var string|null
     */
    public ?string $recurringStartDate = null;

    /**
     * @var TrialInformation|null
     */
    public ?TrialInformation $trialInformation = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProduct302SpecificInput)) {
            $object->paymentProduct302SpecificInput = $this->paymentProduct302SpecificInput->toObject();
        }
        if (!is_null($this->recurringEndDate)) {
            $object->recurringEndDate = $this->recurringEndDate;
        }
        if (!is_null($this->recurringInterval)) {
            $object->recurringInterval = $this->recurringInterval->toObject();
        }
        if (!is_null($this->recurringStartDate)) {
            $object->recurringStartDate = $this->recurringStartDate;
        }
        if (!is_null($this->trialInformation)) {
            $object->trialInformation = $this->trialInformation->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RecurringPaymentsData
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProduct302SpecificInput')) {
            if (!is_object($object->paymentProduct302SpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct302SpecificInput, true) . '\' is not an object');
            }
            $value = new PaymentProduct302SpecificInput();
            $this->paymentProduct302SpecificInput = $value->fromObject($object->paymentProduct302SpecificInput);
        }
        if (property_exists($object, 'recurringEndDate')) {
            $this->recurringEndDate = $object->recurringEndDate;
        }
        if (property_exists($object, 'recurringInterval')) {
            if (!is_object($object->recurringInterval)) {
                throw new UnexpectedValueException('value \'' . print_r($object->recurringInterval, true) . '\' is not an object');
            }
            $value = new Frequency();
            $this->recurringInterval = $value->fromObject($object->recurringInterval);
        }
        if (property_exists($object, 'recurringStartDate')) {
            $this->recurringStartDate = $object->recurringStartDate;
        }
        if (property_exists($object, 'trialInformation')) {
            if (!is_object($object->trialInformation)) {
                throw new UnexpectedValueException('value \'' . print_r($object->trialInformation, true) . '\' is not an object');
            }
            $value = new TrialInformation();
            $this->trialInformation = $value->fromObject($object->trialInformation);
        }
        return $this;
    }
}
