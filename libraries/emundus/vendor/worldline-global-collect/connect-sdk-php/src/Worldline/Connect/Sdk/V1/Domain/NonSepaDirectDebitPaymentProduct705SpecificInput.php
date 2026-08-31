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
class NonSepaDirectDebitPaymentProduct705SpecificInput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $authorisationId = null;

    /**
     * @var BankAccountBban|null
     */
    public ?BankAccountBban $bankAccountBban = null;

    /**
     * @var string|null
     */
    public ?string $transactionType = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->authorisationId)) {
            $object->authorisationId = $this->authorisationId;
        }
        if (!is_null($this->bankAccountBban)) {
            $object->bankAccountBban = $this->bankAccountBban->toObject();
        }
        if (!is_null($this->transactionType)) {
            $object->transactionType = $this->transactionType;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): NonSepaDirectDebitPaymentProduct705SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'authorisationId')) {
            $this->authorisationId = $object->authorisationId;
        }
        if (property_exists($object, 'bankAccountBban')) {
            if (!is_object($object->bankAccountBban)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankAccountBban, true) . '\' is not an object');
            }
            $value = new BankAccountBban();
            $this->bankAccountBban = $value->fromObject($object->bankAccountBban);
        }
        if (property_exists($object, 'transactionType')) {
            $this->transactionType = $object->transactionType;
        }
        return $this;
    }
}
