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
class MobilePaymentProduct320SpecificInputHostedCheckout extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $merchantName = null;

    /**
     * @var string|null
     */
    public ?string $merchantOrigin = null;

    /**
     * @var GPayThreeDSecure|null
     */
    public ?GPayThreeDSecure $threeDSecure = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->merchantName)) {
            $object->merchantName = $this->merchantName;
        }
        if (!is_null($this->merchantOrigin)) {
            $object->merchantOrigin = $this->merchantOrigin;
        }
        if (!is_null($this->threeDSecure)) {
            $object->threeDSecure = $this->threeDSecure->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MobilePaymentProduct320SpecificInputHostedCheckout
    {
        parent::fromObject($object);
        if (property_exists($object, 'merchantName')) {
            $this->merchantName = $object->merchantName;
        }
        if (property_exists($object, 'merchantOrigin')) {
            $this->merchantOrigin = $object->merchantOrigin;
        }
        if (property_exists($object, 'threeDSecure')) {
            if (!is_object($object->threeDSecure)) {
                throw new UnexpectedValueException('value \'' . print_r($object->threeDSecure, true) . '\' is not an object');
            }
            $value = new GPayThreeDSecure();
            $this->threeDSecure = $value->fromObject($object->threeDSecure);
        }
        return $this;
    }
}
