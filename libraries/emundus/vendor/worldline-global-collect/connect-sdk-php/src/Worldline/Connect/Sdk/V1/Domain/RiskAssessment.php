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
class RiskAssessment extends DataObject
{
    /**
     * @var FraudFields|null
     */
    public ?FraudFields $fraudFields = null;

    /**
     * @var MerchantRiskAssessment|null
     */
    public ?MerchantRiskAssessment $merchant = null;

    /**
     * @var OrderRiskAssessment|null
     */
    public ?OrderRiskAssessment $order = null;

    /**
     * @var int|null
     */
    public ?int $paymentProductId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->fraudFields)) {
            $object->fraudFields = $this->fraudFields->toObject();
        }
        if (!is_null($this->merchant)) {
            $object->merchant = $this->merchant->toObject();
        }
        if (!is_null($this->order)) {
            $object->order = $this->order->toObject();
        }
        if (!is_null($this->paymentProductId)) {
            $object->paymentProductId = $this->paymentProductId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RiskAssessment
    {
        parent::fromObject($object);
        if (property_exists($object, 'fraudFields')) {
            if (!is_object($object->fraudFields)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fraudFields, true) . '\' is not an object');
            }
            $value = new FraudFields();
            $this->fraudFields = $value->fromObject($object->fraudFields);
        }
        if (property_exists($object, 'merchant')) {
            if (!is_object($object->merchant)) {
                throw new UnexpectedValueException('value \'' . print_r($object->merchant, true) . '\' is not an object');
            }
            $value = new MerchantRiskAssessment();
            $this->merchant = $value->fromObject($object->merchant);
        }
        if (property_exists($object, 'order')) {
            if (!is_object($object->order)) {
                throw new UnexpectedValueException('value \'' . print_r($object->order, true) . '\' is not an object');
            }
            $value = new OrderRiskAssessment();
            $this->order = $value->fromObject($object->order);
        }
        if (property_exists($object, 'paymentProductId')) {
            $this->paymentProductId = $object->paymentProductId;
        }
        return $this;
    }
}
