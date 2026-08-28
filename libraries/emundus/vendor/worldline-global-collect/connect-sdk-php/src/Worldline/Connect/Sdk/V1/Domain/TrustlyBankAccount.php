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
class TrustlyBankAccount extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $accountLastDigits = null;

    /**
     * @var string|null
     */
    public ?string $bankName = null;

    /**
     * @var string|null
     */
    public ?string $clearinghouse = null;

    /**
     * @var string|null
     */
    public ?string $personIdentificationNumber = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->accountLastDigits)) {
            $object->accountLastDigits = $this->accountLastDigits;
        }
        if (!is_null($this->bankName)) {
            $object->bankName = $this->bankName;
        }
        if (!is_null($this->clearinghouse)) {
            $object->clearinghouse = $this->clearinghouse;
        }
        if (!is_null($this->personIdentificationNumber)) {
            $object->personIdentificationNumber = $this->personIdentificationNumber;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): TrustlyBankAccount
    {
        parent::fromObject($object);
        if (property_exists($object, 'accountLastDigits')) {
            $this->accountLastDigits = $object->accountLastDigits;
        }
        if (property_exists($object, 'bankName')) {
            $this->bankName = $object->bankName;
        }
        if (property_exists($object, 'clearinghouse')) {
            $this->clearinghouse = $object->clearinghouse;
        }
        if (property_exists($object, 'personIdentificationNumber')) {
            $this->personIdentificationNumber = $object->personIdentificationNumber;
        }
        return $this;
    }
}
