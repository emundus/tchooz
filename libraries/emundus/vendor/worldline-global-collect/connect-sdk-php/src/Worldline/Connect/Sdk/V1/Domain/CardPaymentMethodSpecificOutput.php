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
class CardPaymentMethodSpecificOutput extends AbstractPaymentMethodSpecificOutput
{
    /**
     * @var string|null
     */
    public ?string $authorisationCode = null;

    /**
     * @var CardEssentials|null
     */
    public ?CardEssentials $card = null;

    /**
     * @var bool|null
     */
    public ?bool $clickToPayUsed = null;

    /**
     * @var CardFraudResults|null
     */
    public ?CardFraudResults $fraudResults = null;

    /**
     * @var string|null
     */
    public ?string $initialSchemeTransactionId = null;

    /**
     * @var NetworkTokenData|null
     */
    public ?NetworkTokenData $networkTokenData = null;

    /**
     * @var bool|null
     */
    public ?bool $networkTokenUsed = null;

    /**
     * @var string|null
     */
    public ?string $originalTransactionLinkId = null;

    /**
     * @var string|null
     */
    public ?string $paymentAccountReference = null;

    /**
     * @var string|null
     */
    public ?string $schemeTransactionId = null;

    /**
     * @var ThreeDSecureResults|null
     */
    public ?ThreeDSecureResults $threeDSecureResults = null;

    /**
     * @var string|null
     */
    public ?string $token = null;

    /**
     * @var string|null
     */
    public ?string $transactionLinkId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->authorisationCode)) {
            $object->authorisationCode = $this->authorisationCode;
        }
        if (!is_null($this->card)) {
            $object->card = $this->card->toObject();
        }
        if (!is_null($this->clickToPayUsed)) {
            $object->clickToPayUsed = $this->clickToPayUsed;
        }
        if (!is_null($this->fraudResults)) {
            $object->fraudResults = $this->fraudResults->toObject();
        }
        if (!is_null($this->initialSchemeTransactionId)) {
            $object->initialSchemeTransactionId = $this->initialSchemeTransactionId;
        }
        if (!is_null($this->networkTokenData)) {
            $object->networkTokenData = $this->networkTokenData->toObject();
        }
        if (!is_null($this->networkTokenUsed)) {
            $object->networkTokenUsed = $this->networkTokenUsed;
        }
        if (!is_null($this->originalTransactionLinkId)) {
            $object->originalTransactionLinkId = $this->originalTransactionLinkId;
        }
        if (!is_null($this->paymentAccountReference)) {
            $object->paymentAccountReference = $this->paymentAccountReference;
        }
        if (!is_null($this->schemeTransactionId)) {
            $object->schemeTransactionId = $this->schemeTransactionId;
        }
        if (!is_null($this->threeDSecureResults)) {
            $object->threeDSecureResults = $this->threeDSecureResults->toObject();
        }
        if (!is_null($this->token)) {
            $object->token = $this->token;
        }
        if (!is_null($this->transactionLinkId)) {
            $object->transactionLinkId = $this->transactionLinkId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CardPaymentMethodSpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'authorisationCode')) {
            $this->authorisationCode = $object->authorisationCode;
        }
        if (property_exists($object, 'card')) {
            if (!is_object($object->card)) {
                throw new UnexpectedValueException('value \'' . print_r($object->card, true) . '\' is not an object');
            }
            $value = new CardEssentials();
            $this->card = $value->fromObject($object->card);
        }
        if (property_exists($object, 'clickToPayUsed')) {
            $this->clickToPayUsed = $object->clickToPayUsed;
        }
        if (property_exists($object, 'fraudResults')) {
            if (!is_object($object->fraudResults)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fraudResults, true) . '\' is not an object');
            }
            $value = new CardFraudResults();
            $this->fraudResults = $value->fromObject($object->fraudResults);
        }
        if (property_exists($object, 'initialSchemeTransactionId')) {
            $this->initialSchemeTransactionId = $object->initialSchemeTransactionId;
        }
        if (property_exists($object, 'networkTokenData')) {
            if (!is_object($object->networkTokenData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->networkTokenData, true) . '\' is not an object');
            }
            $value = new NetworkTokenData();
            $this->networkTokenData = $value->fromObject($object->networkTokenData);
        }
        if (property_exists($object, 'networkTokenUsed')) {
            $this->networkTokenUsed = $object->networkTokenUsed;
        }
        if (property_exists($object, 'originalTransactionLinkId')) {
            $this->originalTransactionLinkId = $object->originalTransactionLinkId;
        }
        if (property_exists($object, 'paymentAccountReference')) {
            $this->paymentAccountReference = $object->paymentAccountReference;
        }
        if (property_exists($object, 'schemeTransactionId')) {
            $this->schemeTransactionId = $object->schemeTransactionId;
        }
        if (property_exists($object, 'threeDSecureResults')) {
            if (!is_object($object->threeDSecureResults)) {
                throw new UnexpectedValueException('value \'' . print_r($object->threeDSecureResults, true) . '\' is not an object');
            }
            $value = new ThreeDSecureResults();
            $this->threeDSecureResults = $value->fromObject($object->threeDSecureResults);
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        if (property_exists($object, 'transactionLinkId')) {
            $this->transactionLinkId = $object->transactionLinkId;
        }
        return $this;
    }
}
