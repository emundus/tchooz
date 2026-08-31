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
class ThirdPartyData extends DataObject
{
    /**
     * @var PaymentProduct863ThirdPartyData|null
     */
    public ?PaymentProduct863ThirdPartyData $paymentProduct863 = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProduct863)) {
            $object->paymentProduct863 = $this->paymentProduct863->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ThirdPartyData
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProduct863')) {
            if (!is_object($object->paymentProduct863)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct863, true) . '\' is not an object');
            }
            $value = new PaymentProduct863ThirdPartyData();
            $this->paymentProduct863 = $value->fromObject($object->paymentProduct863);
        }
        return $this;
    }
}
