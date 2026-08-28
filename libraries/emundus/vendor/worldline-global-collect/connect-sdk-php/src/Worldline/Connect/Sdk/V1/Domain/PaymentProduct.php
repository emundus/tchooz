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
class PaymentProduct extends DataObject
{
    /**
     * @var AccountOnFile[]|null
     */
    public ?array $accountsOnFile = null;

    /**
     * @var string|null
     */
    public ?string $acquirerCountry = null;

    /**
     * @var bool|null
     */
    public ?bool $allowsClickToPay = null;

    /**
     * @var bool|null
     */
    public ?bool $allowsInstallments = null;

    /**
     * @var bool|null
     */
    public ?bool $allowsMultiplePartialCaptures = null;

    /**
     * @var bool|null
     */
    public ?bool $allowsRecurring = null;

    /**
     * @var bool|null
     */
    public ?bool $allowsTokenization = null;

    /**
     * @var AuthenticationIndicator|null
     */
    public ?AuthenticationIndicator $authenticationIndicator = null;

    /**
     * @var bool|null
     */
    public ?bool $autoTokenized = null;

    /**
     * @var bool|null
     */
    public ?bool $canBeIframed = null;

    /**
     * @var ClickToPayConfiguration|null
     */
    public ?ClickToPayConfiguration $clickToPayConfiguration = null;

    /**
     * @var bool|null
     */
    public ?bool $deviceFingerprintEnabled = null;

    /**
     * @var PaymentProductDisplayHints|null
     */
    public ?PaymentProductDisplayHints $displayHints = null;

    /**
     * @var PaymentProductField[]|null
     */
    public ?array $fields = null;

    /**
     * @var string|null
     */
    public ?string $fieldsWarning = null;

    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var bool|null
     */
    public ?bool $isAuthenticationSupported = null;

    /**
     * @var bool|null
     */
    public ?bool $isJavaScriptRequired = null;

    /**
     * @var int|null
     */
    public ?int $maxAmount = null;

    /**
     * @var int|null
     */
    public ?int $minAmount = null;

    /**
     * @var string|null
     */
    public ?string $mobileIntegrationLevel = null;

    /**
     * @var string|null
     */
    public ?string $paymentMethod = null;

    /**
     * @var PaymentProduct302SpecificData|null
     */
    public ?PaymentProduct302SpecificData $paymentProduct302SpecificData = null;

    /**
     * @var PaymentProduct320SpecificData|null
     */
    public ?PaymentProduct320SpecificData $paymentProduct320SpecificData = null;

    /**
     * @var PaymentProduct863SpecificData|null
     */
    public ?PaymentProduct863SpecificData $paymentProduct863SpecificData = null;

    /**
     * @var string|null
     */
    public ?string $paymentProductGroup = null;

    /**
     * @var bool|null
     */
    public ?bool $supportsMandates = null;

    /**
     * @var bool|null
     */
    public ?bool $usesRedirectionTo3rdParty = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->accountsOnFile)) {
            $object->accountsOnFile = [];
            foreach ($this->accountsOnFile as $element) {
                if (!is_null($element)) {
                    $object->accountsOnFile[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->acquirerCountry)) {
            $object->acquirerCountry = $this->acquirerCountry;
        }
        if (!is_null($this->allowsClickToPay)) {
            $object->allowsClickToPay = $this->allowsClickToPay;
        }
        if (!is_null($this->allowsInstallments)) {
            $object->allowsInstallments = $this->allowsInstallments;
        }
        if (!is_null($this->allowsMultiplePartialCaptures)) {
            $object->allowsMultiplePartialCaptures = $this->allowsMultiplePartialCaptures;
        }
        if (!is_null($this->allowsRecurring)) {
            $object->allowsRecurring = $this->allowsRecurring;
        }
        if (!is_null($this->allowsTokenization)) {
            $object->allowsTokenization = $this->allowsTokenization;
        }
        if (!is_null($this->authenticationIndicator)) {
            $object->authenticationIndicator = $this->authenticationIndicator->toObject();
        }
        if (!is_null($this->autoTokenized)) {
            $object->autoTokenized = $this->autoTokenized;
        }
        if (!is_null($this->canBeIframed)) {
            $object->canBeIframed = $this->canBeIframed;
        }
        if (!is_null($this->clickToPayConfiguration)) {
            $object->clickToPayConfiguration = $this->clickToPayConfiguration->toObject();
        }
        if (!is_null($this->deviceFingerprintEnabled)) {
            $object->deviceFingerprintEnabled = $this->deviceFingerprintEnabled;
        }
        if (!is_null($this->displayHints)) {
            $object->displayHints = $this->displayHints->toObject();
        }
        if (!is_null($this->fields)) {
            $object->fields = [];
            foreach ($this->fields as $element) {
                if (!is_null($element)) {
                    $object->fields[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->fieldsWarning)) {
            $object->fieldsWarning = $this->fieldsWarning;
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        if (!is_null($this->isAuthenticationSupported)) {
            $object->isAuthenticationSupported = $this->isAuthenticationSupported;
        }
        if (!is_null($this->isJavaScriptRequired)) {
            $object->isJavaScriptRequired = $this->isJavaScriptRequired;
        }
        if (!is_null($this->maxAmount)) {
            $object->maxAmount = $this->maxAmount;
        }
        if (!is_null($this->minAmount)) {
            $object->minAmount = $this->minAmount;
        }
        if (!is_null($this->mobileIntegrationLevel)) {
            $object->mobileIntegrationLevel = $this->mobileIntegrationLevel;
        }
        if (!is_null($this->paymentMethod)) {
            $object->paymentMethod = $this->paymentMethod;
        }
        if (!is_null($this->paymentProduct302SpecificData)) {
            $object->paymentProduct302SpecificData = $this->paymentProduct302SpecificData->toObject();
        }
        if (!is_null($this->paymentProduct320SpecificData)) {
            $object->paymentProduct320SpecificData = $this->paymentProduct320SpecificData->toObject();
        }
        if (!is_null($this->paymentProduct863SpecificData)) {
            $object->paymentProduct863SpecificData = $this->paymentProduct863SpecificData->toObject();
        }
        if (!is_null($this->paymentProductGroup)) {
            $object->paymentProductGroup = $this->paymentProductGroup;
        }
        if (!is_null($this->supportsMandates)) {
            $object->supportsMandates = $this->supportsMandates;
        }
        if (!is_null($this->usesRedirectionTo3rdParty)) {
            $object->usesRedirectionTo3rdParty = $this->usesRedirectionTo3rdParty;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProduct
    {
        parent::fromObject($object);
        if (property_exists($object, 'accountsOnFile')) {
            if (!is_array($object->accountsOnFile) && !is_object($object->accountsOnFile)) {
                throw new UnexpectedValueException('value \'' . print_r($object->accountsOnFile, true) . '\' is not an array or object');
            }
            $this->accountsOnFile = [];
            foreach ($object->accountsOnFile as $element) {
                $value = new AccountOnFile();
                $this->accountsOnFile[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'acquirerCountry')) {
            $this->acquirerCountry = $object->acquirerCountry;
        }
        if (property_exists($object, 'allowsClickToPay')) {
            $this->allowsClickToPay = $object->allowsClickToPay;
        }
        if (property_exists($object, 'allowsInstallments')) {
            $this->allowsInstallments = $object->allowsInstallments;
        }
        if (property_exists($object, 'allowsMultiplePartialCaptures')) {
            $this->allowsMultiplePartialCaptures = $object->allowsMultiplePartialCaptures;
        }
        if (property_exists($object, 'allowsRecurring')) {
            $this->allowsRecurring = $object->allowsRecurring;
        }
        if (property_exists($object, 'allowsTokenization')) {
            $this->allowsTokenization = $object->allowsTokenization;
        }
        if (property_exists($object, 'authenticationIndicator')) {
            if (!is_object($object->authenticationIndicator)) {
                throw new UnexpectedValueException('value \'' . print_r($object->authenticationIndicator, true) . '\' is not an object');
            }
            $value = new AuthenticationIndicator();
            $this->authenticationIndicator = $value->fromObject($object->authenticationIndicator);
        }
        if (property_exists($object, 'autoTokenized')) {
            $this->autoTokenized = $object->autoTokenized;
        }
        if (property_exists($object, 'canBeIframed')) {
            $this->canBeIframed = $object->canBeIframed;
        }
        if (property_exists($object, 'clickToPayConfiguration')) {
            if (!is_object($object->clickToPayConfiguration)) {
                throw new UnexpectedValueException('value \'' . print_r($object->clickToPayConfiguration, true) . '\' is not an object');
            }
            $value = new ClickToPayConfiguration();
            $this->clickToPayConfiguration = $value->fromObject($object->clickToPayConfiguration);
        }
        if (property_exists($object, 'deviceFingerprintEnabled')) {
            $this->deviceFingerprintEnabled = $object->deviceFingerprintEnabled;
        }
        if (property_exists($object, 'displayHints')) {
            if (!is_object($object->displayHints)) {
                throw new UnexpectedValueException('value \'' . print_r($object->displayHints, true) . '\' is not an object');
            }
            $value = new PaymentProductDisplayHints();
            $this->displayHints = $value->fromObject($object->displayHints);
        }
        if (property_exists($object, 'fields')) {
            if (!is_array($object->fields) && !is_object($object->fields)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fields, true) . '\' is not an array or object');
            }
            $this->fields = [];
            foreach ($object->fields as $element) {
                $value = new PaymentProductField();
                $this->fields[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'fieldsWarning')) {
            $this->fieldsWarning = $object->fieldsWarning;
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        if (property_exists($object, 'isAuthenticationSupported')) {
            $this->isAuthenticationSupported = $object->isAuthenticationSupported;
        }
        if (property_exists($object, 'isJavaScriptRequired')) {
            $this->isJavaScriptRequired = $object->isJavaScriptRequired;
        }
        if (property_exists($object, 'maxAmount')) {
            $this->maxAmount = $object->maxAmount;
        }
        if (property_exists($object, 'minAmount')) {
            $this->minAmount = $object->minAmount;
        }
        if (property_exists($object, 'mobileIntegrationLevel')) {
            $this->mobileIntegrationLevel = $object->mobileIntegrationLevel;
        }
        if (property_exists($object, 'paymentMethod')) {
            $this->paymentMethod = $object->paymentMethod;
        }
        if (property_exists($object, 'paymentProduct302SpecificData')) {
            if (!is_object($object->paymentProduct302SpecificData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct302SpecificData, true) . '\' is not an object');
            }
            $value = new PaymentProduct302SpecificData();
            $this->paymentProduct302SpecificData = $value->fromObject($object->paymentProduct302SpecificData);
        }
        if (property_exists($object, 'paymentProduct320SpecificData')) {
            if (!is_object($object->paymentProduct320SpecificData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct320SpecificData, true) . '\' is not an object');
            }
            $value = new PaymentProduct320SpecificData();
            $this->paymentProduct320SpecificData = $value->fromObject($object->paymentProduct320SpecificData);
        }
        if (property_exists($object, 'paymentProduct863SpecificData')) {
            if (!is_object($object->paymentProduct863SpecificData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct863SpecificData, true) . '\' is not an object');
            }
            $value = new PaymentProduct863SpecificData();
            $this->paymentProduct863SpecificData = $value->fromObject($object->paymentProduct863SpecificData);
        }
        if (property_exists($object, 'paymentProductGroup')) {
            $this->paymentProductGroup = $object->paymentProductGroup;
        }
        if (property_exists($object, 'supportsMandates')) {
            $this->supportsMandates = $object->supportsMandates;
        }
        if (property_exists($object, 'usesRedirectionTo3rdParty')) {
            $this->usesRedirectionTo3rdParty = $object->usesRedirectionTo3rdParty;
        }
        return $this;
    }
}
