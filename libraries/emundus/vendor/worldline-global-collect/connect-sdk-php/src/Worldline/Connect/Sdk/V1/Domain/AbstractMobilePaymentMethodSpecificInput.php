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
class AbstractMobilePaymentMethodSpecificInput extends AbstractPaymentMethodSpecificInput
{
    /**
     * @var string|null
     */
    public ?string $authorizationMode = null;

    /**
     * @var string|null
     */
    public ?string $customerReference = null;

    /**
     * @var string|null
     */
    public ?string $initialSchemeTransactionId = null;

    /**
     * @var string|null
     */
    public ?string $originalTransactionLinkId = null;

    /**
     * @var CardRecurrenceDetails|null
     */
    public ?CardRecurrenceDetails $recurring = null;

    /**
     * @var bool|null
     */
    public ?bool $requiresApproval = null;

    /**
     * @var bool|null
     */
    public ?bool $skipFraudService = null;

    /**
     * @var string|null
     */
    public ?string $token = null;

    /**
     * @var bool|null
     */
    public ?bool $tokenize = null;

    /**
     * @var string|null
     */
    public ?string $unscheduledCardOnFileRequestor = null;

    /**
     * @var string|null
     */
    public ?string $unscheduledCardOnFileSequenceIndicator = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->authorizationMode)) {
            $object->authorizationMode = $this->authorizationMode;
        }
        if (!is_null($this->customerReference)) {
            $object->customerReference = $this->customerReference;
        }
        if (!is_null($this->initialSchemeTransactionId)) {
            $object->initialSchemeTransactionId = $this->initialSchemeTransactionId;
        }
        if (!is_null($this->originalTransactionLinkId)) {
            $object->originalTransactionLinkId = $this->originalTransactionLinkId;
        }
        if (!is_null($this->recurring)) {
            $object->recurring = $this->recurring->toObject();
        }
        if (!is_null($this->requiresApproval)) {
            $object->requiresApproval = $this->requiresApproval;
        }
        if (!is_null($this->skipFraudService)) {
            $object->skipFraudService = $this->skipFraudService;
        }
        if (!is_null($this->token)) {
            $object->token = $this->token;
        }
        if (!is_null($this->tokenize)) {
            $object->tokenize = $this->tokenize;
        }
        if (!is_null($this->unscheduledCardOnFileRequestor)) {
            $object->unscheduledCardOnFileRequestor = $this->unscheduledCardOnFileRequestor;
        }
        if (!is_null($this->unscheduledCardOnFileSequenceIndicator)) {
            $object->unscheduledCardOnFileSequenceIndicator = $this->unscheduledCardOnFileSequenceIndicator;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AbstractMobilePaymentMethodSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'authorizationMode')) {
            $this->authorizationMode = $object->authorizationMode;
        }
        if (property_exists($object, 'customerReference')) {
            $this->customerReference = $object->customerReference;
        }
        if (property_exists($object, 'initialSchemeTransactionId')) {
            $this->initialSchemeTransactionId = $object->initialSchemeTransactionId;
        }
        if (property_exists($object, 'originalTransactionLinkId')) {
            $this->originalTransactionLinkId = $object->originalTransactionLinkId;
        }
        if (property_exists($object, 'recurring')) {
            if (!is_object($object->recurring)) {
                throw new UnexpectedValueException('value \'' . print_r($object->recurring, true) . '\' is not an object');
            }
            $value = new CardRecurrenceDetails();
            $this->recurring = $value->fromObject($object->recurring);
        }
        if (property_exists($object, 'requiresApproval')) {
            $this->requiresApproval = $object->requiresApproval;
        }
        if (property_exists($object, 'skipFraudService')) {
            $this->skipFraudService = $object->skipFraudService;
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        if (property_exists($object, 'tokenize')) {
            $this->tokenize = $object->tokenize;
        }
        if (property_exists($object, 'unscheduledCardOnFileRequestor')) {
            $this->unscheduledCardOnFileRequestor = $object->unscheduledCardOnFileRequestor;
        }
        if (property_exists($object, 'unscheduledCardOnFileSequenceIndicator')) {
            $this->unscheduledCardOnFileSequenceIndicator = $object->unscheduledCardOnFileSequenceIndicator;
        }
        return $this;
    }
}
