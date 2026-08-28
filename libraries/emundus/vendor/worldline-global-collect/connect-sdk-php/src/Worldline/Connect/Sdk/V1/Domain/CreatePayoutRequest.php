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
class CreatePayoutRequest extends DataObject
{
    /**
     * @var AmountOfMoney|null
     *
     * @deprecated Moved to PayoutDetails
     */
    public ?AmountOfMoney $amountOfMoney = null;

    /**
     * @var BankAccountBban|null
     *
     * @deprecated Moved to BankTransferPayoutMethodSpecificInput
     */
    public ?BankAccountBban $bankAccountBban = null;

    /**
     * @var BankAccountIban|null
     *
     * @deprecated Moved to BankTransferPayoutMethodSpecificInput
     */
    public ?BankAccountIban $bankAccountIban = null;

    /**
     * @var BankTransferPayoutMethodSpecificInput|null
     */
    public ?BankTransferPayoutMethodSpecificInput $bankTransferPayoutMethodSpecificInput = null;

    /**
     * @var CardPayoutMethodSpecificInput|null
     */
    public ?CardPayoutMethodSpecificInput $cardPayoutMethodSpecificInput = null;

    /**
     * @var PayoutCustomer|null
     *
     * @deprecated Moved to PayoutDetails
     */
    public ?PayoutCustomer $customer = null;

    /**
     * @var PayoutMerchant|null
     */
    public ?PayoutMerchant $merchant = null;

    /**
     * @var string|null
     *
     * @deprecated Moved to BankTransferPayoutMethodSpecificInput
     */
    public ?string $payoutDate = null;

    /**
     * @var PayoutDetails|null
     */
    public ?PayoutDetails $payoutDetails = null;

    /**
     * @var string|null
     *
     * @deprecated Moved to BankTransferPayoutMethodSpecificInput
     */
    public ?string $payoutText = null;

    /**
     * @var PayoutReferences|null
     *
     * @deprecated Moved to PayoutDetails
     */
    public ?PayoutReferences $references = null;

    /**
     * @var string|null
     *
     * @deprecated Moved to BankTransferPayoutMethodSpecificInput
     */
    public ?string $swiftCode = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->amountOfMoney)) {
            $object->amountOfMoney = $this->amountOfMoney->toObject();
        }
        if (!is_null($this->bankAccountBban)) {
            $object->bankAccountBban = $this->bankAccountBban->toObject();
        }
        if (!is_null($this->bankAccountIban)) {
            $object->bankAccountIban = $this->bankAccountIban->toObject();
        }
        if (!is_null($this->bankTransferPayoutMethodSpecificInput)) {
            $object->bankTransferPayoutMethodSpecificInput = $this->bankTransferPayoutMethodSpecificInput->toObject();
        }
        if (!is_null($this->cardPayoutMethodSpecificInput)) {
            $object->cardPayoutMethodSpecificInput = $this->cardPayoutMethodSpecificInput->toObject();
        }
        if (!is_null($this->customer)) {
            $object->customer = $this->customer->toObject();
        }
        if (!is_null($this->merchant)) {
            $object->merchant = $this->merchant->toObject();
        }
        if (!is_null($this->payoutDate)) {
            $object->payoutDate = $this->payoutDate;
        }
        if (!is_null($this->payoutDetails)) {
            $object->payoutDetails = $this->payoutDetails->toObject();
        }
        if (!is_null($this->payoutText)) {
            $object->payoutText = $this->payoutText;
        }
        if (!is_null($this->references)) {
            $object->references = $this->references->toObject();
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
    public function fromObject(object $object): CreatePayoutRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'amountOfMoney')) {
            if (!is_object($object->amountOfMoney)) {
                throw new UnexpectedValueException('value \'' . print_r($object->amountOfMoney, true) . '\' is not an object');
            }
            $value = new AmountOfMoney();
            $this->amountOfMoney = $value->fromObject($object->amountOfMoney);
        }
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
        if (property_exists($object, 'bankTransferPayoutMethodSpecificInput')) {
            if (!is_object($object->bankTransferPayoutMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankTransferPayoutMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new BankTransferPayoutMethodSpecificInput();
            $this->bankTransferPayoutMethodSpecificInput = $value->fromObject($object->bankTransferPayoutMethodSpecificInput);
        }
        if (property_exists($object, 'cardPayoutMethodSpecificInput')) {
            if (!is_object($object->cardPayoutMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->cardPayoutMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new CardPayoutMethodSpecificInput();
            $this->cardPayoutMethodSpecificInput = $value->fromObject($object->cardPayoutMethodSpecificInput);
        }
        if (property_exists($object, 'customer')) {
            if (!is_object($object->customer)) {
                throw new UnexpectedValueException('value \'' . print_r($object->customer, true) . '\' is not an object');
            }
            $value = new PayoutCustomer();
            $this->customer = $value->fromObject($object->customer);
        }
        if (property_exists($object, 'merchant')) {
            if (!is_object($object->merchant)) {
                throw new UnexpectedValueException('value \'' . print_r($object->merchant, true) . '\' is not an object');
            }
            $value = new PayoutMerchant();
            $this->merchant = $value->fromObject($object->merchant);
        }
        if (property_exists($object, 'payoutDate')) {
            $this->payoutDate = $object->payoutDate;
        }
        if (property_exists($object, 'payoutDetails')) {
            if (!is_object($object->payoutDetails)) {
                throw new UnexpectedValueException('value \'' . print_r($object->payoutDetails, true) . '\' is not an object');
            }
            $value = new PayoutDetails();
            $this->payoutDetails = $value->fromObject($object->payoutDetails);
        }
        if (property_exists($object, 'payoutText')) {
            $this->payoutText = $object->payoutText;
        }
        if (property_exists($object, 'references')) {
            if (!is_object($object->references)) {
                throw new UnexpectedValueException('value \'' . print_r($object->references, true) . '\' is not an object');
            }
            $value = new PayoutReferences();
            $this->references = $value->fromObject($object->references);
        }
        if (property_exists($object, 'swiftCode')) {
            $this->swiftCode = $object->swiftCode;
        }
        return $this;
    }
}
