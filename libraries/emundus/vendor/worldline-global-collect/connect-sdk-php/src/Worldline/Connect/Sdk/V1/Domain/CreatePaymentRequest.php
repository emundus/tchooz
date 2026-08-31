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
class CreatePaymentRequest extends DataObject
{
    /**
     * @var BankTransferPaymentMethodSpecificInput|null
     */
    public ?BankTransferPaymentMethodSpecificInput $bankTransferPaymentMethodSpecificInput = null;

    /**
     * @var CardPaymentMethodSpecificInput|null
     */
    public ?CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput = null;

    /**
     * @var CashPaymentMethodSpecificInput|null
     */
    public ?CashPaymentMethodSpecificInput $cashPaymentMethodSpecificInput = null;

    /**
     * @var NonSepaDirectDebitPaymentMethodSpecificInput|null
     */
    public ?NonSepaDirectDebitPaymentMethodSpecificInput $directDebitPaymentMethodSpecificInput = null;

    /**
     * @var EInvoicePaymentMethodSpecificInput|null
     */
    public ?EInvoicePaymentMethodSpecificInput $eInvoicePaymentMethodSpecificInput = null;

    /**
     * @var string|null
     */
    public ?string $encryptedCustomerInput = null;

    /**
     * @var FraudFields|null
     */
    public ?FraudFields $fraudFields = null;

    /**
     * @var InvoicePaymentMethodSpecificInput|null
     */
    public ?InvoicePaymentMethodSpecificInput $invoicePaymentMethodSpecificInput = null;

    /**
     * @var Merchant|null
     */
    public ?Merchant $merchant = null;

    /**
     * @var MobilePaymentMethodSpecificInput|null
     */
    public ?MobilePaymentMethodSpecificInput $mobilePaymentMethodSpecificInput = null;

    /**
     * @var Order|null
     */
    public ?Order $order = null;

    /**
     * @var RedirectPaymentMethodSpecificInput|null
     */
    public ?RedirectPaymentMethodSpecificInput $redirectPaymentMethodSpecificInput = null;

    /**
     * @var SepaDirectDebitPaymentMethodSpecificInput|null
     */
    public ?SepaDirectDebitPaymentMethodSpecificInput $sepaDirectDebitPaymentMethodSpecificInput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->bankTransferPaymentMethodSpecificInput)) {
            $object->bankTransferPaymentMethodSpecificInput = $this->bankTransferPaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->cardPaymentMethodSpecificInput)) {
            $object->cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->cashPaymentMethodSpecificInput)) {
            $object->cashPaymentMethodSpecificInput = $this->cashPaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->directDebitPaymentMethodSpecificInput)) {
            $object->directDebitPaymentMethodSpecificInput = $this->directDebitPaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->eInvoicePaymentMethodSpecificInput)) {
            $object->eInvoicePaymentMethodSpecificInput = $this->eInvoicePaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->encryptedCustomerInput)) {
            $object->encryptedCustomerInput = $this->encryptedCustomerInput;
        }
        if (!is_null($this->fraudFields)) {
            $object->fraudFields = $this->fraudFields->toObject();
        }
        if (!is_null($this->invoicePaymentMethodSpecificInput)) {
            $object->invoicePaymentMethodSpecificInput = $this->invoicePaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->merchant)) {
            $object->merchant = $this->merchant->toObject();
        }
        if (!is_null($this->mobilePaymentMethodSpecificInput)) {
            $object->mobilePaymentMethodSpecificInput = $this->mobilePaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->order)) {
            $object->order = $this->order->toObject();
        }
        if (!is_null($this->redirectPaymentMethodSpecificInput)) {
            $object->redirectPaymentMethodSpecificInput = $this->redirectPaymentMethodSpecificInput->toObject();
        }
        if (!is_null($this->sepaDirectDebitPaymentMethodSpecificInput)) {
            $object->sepaDirectDebitPaymentMethodSpecificInput = $this->sepaDirectDebitPaymentMethodSpecificInput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CreatePaymentRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'bankTransferPaymentMethodSpecificInput')) {
            if (!is_object($object->bankTransferPaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankTransferPaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new BankTransferPaymentMethodSpecificInput();
            $this->bankTransferPaymentMethodSpecificInput = $value->fromObject($object->bankTransferPaymentMethodSpecificInput);
        }
        if (property_exists($object, 'cardPaymentMethodSpecificInput')) {
            if (!is_object($object->cardPaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->cardPaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new CardPaymentMethodSpecificInput();
            $this->cardPaymentMethodSpecificInput = $value->fromObject($object->cardPaymentMethodSpecificInput);
        }
        if (property_exists($object, 'cashPaymentMethodSpecificInput')) {
            if (!is_object($object->cashPaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->cashPaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new CashPaymentMethodSpecificInput();
            $this->cashPaymentMethodSpecificInput = $value->fromObject($object->cashPaymentMethodSpecificInput);
        }
        if (property_exists($object, 'directDebitPaymentMethodSpecificInput')) {
            if (!is_object($object->directDebitPaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->directDebitPaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new NonSepaDirectDebitPaymentMethodSpecificInput();
            $this->directDebitPaymentMethodSpecificInput = $value->fromObject($object->directDebitPaymentMethodSpecificInput);
        }
        if (property_exists($object, 'eInvoicePaymentMethodSpecificInput')) {
            if (!is_object($object->eInvoicePaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->eInvoicePaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new EInvoicePaymentMethodSpecificInput();
            $this->eInvoicePaymentMethodSpecificInput = $value->fromObject($object->eInvoicePaymentMethodSpecificInput);
        }
        if (property_exists($object, 'encryptedCustomerInput')) {
            $this->encryptedCustomerInput = $object->encryptedCustomerInput;
        }
        if (property_exists($object, 'fraudFields')) {
            if (!is_object($object->fraudFields)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fraudFields, true) . '\' is not an object');
            }
            $value = new FraudFields();
            $this->fraudFields = $value->fromObject($object->fraudFields);
        }
        if (property_exists($object, 'invoicePaymentMethodSpecificInput')) {
            if (!is_object($object->invoicePaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->invoicePaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new InvoicePaymentMethodSpecificInput();
            $this->invoicePaymentMethodSpecificInput = $value->fromObject($object->invoicePaymentMethodSpecificInput);
        }
        if (property_exists($object, 'merchant')) {
            if (!is_object($object->merchant)) {
                throw new UnexpectedValueException('value \'' . print_r($object->merchant, true) . '\' is not an object');
            }
            $value = new Merchant();
            $this->merchant = $value->fromObject($object->merchant);
        }
        if (property_exists($object, 'mobilePaymentMethodSpecificInput')) {
            if (!is_object($object->mobilePaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->mobilePaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new MobilePaymentMethodSpecificInput();
            $this->mobilePaymentMethodSpecificInput = $value->fromObject($object->mobilePaymentMethodSpecificInput);
        }
        if (property_exists($object, 'order')) {
            if (!is_object($object->order)) {
                throw new UnexpectedValueException('value \'' . print_r($object->order, true) . '\' is not an object');
            }
            $value = new Order();
            $this->order = $value->fromObject($object->order);
        }
        if (property_exists($object, 'redirectPaymentMethodSpecificInput')) {
            if (!is_object($object->redirectPaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->redirectPaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new RedirectPaymentMethodSpecificInput();
            $this->redirectPaymentMethodSpecificInput = $value->fromObject($object->redirectPaymentMethodSpecificInput);
        }
        if (property_exists($object, 'sepaDirectDebitPaymentMethodSpecificInput')) {
            if (!is_object($object->sepaDirectDebitPaymentMethodSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->sepaDirectDebitPaymentMethodSpecificInput, true) . '\' is not an object');
            }
            $value = new SepaDirectDebitPaymentMethodSpecificInput();
            $this->sepaDirectDebitPaymentMethodSpecificInput = $value->fromObject($object->sepaDirectDebitPaymentMethodSpecificInput);
        }
        return $this;
    }
}
