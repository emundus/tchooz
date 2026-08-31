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
class OrderTypeInformation extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $fundingType = null;

    /**
     * @var string|null
     */
    public ?string $paymentCode = null;

    /**
     * @var string|null
     */
    public ?string $purchaseType = null;

    /**
     * @var string|null
     */
    public ?string $transactionType = null;

    /**
     * @var string|null
     */
    public ?string $usageType = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->fundingType)) {
            $object->fundingType = $this->fundingType;
        }
        if (!is_null($this->paymentCode)) {
            $object->paymentCode = $this->paymentCode;
        }
        if (!is_null($this->purchaseType)) {
            $object->purchaseType = $this->purchaseType;
        }
        if (!is_null($this->transactionType)) {
            $object->transactionType = $this->transactionType;
        }
        if (!is_null($this->usageType)) {
            $object->usageType = $this->usageType;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): OrderTypeInformation
    {
        parent::fromObject($object);
        if (property_exists($object, 'fundingType')) {
            $this->fundingType = $object->fundingType;
        }
        if (property_exists($object, 'paymentCode')) {
            $this->paymentCode = $object->paymentCode;
        }
        if (property_exists($object, 'purchaseType')) {
            $this->purchaseType = $object->purchaseType;
        }
        if (property_exists($object, 'transactionType')) {
            $this->transactionType = $object->transactionType;
        }
        if (property_exists($object, 'usageType')) {
            $this->usageType = $object->usageType;
        }
        return $this;
    }
}
