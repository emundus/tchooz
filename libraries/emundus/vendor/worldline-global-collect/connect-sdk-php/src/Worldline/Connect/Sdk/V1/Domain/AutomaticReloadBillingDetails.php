<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Domain;

use UnexpectedValueException;

/**
 * @package Worldline\Connect\Sdk\V1\Domain
 */
class AutomaticReloadBillingDetails extends BaseBillingDetails
{
    /**
     * @var int|null
     */
    public ?int $automaticReloadPaymentThresholdAmount = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->automaticReloadPaymentThresholdAmount)) {
            $object->automaticReloadPaymentThresholdAmount = $this->automaticReloadPaymentThresholdAmount;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AutomaticReloadBillingDetails
    {
        parent::fromObject($object);
        if (property_exists($object, 'automaticReloadPaymentThresholdAmount')) {
            $this->automaticReloadPaymentThresholdAmount = $object->automaticReloadPaymentThresholdAmount;
        }
        return $this;
    }
}
