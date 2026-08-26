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
class CashPaymentMethodSpecificInputBase extends AbstractCashPaymentMethodSpecificInput
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
    public function fromObject(object $object): CashPaymentMethodSpecificInputBase
    {
        parent::fromObject($object);
        return $this;
    }
}
