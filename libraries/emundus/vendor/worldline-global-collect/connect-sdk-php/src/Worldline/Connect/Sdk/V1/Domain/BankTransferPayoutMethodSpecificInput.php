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
class BankTransferPayoutMethodSpecificInput extends AbstractPayoutMethodSpecificInput
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
     * @var PayoutCustomer|null
     *
     * @deprecated Moved to PayoutDetails
     */
    public ?PayoutCustomer $customer = null;

    /**
     * @var string|null
     */
    public ?string $payoutDate = null;

    /**
     * @var string|null
     */
    public ?string $payoutText = null;

    /**
     * @var string|null
     */
    public ?string $swiftCode = null;

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
        if (!is_null($this->customer)) {
            $object->customer = $this->customer->toObject();
        }
        if (!is_null($this->payoutDate)) {
            $object->payoutDate = $this->payoutDate;
        }
        if (!is_null($this->payoutText)) {
            $object->payoutText = $this->payoutText;
        }
        if (!is_null($this->swiftCode)) {
            $object->swiftCode = $this->swiftCode;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): BankTransferPayoutMethodSpecificInput
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
        if (property_exists($object, 'customer')) {
            if (!is_object($object->customer)) {
                throw new UnexpectedValueException('value \'' . print_r($object->customer, true) . '\' is not an object');
            }
            $value = new PayoutCustomer();
            $this->customer = $value->fromObject($object->customer);
        }
        if (property_exists($object, 'payoutDate')) {
            $this->payoutDate = $object->payoutDate;
        }
        if (property_exists($object, 'payoutText')) {
            $this->payoutText = $object->payoutText;
        }
        if (property_exists($object, 'swiftCode')) {
            $this->swiftCode = $object->swiftCode;
        }
        return $this;
    }
}
