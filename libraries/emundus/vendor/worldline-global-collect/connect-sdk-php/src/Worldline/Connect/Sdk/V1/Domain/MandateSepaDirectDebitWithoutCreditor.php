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
class MandateSepaDirectDebitWithoutCreditor extends DataObject
{
    /**
     * @var BankAccountIban|null
     */
    public ?BankAccountIban $bankAccountIban = null;

    /**
     * @var string|null
     */
    public ?string $customerContractIdentifier = null;

    /**
     * @var Debtor|null
     */
    public ?Debtor $debtor = null;

    /**
     * @var bool|null
     */
    public ?bool $isRecurring = null;

    /**
     * @var MandateApproval|null
     */
    public ?MandateApproval $mandateApproval = null;

    /**
     * @var string|null
     */
    public ?string $preNotification = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->bankAccountIban)) {
            $object->bankAccountIban = $this->bankAccountIban->toObject();
        }
        if (!is_null($this->customerContractIdentifier)) {
            $object->customerContractIdentifier = $this->customerContractIdentifier;
        }
        if (!is_null($this->debtor)) {
            $object->debtor = $this->debtor->toObject();
        }
        if (!is_null($this->isRecurring)) {
            $object->isRecurring = $this->isRecurring;
        }
        if (!is_null($this->mandateApproval)) {
            $object->mandateApproval = $this->mandateApproval->toObject();
        }
        if (!is_null($this->preNotification)) {
            $object->preNotification = $this->preNotification;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MandateSepaDirectDebitWithoutCreditor
    {
        parent::fromObject($object);
        if (property_exists($object, 'bankAccountIban')) {
            if (!is_object($object->bankAccountIban)) {
                throw new UnexpectedValueException('value \'' . print_r($object->bankAccountIban, true) . '\' is not an object');
            }
            $value = new BankAccountIban();
            $this->bankAccountIban = $value->fromObject($object->bankAccountIban);
        }
        if (property_exists($object, 'customerContractIdentifier')) {
            $this->customerContractIdentifier = $object->customerContractIdentifier;
        }
        if (property_exists($object, 'debtor')) {
            if (!is_object($object->debtor)) {
                throw new UnexpectedValueException('value \'' . print_r($object->debtor, true) . '\' is not an object');
            }
            $value = new Debtor();
            $this->debtor = $value->fromObject($object->debtor);
        }
        if (property_exists($object, 'isRecurring')) {
            $this->isRecurring = $object->isRecurring;
        }
        if (property_exists($object, 'mandateApproval')) {
            if (!is_object($object->mandateApproval)) {
                throw new UnexpectedValueException('value \'' . print_r($object->mandateApproval, true) . '\' is not an object');
            }
            $value = new MandateApproval();
            $this->mandateApproval = $value->fromObject($object->mandateApproval);
        }
        if (property_exists($object, 'preNotification')) {
            $this->preNotification = $object->preNotification;
        }
        return $this;
    }
}
