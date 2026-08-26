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
class PaymentProduct302SpecificInput extends DataObject
{
    /**
     * @var AutomaticReloadBillingDetails|null
     */
    public ?AutomaticReloadBillingDetails $automaticReloadBilling = null;

    /**
     * @var string|null
     */
    public ?string $billingAgreement = null;

    /**
     * @var DeferredBillingDetails|null
     */
    public ?DeferredBillingDetails $deferredBilling = null;

    /**
     * @var string|null
     */
    public ?string $managementUrl = null;

    /**
     * @var string|null
     */
    public ?string $paymentDescription = null;

    /**
     * @var RecurringBillingDetails|null
     */
    public ?RecurringBillingDetails $regularBilling = null;

    /**
     * @var string|null
     */
    public ?string $tokenNotificationUrl = null;

    /**
     * @var RecurringBillingDetails|null
     */
    public ?RecurringBillingDetails $trialBilling = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->automaticReloadBilling)) {
            $object->automaticReloadBilling = $this->automaticReloadBilling->toObject();
        }
        if (!is_null($this->billingAgreement)) {
            $object->billingAgreement = $this->billingAgreement;
        }
        if (!is_null($this->deferredBilling)) {
            $object->deferredBilling = $this->deferredBilling->toObject();
        }
        if (!is_null($this->managementUrl)) {
            $object->managementUrl = $this->managementUrl;
        }
        if (!is_null($this->paymentDescription)) {
            $object->paymentDescription = $this->paymentDescription;
        }
        if (!is_null($this->regularBilling)) {
            $object->regularBilling = $this->regularBilling->toObject();
        }
        if (!is_null($this->tokenNotificationUrl)) {
            $object->tokenNotificationUrl = $this->tokenNotificationUrl;
        }
        if (!is_null($this->trialBilling)) {
            $object->trialBilling = $this->trialBilling->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProduct302SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'automaticReloadBilling')) {
            if (!is_object($object->automaticReloadBilling)) {
                throw new UnexpectedValueException('value \'' . print_r($object->automaticReloadBilling, true) . '\' is not an object');
            }
            $value = new AutomaticReloadBillingDetails();
            $this->automaticReloadBilling = $value->fromObject($object->automaticReloadBilling);
        }
        if (property_exists($object, 'billingAgreement')) {
            $this->billingAgreement = $object->billingAgreement;
        }
        if (property_exists($object, 'deferredBilling')) {
            if (!is_object($object->deferredBilling)) {
                throw new UnexpectedValueException('value \'' . print_r($object->deferredBilling, true) . '\' is not an object');
            }
            $value = new DeferredBillingDetails();
            $this->deferredBilling = $value->fromObject($object->deferredBilling);
        }
        if (property_exists($object, 'managementUrl')) {
            $this->managementUrl = $object->managementUrl;
        }
        if (property_exists($object, 'paymentDescription')) {
            $this->paymentDescription = $object->paymentDescription;
        }
        if (property_exists($object, 'regularBilling')) {
            if (!is_object($object->regularBilling)) {
                throw new UnexpectedValueException('value \'' . print_r($object->regularBilling, true) . '\' is not an object');
            }
            $value = new RecurringBillingDetails();
            $this->regularBilling = $value->fromObject($object->regularBilling);
        }
        if (property_exists($object, 'tokenNotificationUrl')) {
            $this->tokenNotificationUrl = $object->tokenNotificationUrl;
        }
        if (property_exists($object, 'trialBilling')) {
            if (!is_object($object->trialBilling)) {
                throw new UnexpectedValueException('value \'' . print_r($object->trialBilling, true) . '\' is not an object');
            }
            $value = new RecurringBillingDetails();
            $this->trialBilling = $value->fromObject($object->trialBilling);
        }
        return $this;
    }
}
