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
class TokenResponse extends DataObject
{
    /**
     * @var TokenCard|null
     */
    public ?TokenCard $card = null;

    /**
     * @var TokenEWallet|null
     */
    public ?TokenEWallet $eWallet = null;

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var TokenNonSepaDirectDebit|null
     */
    public ?TokenNonSepaDirectDebit $nonSepaDirectDebit = null;

    /**
     * @var string|null
     */
    public ?string $originalPaymentId = null;

    /**
     * @var int|null
     */
    public ?int $paymentProductId = null;

    /**
     * @var TokenSepaDirectDebit|null
     */
    public ?TokenSepaDirectDebit $sepaDirectDebit = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->card)) {
            $object->card = $this->card->toObject();
        }
        if (!is_null($this->eWallet)) {
            $object->eWallet = $this->eWallet->toObject();
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        if (!is_null($this->nonSepaDirectDebit)) {
            $object->nonSepaDirectDebit = $this->nonSepaDirectDebit->toObject();
        }
        if (!is_null($this->originalPaymentId)) {
            $object->originalPaymentId = $this->originalPaymentId;
        }
        if (!is_null($this->paymentProductId)) {
            $object->paymentProductId = $this->paymentProductId;
        }
        if (!is_null($this->sepaDirectDebit)) {
            $object->sepaDirectDebit = $this->sepaDirectDebit->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): TokenResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'card')) {
            if (!is_object($object->card)) {
                throw new UnexpectedValueException('value \'' . print_r($object->card, true) . '\' is not an object');
            }
            $value = new TokenCard();
            $this->card = $value->fromObject($object->card);
        }
        if (property_exists($object, 'eWallet')) {
            if (!is_object($object->eWallet)) {
                throw new UnexpectedValueException('value \'' . print_r($object->eWallet, true) . '\' is not an object');
            }
            $value = new TokenEWallet();
            $this->eWallet = $value->fromObject($object->eWallet);
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        if (property_exists($object, 'nonSepaDirectDebit')) {
            if (!is_object($object->nonSepaDirectDebit)) {
                throw new UnexpectedValueException('value \'' . print_r($object->nonSepaDirectDebit, true) . '\' is not an object');
            }
            $value = new TokenNonSepaDirectDebit();
            $this->nonSepaDirectDebit = $value->fromObject($object->nonSepaDirectDebit);
        }
        if (property_exists($object, 'originalPaymentId')) {
            $this->originalPaymentId = $object->originalPaymentId;
        }
        if (property_exists($object, 'paymentProductId')) {
            $this->paymentProductId = $object->paymentProductId;
        }
        if (property_exists($object, 'sepaDirectDebit')) {
            if (!is_object($object->sepaDirectDebit)) {
                throw new UnexpectedValueException('value \'' . print_r($object->sepaDirectDebit, true) . '\' is not an object');
            }
            $value = new TokenSepaDirectDebit();
            $this->sepaDirectDebit = $value->fromObject($object->sepaDirectDebit);
        }
        return $this;
    }
}
