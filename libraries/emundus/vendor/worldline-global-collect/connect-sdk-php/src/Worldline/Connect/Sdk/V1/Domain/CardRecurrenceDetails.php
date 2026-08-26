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
class CardRecurrenceDetails extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $endDate = null;

    /**
     * @var int|null
     */
    public ?int $minFrequency = null;

    /**
     * @var string|null
     */
    public ?string $recurringPaymentSequenceIndicator = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->endDate)) {
            $object->endDate = $this->endDate;
        }
        if (!is_null($this->minFrequency)) {
            $object->minFrequency = $this->minFrequency;
        }
        if (!is_null($this->recurringPaymentSequenceIndicator)) {
            $object->recurringPaymentSequenceIndicator = $this->recurringPaymentSequenceIndicator;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CardRecurrenceDetails
    {
        parent::fromObject($object);
        if (property_exists($object, 'endDate')) {
            $this->endDate = $object->endDate;
        }
        if (property_exists($object, 'minFrequency')) {
            $this->minFrequency = $object->minFrequency;
        }
        if (property_exists($object, 'recurringPaymentSequenceIndicator')) {
            $this->recurringPaymentSequenceIndicator = $object->recurringPaymentSequenceIndicator;
        }
        return $this;
    }
}
