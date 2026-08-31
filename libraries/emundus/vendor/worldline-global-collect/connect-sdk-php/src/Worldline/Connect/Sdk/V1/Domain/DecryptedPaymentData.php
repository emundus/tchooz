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
class DecryptedPaymentData extends DataObject
{
    /**
     * @var string|null
     *
     * @deprecated Use decryptedPaymentData.paymentMethod instead
     */
    public ?string $authMethod = null;

    /**
     * @var string|null
     */
    public ?string $cardholderName = null;

    /**
     * @var string|null
     */
    public ?string $cryptogram = null;

    /**
     * @var string|null
     */
    public ?string $dpan = null;

    /**
     * @var int|null
     */
    public ?int $eci = null;

    /**
     * @var string|null
     */
    public ?string $expiryDate = null;

    /**
     * @var string|null
     */
    public ?string $pan = null;

    /**
     * @var string|null
     */
    public ?string $paymentMethod = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->authMethod)) {
            $object->authMethod = $this->authMethod;
        }
        if (!is_null($this->cardholderName)) {
            $object->cardholderName = $this->cardholderName;
        }
        if (!is_null($this->cryptogram)) {
            $object->cryptogram = $this->cryptogram;
        }
        if (!is_null($this->dpan)) {
            $object->dpan = $this->dpan;
        }
        if (!is_null($this->eci)) {
            $object->eci = $this->eci;
        }
        if (!is_null($this->expiryDate)) {
            $object->expiryDate = $this->expiryDate;
        }
        if (!is_null($this->pan)) {
            $object->pan = $this->pan;
        }
        if (!is_null($this->paymentMethod)) {
            $object->paymentMethod = $this->paymentMethod;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DecryptedPaymentData
    {
        parent::fromObject($object);
        if (property_exists($object, 'authMethod')) {
            $this->authMethod = $object->authMethod;
        }
        if (property_exists($object, 'cardholderName')) {
            $this->cardholderName = $object->cardholderName;
        }
        if (property_exists($object, 'cryptogram')) {
            $this->cryptogram = $object->cryptogram;
        }
        if (property_exists($object, 'dpan')) {
            $this->dpan = $object->dpan;
        }
        if (property_exists($object, 'eci')) {
            $this->eci = $object->eci;
        }
        if (property_exists($object, 'expiryDate')) {
            $this->expiryDate = $object->expiryDate;
        }
        if (property_exists($object, 'pan')) {
            $this->pan = $object->pan;
        }
        if (property_exists($object, 'paymentMethod')) {
            $this->paymentMethod = $object->paymentMethod;
        }
        return $this;
    }
}
