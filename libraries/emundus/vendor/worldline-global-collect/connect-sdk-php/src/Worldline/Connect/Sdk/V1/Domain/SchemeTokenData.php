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
class SchemeTokenData extends DataObject
{
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
    public ?string $eci = null;

    /**
     * @var string|null
     */
    public ?string $networkToken = null;

    /**
     * @var string|null
     */
    public ?string $tokenExpiryDate = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->cardholderName)) {
            $object->cardholderName = $this->cardholderName;
        }
        if (!is_null($this->cryptogram)) {
            $object->cryptogram = $this->cryptogram;
        }
        if (!is_null($this->eci)) {
            $object->eci = $this->eci;
        }
        if (!is_null($this->networkToken)) {
            $object->networkToken = $this->networkToken;
        }
        if (!is_null($this->tokenExpiryDate)) {
            $object->tokenExpiryDate = $this->tokenExpiryDate;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): SchemeTokenData
    {
        parent::fromObject($object);
        if (property_exists($object, 'cardholderName')) {
            $this->cardholderName = $object->cardholderName;
        }
        if (property_exists($object, 'cryptogram')) {
            $this->cryptogram = $object->cryptogram;
        }
        if (property_exists($object, 'eci')) {
            $this->eci = $object->eci;
        }
        if (property_exists($object, 'networkToken')) {
            $this->networkToken = $object->networkToken;
        }
        if (property_exists($object, 'tokenExpiryDate')) {
            $this->tokenExpiryDate = $object->tokenExpiryDate;
        }
        return $this;
    }
}
