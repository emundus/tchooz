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
class AbstractThreeDSecure extends DataObject
{
    /**
     * @var AmountOfMoney|null
     */
    public ?AmountOfMoney $authenticationAmount = null;

    /**
     * @var string|null
     */
    public ?string $authenticationFlow = null;

    /**
     * @var string|null
     */
    public ?string $challengeCanvasSize = null;

    /**
     * @var string|null
     */
    public ?string $challengeIndicator = null;

    /**
     * @var string|null
     */
    public ?string $exemptionRequest = null;

    /**
     * @var ThreeDSecureData|null
     */
    public ?ThreeDSecureData $priorThreeDSecureData = null;

    /**
     * @var SdkDataInput|null
     */
    public ?SdkDataInput $sdkData = null;

    /**
     * @var bool|null
     */
    public ?bool $skipAuthentication = null;

    /**
     * @var string|null
     */
    public ?string $transactionRiskLevel = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->authenticationAmount)) {
            $object->authenticationAmount = $this->authenticationAmount->toObject();
        }
        if (!is_null($this->authenticationFlow)) {
            $object->authenticationFlow = $this->authenticationFlow;
        }
        if (!is_null($this->challengeCanvasSize)) {
            $object->challengeCanvasSize = $this->challengeCanvasSize;
        }
        if (!is_null($this->challengeIndicator)) {
            $object->challengeIndicator = $this->challengeIndicator;
        }
        if (!is_null($this->exemptionRequest)) {
            $object->exemptionRequest = $this->exemptionRequest;
        }
        if (!is_null($this->priorThreeDSecureData)) {
            $object->priorThreeDSecureData = $this->priorThreeDSecureData->toObject();
        }
        if (!is_null($this->sdkData)) {
            $object->sdkData = $this->sdkData->toObject();
        }
        if (!is_null($this->skipAuthentication)) {
            $object->skipAuthentication = $this->skipAuthentication;
        }
        if (!is_null($this->transactionRiskLevel)) {
            $object->transactionRiskLevel = $this->transactionRiskLevel;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AbstractThreeDSecure
    {
        parent::fromObject($object);
        if (property_exists($object, 'authenticationAmount')) {
            if (!is_object($object->authenticationAmount)) {
                throw new UnexpectedValueException('value \'' . print_r($object->authenticationAmount, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->authenticationAmount = $value->fromObject($object->authenticationAmount);
        }
        if (property_exists($object, 'authenticationFlow')) {
            $this->authenticationFlow = $object->authenticationFlow;
        }
        if (property_exists($object, 'challengeCanvasSize')) {
            $this->challengeCanvasSize = $object->challengeCanvasSize;
        }
        if (property_exists($object, 'challengeIndicator')) {
            $this->challengeIndicator = $object->challengeIndicator;
        }
        if (property_exists($object, 'exemptionRequest')) {
            $this->exemptionRequest = $object->exemptionRequest;
        }
        if (property_exists($object, 'priorThreeDSecureData')) {
            if (!is_object($object->priorThreeDSecureData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->priorThreeDSecureData, true) . '\' is not an object');
            }
            $value = new ThreeDSecureData();
            $this->priorThreeDSecureData = $value->fromObject($object->priorThreeDSecureData);
        }
        if (property_exists($object, 'sdkData')) {
            if (!is_object($object->sdkData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->sdkData, true) . '\' is not an object');
            }
            $value = new SdkDataInput();
            $this->sdkData = $value->fromObject($object->sdkData);
        }
        if (property_exists($object, 'skipAuthentication')) {
            $this->skipAuthentication = $object->skipAuthentication;
        }
        if (property_exists($object, 'transactionRiskLevel')) {
            $this->transactionRiskLevel = $object->transactionRiskLevel;
        }
        return $this;
    }
}
