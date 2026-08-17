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
class CardPaymentMethodSpecificInput extends AbstractCardPaymentMethodSpecificInput
{
    /**
     * @var Card|null
     */
    public ?Card $card = null;

    /**
     * @var ClickToPayInput|null
     */
    public ?ClickToPayInput $clickToPay = null;

    /**
     * @var ExternalCardholderAuthenticationData|null
     *
     * @deprecated Use threeDSecure.externalCardholderAuthenticationData instead
     */
    public ?ExternalCardholderAuthenticationData $externalCardholderAuthenticationData = null;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring = null;

    /**
     * @var string|null
     */
    public ?string $merchantInitiatedReasonIndicator = null;

    /**
     * @var SchemeTokenData|null
     */
    public ?SchemeTokenData $networkTokenData = null;

    /**
     * @var string|null
     *
     * @deprecated Use threeDSecure.redirectionData.returnUrl instead
     */
    public ?string $returnUrl = null;

    /**
     * @var ThreeDSecure|null
     */
    public ?ThreeDSecure $threeDSecure = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->card)) {
            $object->card = $this->card->toObject();
        }
        if (!is_null($this->clickToPay)) {
            $object->clickToPay = $this->clickToPay->toObject();
        }
        if (!is_null($this->externalCardholderAuthenticationData)) {
            $object->externalCardholderAuthenticationData = $this->externalCardholderAuthenticationData->toObject();
        }
        if (!is_null($this->isRecurring)) {
            $object->isRecurring = $this->isRecurring;
        }
        if (!is_null($this->merchantInitiatedReasonIndicator)) {
            $object->merchantInitiatedReasonIndicator = $this->merchantInitiatedReasonIndicator;
        }
        if (!is_null($this->networkTokenData)) {
            $object->networkTokenData = $this->networkTokenData->toObject();
        }
        if (!is_null($this->returnUrl)) {
            $object->returnUrl = $this->returnUrl;
        }
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
    public function fromObject(object $object): CardPaymentMethodSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'card')) {
            if (!is_object($object->card)) {
                throw new UnexpectedValueException('value \'' . print_r($object->card, true) . '\' is not an object');
            }
            $value = new Card();
            $this->card = $value->fromObject($object->card);
        }
        if (property_exists($object, 'clickToPay')) {
            if (!is_object($object->clickToPay)) {
                throw new UnexpectedValueException('value \'' . print_r($object->clickToPay, true) . '\' is not an object');
            }
            $value = new ClickToPayInput();
            $this->clickToPay = $value->fromObject($object->clickToPay);
        }
        if (property_exists($object, 'externalCardholderAuthenticationData')) {
            if (!is_object($object->externalCardholderAuthenticationData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->externalCardholderAuthenticationData, true) . '\' is not an object');
            }
            $value = new ExternalCardholderAuthenticationData();
            $this->externalCardholderAuthenticationData = $value->fromObject($object->externalCardholderAuthenticationData);
        }
        if (property_exists($object, 'isRecurring')) {
            $this->isRecurring = $object->isRecurring;
        }
        if (property_exists($object, 'merchantInitiatedReasonIndicator')) {
            $this->merchantInitiatedReasonIndicator = $object->merchantInitiatedReasonIndicator;
        }
        if (property_exists($object, 'networkTokenData')) {
            if (!is_object($object->networkTokenData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->networkTokenData, true) . '\' is not an object');
            }
            $value = new SchemeTokenData();
            $this->networkTokenData = $value->fromObject($object->networkTokenData);
        }
        if (property_exists($object, 'returnUrl')) {
            $this->returnUrl = $object->returnUrl;
        }
        if (property_exists($object, 'threeDSecure')) {
            if (!is_object($object->threeDSecure)) {
                throw new UnexpectedValueException('value \'' . print_r($object->threeDSecure, true) . '\' is not an object');
            }
            $value = new ThreeDSecure();
            $this->threeDSecure = $value->fromObject($object->threeDSecure);
        }
        return $this;
    }
}
