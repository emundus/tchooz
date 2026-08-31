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
class CustomerPaymentActivity extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $numberOfPaymentAttemptsLast24Hours = null;

    /**
     * @var int|null
     */
    public ?int $numberOfPaymentAttemptsLastYear = null;

    /**
     * @var int|null
     */
    public ?int $numberOfPurchasesLast6Months = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->numberOfPaymentAttemptsLast24Hours)) {
            $object->numberOfPaymentAttemptsLast24Hours = $this->numberOfPaymentAttemptsLast24Hours;
        }
        if (!is_null($this->numberOfPaymentAttemptsLastYear)) {
            $object->numberOfPaymentAttemptsLastYear = $this->numberOfPaymentAttemptsLastYear;
        }
        if (!is_null($this->numberOfPurchasesLast6Months)) {
            $object->numberOfPurchasesLast6Months = $this->numberOfPurchasesLast6Months;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CustomerPaymentActivity
    {
        parent::fromObject($object);
        if (property_exists($object, 'numberOfPaymentAttemptsLast24Hours')) {
            $this->numberOfPaymentAttemptsLast24Hours = $object->numberOfPaymentAttemptsLast24Hours;
        }
        if (property_exists($object, 'numberOfPaymentAttemptsLastYear')) {
            $this->numberOfPaymentAttemptsLastYear = $object->numberOfPaymentAttemptsLastYear;
        }
        if (property_exists($object, 'numberOfPurchasesLast6Months')) {
            $this->numberOfPurchasesLast6Months = $object->numberOfPurchasesLast6Months;
        }
        return $this;
    }
}
