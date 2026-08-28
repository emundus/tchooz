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
class RefundPaymentProduct840SpecificOutput extends DataObject
{
    /**
     * @var RefundPaymentProduct840CustomerAccount|null
     */
    public ?RefundPaymentProduct840CustomerAccount $customerAccount = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->customerAccount)) {
            $object->customerAccount = $this->customerAccount->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RefundPaymentProduct840SpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'customerAccount')) {
            if (!is_object($object->customerAccount)) {
                throw new UnexpectedValueException('value \'' . print_r($object->customerAccount, true) . '\' is not an object');
            }
            $value = new RefundPaymentProduct840CustomerAccount();
            $this->customerAccount = $value->fromObject($object->customerAccount);
        }
        return $this;
    }
}
