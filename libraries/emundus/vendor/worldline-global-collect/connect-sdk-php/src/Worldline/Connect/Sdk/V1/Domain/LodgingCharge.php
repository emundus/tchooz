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
class LodgingCharge extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $chargeAmount = null;

    /**
     * @var string|null
     */
    public ?string $chargeAmountCurrencyCode = null;

    /**
     * @var string|null
     */
    public ?string $chargeType = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->chargeAmount)) {
            $object->chargeAmount = $this->chargeAmount;
        }
        if (!is_null($this->chargeAmountCurrencyCode)) {
            $object->chargeAmountCurrencyCode = $this->chargeAmountCurrencyCode;
        }
        if (!is_null($this->chargeType)) {
            $object->chargeType = $this->chargeType;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): LodgingCharge
    {
        parent::fromObject($object);
        if (property_exists($object, 'chargeAmount')) {
            $this->chargeAmount = $object->chargeAmount;
        }
        if (property_exists($object, 'chargeAmountCurrencyCode')) {
            $this->chargeAmountCurrencyCode = $object->chargeAmountCurrencyCode;
        }
        if (property_exists($object, 'chargeType')) {
            $this->chargeType = $object->chargeType;
        }
        return $this;
    }
}
