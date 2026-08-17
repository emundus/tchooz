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
class PaymentProduct863ThirdPartyData extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $appId = null;

    /**
     * @var string|null
     */
    public ?string $nonceStr = null;

    /**
     * @var string|null
     */
    public ?string $packageSign = null;

    /**
     * @var string|null
     */
    public ?string $paySign = null;

    /**
     * @var string|null
     */
    public ?string $prepayId = null;

    /**
     * @var string|null
     */
    public ?string $signType = null;

    /**
     * @var string|null
     */
    public ?string $timeStamp = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->appId)) {
            $object->appId = $this->appId;
        }
        if (!is_null($this->nonceStr)) {
            $object->nonceStr = $this->nonceStr;
        }
        if (!is_null($this->packageSign)) {
            $object->packageSign = $this->packageSign;
        }
        if (!is_null($this->paySign)) {
            $object->paySign = $this->paySign;
        }
        if (!is_null($this->prepayId)) {
            $object->prepayId = $this->prepayId;
        }
        if (!is_null($this->signType)) {
            $object->signType = $this->signType;
        }
        if (!is_null($this->timeStamp)) {
            $object->timeStamp = $this->timeStamp;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProduct863ThirdPartyData
    {
        parent::fromObject($object);
        if (property_exists($object, 'appId')) {
            $this->appId = $object->appId;
        }
        if (property_exists($object, 'nonceStr')) {
            $this->nonceStr = $object->nonceStr;
        }
        if (property_exists($object, 'packageSign')) {
            $this->packageSign = $object->packageSign;
        }
        if (property_exists($object, 'paySign')) {
            $this->paySign = $object->paySign;
        }
        if (property_exists($object, 'prepayId')) {
            $this->prepayId = $object->prepayId;
        }
        if (property_exists($object, 'signType')) {
            $this->signType = $object->signType;
        }
        if (property_exists($object, 'timeStamp')) {
            $this->timeStamp = $object->timeStamp;
        }
        return $this;
    }
}
