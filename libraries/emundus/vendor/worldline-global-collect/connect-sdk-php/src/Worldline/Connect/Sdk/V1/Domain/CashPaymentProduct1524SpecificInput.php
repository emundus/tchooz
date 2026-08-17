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
class CashPaymentProduct1524SpecificInput extends CashPaymentProductWithRedirectSpecificInputBase
{
    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CashPaymentProduct1524SpecificInput
    {
        parent::fromObject($object);
        return $this;
    }
}
