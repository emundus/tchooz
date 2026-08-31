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
class CardPaymentMethodSpecificInputBase extends AbstractCardPaymentMethodSpecificInput
{
    /**
     * @var ThreeDSecureBase|null
     */
    public ?ThreeDSecureBase $threeDSecure = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
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
    public function fromObject(object $object): CardPaymentMethodSpecificInputBase
    {
        parent::fromObject($object);
        if (property_exists($object, 'threeDSecure')) {
            if (!is_object($object->threeDSecure)) {
                throw new UnexpectedValueException('value \'' . print_r($object->threeDSecure, true) . '\' is not an object');
            }
            $value = new ThreeDSecureBase();
            $this->threeDSecure = $value->fromObject($object->threeDSecure);
        }
        return $this;
    }
}
