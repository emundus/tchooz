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
class HostedCheckoutSpecificInput extends DataObject
{
    /**
     * @var bool|null
     */
    public ?bool $allowClickToPay = null;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring = null;

    /**
     * @var string|null
     */
    public ?string $locale = null;

    /**
     * @var PaymentProductFiltersHostedCheckout|null
     */
    public ?PaymentProductFiltersHostedCheckout $paymentProductFilters = null;

    /**
     * @var RecurringPaymentsData|null
     */
    public ?RecurringPaymentsData $recurringPaymentsData = null;

    /**
     * @var bool|null
     */
    public ?bool $returnCancelState = null;

    /**
     * @var string|null
     */
    public ?string $returnUrl = null;

    /**
     * @var bool|null
     */
    public ?bool $showResultPage = null;

    /**
     * @var string|null
     */
    public ?string $tokens = null;

    /**
     * @var bool|null
     */
    public ?bool $validateShoppingCart = null;

    /**
     * @var string|null
     */
    public ?string $variant = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->allowClickToPay)) {
            $object->allowClickToPay = $this->allowClickToPay;
        }
        if (!is_null($this->isRecurring)) {
            $object->isRecurring = $this->isRecurring;
        }
        if (!is_null($this->locale)) {
            $object->locale = $this->locale;
        }
        if (!is_null($this->paymentProductFilters)) {
            $object->paymentProductFilters = $this->paymentProductFilters->toObject();
        }
        if (!is_null($this->recurringPaymentsData)) {
            $object->recurringPaymentsData = $this->recurringPaymentsData->toObject();
        }
        if (!is_null($this->returnCancelState)) {
            $object->returnCancelState = $this->returnCancelState;
        }
        if (!is_null($this->returnUrl)) {
            $object->returnUrl = $this->returnUrl;
        }
        if (!is_null($this->showResultPage)) {
            $object->showResultPage = $this->showResultPage;
        }
        if (!is_null($this->tokens)) {
            $object->tokens = $this->tokens;
        }
        if (!is_null($this->validateShoppingCart)) {
            $object->validateShoppingCart = $this->validateShoppingCart;
        }
        if (!is_null($this->variant)) {
            $object->variant = $this->variant;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): HostedCheckoutSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'allowClickToPay')) {
            $this->allowClickToPay = $object->allowClickToPay;
        }
        if (property_exists($object, 'isRecurring')) {
            $this->isRecurring = $object->isRecurring;
        }
        if (property_exists($object, 'locale')) {
            $this->locale = $object->locale;
        }
        if (property_exists($object, 'paymentProductFilters')) {
            if (!is_object($object->paymentProductFilters)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProductFilters, true) . '\' is not an object');
            }
            $value = new PaymentProductFiltersHostedCheckout();
            $this->paymentProductFilters = $value->fromObject($object->paymentProductFilters);
        }
        if (property_exists($object, 'recurringPaymentsData')) {
            if (!is_object($object->recurringPaymentsData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->recurringPaymentsData, true) . '\' is not an object');
            }
            $value = new RecurringPaymentsData();
            $this->recurringPaymentsData = $value->fromObject($object->recurringPaymentsData);
        }
        if (property_exists($object, 'returnCancelState')) {
            $this->returnCancelState = $object->returnCancelState;
        }
        if (property_exists($object, 'returnUrl')) {
            $this->returnUrl = $object->returnUrl;
        }
        if (property_exists($object, 'showResultPage')) {
            $this->showResultPage = $object->showResultPage;
        }
        if (property_exists($object, 'tokens')) {
            $this->tokens = $object->tokens;
        }
        if (property_exists($object, 'validateShoppingCart')) {
            $this->validateShoppingCart = $object->validateShoppingCart;
        }
        if (property_exists($object, 'variant')) {
            $this->variant = $object->variant;
        }
        return $this;
    }
}
