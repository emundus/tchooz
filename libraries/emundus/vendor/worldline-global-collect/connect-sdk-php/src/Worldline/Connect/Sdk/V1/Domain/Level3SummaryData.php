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
 *
 * @deprecated Use ShoppingCart.amountBreakdown instead
 */
class Level3SummaryData extends DataObject
{
    /**
     * @var int|null
     *
     * @deprecated Use ShoppingCart.amountBreakdown with type DISCOUNT instead
     */
    public ?int $discountAmount = null;

    /**
     * @var int|null
     *
     * @deprecated Use ShoppingCart.amountBreakdown with type DUTY instead
     */
    public ?int $dutyAmount = null;

    /**
     * @var int|null
     *
     * @deprecated Use ShoppingCart.amountBreakdown with type SHIPPING instead
     */
    public ?int $shippingAmount = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->discountAmount)) {
            $object->discountAmount = $this->discountAmount;
        }
        if (!is_null($this->dutyAmount)) {
            $object->dutyAmount = $this->dutyAmount;
        }
        if (!is_null($this->shippingAmount)) {
            $object->shippingAmount = $this->shippingAmount;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): Level3SummaryData
    {
        parent::fromObject($object);
        if (property_exists($object, 'discountAmount')) {
            $this->discountAmount = $object->discountAmount;
        }
        if (property_exists($object, 'dutyAmount')) {
            $this->dutyAmount = $object->dutyAmount;
        }
        if (property_exists($object, 'shippingAmount')) {
            $this->shippingAmount = $object->shippingAmount;
        }
        return $this;
    }
}
