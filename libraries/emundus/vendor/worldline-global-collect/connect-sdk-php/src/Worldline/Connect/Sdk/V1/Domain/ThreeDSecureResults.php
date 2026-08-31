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
class ThreeDSecureResults extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $acsTransactionId = null;

    /**
     * @var string|null
     */
    public ?string $appliedExemption = null;

    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $authenticationAmount = null;

    /**
     * @var string|null
     */
    public ?string $cavv = null;

    /**
     * @var string|null
     */
    public ?string $directoryServerTransactionId = null;

    /**
     * @var string|null
     */
    public ?string $eci = null;

    /**
     * @var ExemptionOutput|null
     */
    public ?ExemptionOutput $exemptionOutput = null;

    /**
     * @var int|null
     */
    public ?int $schemeRiskScore = null;

    /**
     * @var SdkDataOutput|null
     */
    public ?SdkDataOutput $sdkData = null;

    /**
     * @var ThreeDSecureData|null
     */
    public ?ThreeDSecureData $threeDSecureData = null;

    /**
     * @var string|null
     */
    public ?string $threeDSecureVersion = null;

    /**
     * @var string|null
     */
    public ?string $threeDServerTransactionId = null;

    /**
     * @var string|null
     */
    public ?string $xid = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->acsTransactionId)) {
            $object->acsTransactionId = $this->acsTransactionId;
        }
        if (!is_null($this->appliedExemption)) {
            $object->appliedExemption = $this->appliedExemption;
        }
        if (!is_null($this->authenticationAmount)) {
            $object->authenticationAmount = $this->authenticationAmount->toObject();
        }
        if (!is_null($this->cavv)) {
            $object->cavv = $this->cavv;
        }
        if (!is_null($this->directoryServerTransactionId)) {
            $object->directoryServerTransactionId = $this->directoryServerTransactionId;
        }
        if (!is_null($this->eci)) {
            $object->eci = $this->eci;
        }
        if (!is_null($this->exemptionOutput)) {
            $object->exemptionOutput = $this->exemptionOutput->toObject();
        }
        if (!is_null($this->schemeRiskScore)) {
            $object->schemeRiskScore = $this->schemeRiskScore;
        }
        if (!is_null($this->sdkData)) {
            $object->sdkData = $this->sdkData->toObject();
        }
        if (!is_null($this->threeDSecureData)) {
            $object->threeDSecureData = $this->threeDSecureData->toObject();
        }
        if (!is_null($this->threeDSecureVersion)) {
            $object->threeDSecureVersion = $this->threeDSecureVersion;
        }
        if (!is_null($this->threeDServerTransactionId)) {
            $object->threeDServerTransactionId = $this->threeDServerTransactionId;
        }
        if (!is_null($this->xid)) {
            $object->xid = $this->xid;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ThreeDSecureResults
    {
        parent::fromObject($object);
        if (property_exists($object, 'acsTransactionId')) {
            $this->acsTransactionId = $object->acsTransactionId;
        }
        if (property_exists($object, 'appliedExemption')) {
            $this->appliedExemption = $object->appliedExemption;
        }
        if (property_exists($object, 'authenticationAmount')) {
            if (!is_object($object->authenticationAmount)) {
                throw new UnexpectedValueException('value \'' . print_r($object->authenticationAmount, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->authenticationAmount = $value->fromObject($object->authenticationAmount);
        }
        if (property_exists($object, 'cavv')) {
            $this->cavv = $object->cavv;
        }
        if (property_exists($object, 'directoryServerTransactionId')) {
            $this->directoryServerTransactionId = $object->directoryServerTransactionId;
        }
        if (property_exists($object, 'eci')) {
            $this->eci = $object->eci;
        }
        if (property_exists($object, 'exemptionOutput')) {
            if (!is_object($object->exemptionOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->exemptionOutput, true) . '\' is not an object');
            }
            $value = new ExemptionOutput();
            $this->exemptionOutput = $value->fromObject($object->exemptionOutput);
        }
        if (property_exists($object, 'schemeRiskScore')) {
            $this->schemeRiskScore = $object->schemeRiskScore;
        }
        if (property_exists($object, 'sdkData')) {
            if (!is_object($object->sdkData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->sdkData, true) . '\' is not an object');
            }
            $value = new SdkDataOutput();
            $this->sdkData = $value->fromObject($object->sdkData);
        }
        if (property_exists($object, 'threeDSecureData')) {
            if (!is_object($object->threeDSecureData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->threeDSecureData, true) . '\' is not an object');
            }
            $value = new ThreeDSecureData();
            $this->threeDSecureData = $value->fromObject($object->threeDSecureData);
        }
        if (property_exists($object, 'threeDSecureVersion')) {
            $this->threeDSecureVersion = $object->threeDSecureVersion;
        }
        if (property_exists($object, 'threeDServerTransactionId')) {
            $this->threeDServerTransactionId = $object->threeDServerTransactionId;
        }
        if (property_exists($object, 'xid')) {
            $this->xid = $object->xid;
        }
        return $this;
    }
}
