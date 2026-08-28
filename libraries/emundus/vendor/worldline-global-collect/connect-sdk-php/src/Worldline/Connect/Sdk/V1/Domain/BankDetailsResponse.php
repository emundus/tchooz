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
class BankDetailsResponse extends DataObject
{
    /**
     * @var BankAccountBban|null
     */
    public ?BankAccountBban $bankAccountBban = null;

    /**
     * @var BankAccountIban|null
     */
    public ?BankAccountIban $bankAccountIban = null;

    /**
     * @var BankData|null
     */
    public ?BankData $bankData = null;

    /**
     * @var Swift|null
     */
    public ?Swift $swift = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->bankAccountBban)) {
            $object->bankAccountBban = $this->bankAccountBban->toObject();
        }
        if (!is_null($this->bankAccountIban)) {
            $object->bankAccountIban = $this->bankAccountIban->toObject();
        }
        if (!is_null($this->bankData)) {
            $object->bankData = $this->bankData->toObject();
        }
        if (!is_null($this->swift)) {
            $object->swift = $this->swift->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): BankDetailsResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'bankAccountBban')) {
            if (!is_object($object->bankAccountBban)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankAccountBban, true) . '\' is not an object');
            }
            $value = new BankAccountBban();
            $this->bankAccountBban = $value->fromObject($object->bankAccountBban);
        }
        if (property_exists($object, 'bankAccountIban')) {
            if (!is_object($object->bankAccountIban)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankAccountIban, true) . '\' is not an object');
            }
            $value = new BankAccountIban();
            $this->bankAccountIban = $value->fromObject($object->bankAccountIban);
        }
        if (property_exists($object, 'bankData')) {
            if (!is_object($object->bankData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankData, true) . '\' is not an object');
            }
            $value = new BankData();
            $this->bankData = $value->fromObject($object->bankData);
        }
        if (property_exists($object, 'swift')) {
            if (!is_object($object->swift)) {
                throw new UnexpectedValueException('value \'' . print_r($object->swift, true) . '\' is not an object');
            }
            $value = new Swift();
            $this->swift = $value->fromObject($object->swift);
        }
        return $this;
    }
}
