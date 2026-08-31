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
class PaymentProduct840CustomerAccount extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $accountId = null;

    /**
     * @var string|null
     */
    public ?string $billingAgreementId = null;

    /**
     * @var string|null
     */
    public ?string $companyName = null;

    /**
     * @var string|null
     */
    public ?string $contactPhone = null;

    /**
     * @var string|null
     */
    public ?string $countryCode = null;

    /**
     * @var string|null
     */
    public ?string $customerAccountStatus = null;

    /**
     * @var string|null
     */
    public ?string $customerAddressStatus = null;

    /**
     * @var string|null
     */
    public ?string $firstName = null;

    /**
     * @var string|null
     */
    public ?string $payerId = null;

    /**
     * @var string|null
     */
    public ?string $surname = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->accountId)) {
            $object->accountId = $this->accountId;
        }
        if (!is_null($this->billingAgreementId)) {
            $object->billingAgreementId = $this->billingAgreementId;
        }
        if (!is_null($this->companyName)) {
            $object->companyName = $this->companyName;
        }
        if (!is_null($this->contactPhone)) {
            $object->contactPhone = $this->contactPhone;
        }
        if (!is_null($this->countryCode)) {
            $object->countryCode = $this->countryCode;
        }
        if (!is_null($this->customerAccountStatus)) {
            $object->customerAccountStatus = $this->customerAccountStatus;
        }
        if (!is_null($this->customerAddressStatus)) {
            $object->customerAddressStatus = $this->customerAddressStatus;
        }
        if (!is_null($this->firstName)) {
            $object->firstName = $this->firstName;
        }
        if (!is_null($this->payerId)) {
            $object->payerId = $this->payerId;
        }
        if (!is_null($this->surname)) {
            $object->surname = $this->surname;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProduct840CustomerAccount
    {
        parent::fromObject($object);
        if (property_exists($object, 'accountId')) {
            $this->accountId = $object->accountId;
        }
        if (property_exists($object, 'billingAgreementId')) {
            $this->billingAgreementId = $object->billingAgreementId;
        }
        if (property_exists($object, 'companyName')) {
            $this->companyName = $object->companyName;
        }
        if (property_exists($object, 'contactPhone')) {
            $this->contactPhone = $object->contactPhone;
        }
        if (property_exists($object, 'countryCode')) {
            $this->countryCode = $object->countryCode;
        }
        if (property_exists($object, 'customerAccountStatus')) {
            $this->customerAccountStatus = $object->customerAccountStatus;
        }
        if (property_exists($object, 'customerAddressStatus')) {
            $this->customerAddressStatus = $object->customerAddressStatus;
        }
        if (property_exists($object, 'firstName')) {
            $this->firstName = $object->firstName;
        }
        if (property_exists($object, 'payerId')) {
            $this->payerId = $object->payerId;
        }
        if (property_exists($object, 'surname')) {
            $this->surname = $object->surname;
        }
        return $this;
    }
}
