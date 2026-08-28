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
class AbstractRedirectPaymentProduct4101SpecificInput extends DataObject
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
    public function fromObject(object $object): AbstractRedirectPaymentProduct4101SpecificInput
    {
        parent::fromObject($object);
        return $this;
    }
}
