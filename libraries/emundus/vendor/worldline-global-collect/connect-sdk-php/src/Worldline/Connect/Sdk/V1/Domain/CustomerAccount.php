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
class CustomerAccount extends DataObject
{
    /**
     * @var CustomerAccountAuthentication|null
     */
    public ?CustomerAccountAuthentication $authentication = null;

    /**
     * @var string|null
     */
    public ?string $changeDate = null;

    /**
     * @var bool|null
     */
    public ?bool $changedDuringCheckout = null;

    /**
     * @var string|null
     */
    public ?string $createDate = null;

    /**
     * @var bool|null
     */
    public ?bool $hadSuspiciousActivity = null;

    /**
     * @var bool|null
     */
    public ?bool $hasForgottenPassword = null;

    /**
     * @var bool|null
     */
    public ?bool $hasPassword = null;

    /**
     * @var string|null
     */
    public ?string $passwordChangeDate = null;

    /**
     * @var bool|null
     */
    public ?bool $passwordChangedDuringCheckout = null;

    /**
     * @var PaymentAccountOnFile|null
     */
    public ?PaymentAccountOnFile $paymentAccountOnFile = null;

    /**
     * @var string|null
     */
    public ?string $paymentAccountOnFileType = null;

    /**
     * @var CustomerPaymentActivity|null
     */
    public ?CustomerPaymentActivity $paymentActivity = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->authentication)) {
            $object->authentication = $this->authentication->toObject();
        }
        if (!is_null($this->changeDate)) {
            $object->changeDate = $this->changeDate;
        }
        if (!is_null($this->changedDuringCheckout)) {
            $object->changedDuringCheckout = $this->changedDuringCheckout;
        }
        if (!is_null($this->createDate)) {
            $object->createDate = $this->createDate;
        }
        if (!is_null($this->hadSuspiciousActivity)) {
            $object->hadSuspiciousActivity = $this->hadSuspiciousActivity;
        }
        if (!is_null($this->hasForgottenPassword)) {
            $object->hasForgottenPassword = $this->hasForgottenPassword;
        }
        if (!is_null($this->hasPassword)) {
            $object->hasPassword = $this->hasPassword;
        }
        if (!is_null($this->passwordChangeDate)) {
            $object->passwordChangeDate = $this->passwordChangeDate;
        }
        if (!is_null($this->passwordChangedDuringCheckout)) {
            $object->passwordChangedDuringCheckout = $this->passwordChangedDuringCheckout;
        }
        if (!is_null($this->paymentAccountOnFile)) {
            $object->paymentAccountOnFile = $this->paymentAccountOnFile->toObject();
        }
        if (!is_null($this->paymentAccountOnFileType)) {
            $object->paymentAccountOnFileType = $this->paymentAccountOnFileType;
        }
        if (!is_null($this->paymentActivity)) {
            $object->paymentActivity = $this->paymentActivity->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CustomerAccount
    {
        parent::fromObject($object);
        if (property_exists($object, 'authentication')) {
            if (!is_object($object->authentication)) {
                throw new UnexpectedValueException('value \'' . print_r($object->authentication, true) . '\' is not an object');
            }
            $value = new CustomerAccountAuthentication();
            $this->authentication = $value->fromObject($object->authentication);
        }
        if (property_exists($object, 'changeDate')) {
            $this->changeDate = $object->changeDate;
        }
        if (property_exists($object, 'changedDuringCheckout')) {
            $this->changedDuringCheckout = $object->changedDuringCheckout;
        }
        if (property_exists($object, 'createDate')) {
            $this->createDate = $object->createDate;
        }
        if (property_exists($object, 'hadSuspiciousActivity')) {
            $this->hadSuspiciousActivity = $object->hadSuspiciousActivity;
        }
        if (property_exists($object, 'hasForgottenPassword')) {
            $this->hasForgottenPassword = $object->hasForgottenPassword;
        }
        if (property_exists($object, 'hasPassword')) {
            $this->hasPassword = $object->hasPassword;
        }
        if (property_exists($object, 'passwordChangeDate')) {
            $this->passwordChangeDate = $object->passwordChangeDate;
        }
        if (property_exists($object, 'passwordChangedDuringCheckout')) {
            $this->passwordChangedDuringCheckout = $object->passwordChangedDuringCheckout;
        }
        if (property_exists($object, 'paymentAccountOnFile')) {
            if (!is_object($object->paymentAccountOnFile)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentAccountOnFile, true) . '\' is not an object');
            }
            $value = new PaymentAccountOnFile();
            $this->paymentAccountOnFile = $value->fromObject($object->paymentAccountOnFile);
        }
        if (property_exists($object, 'paymentAccountOnFileType')) {
            $this->paymentAccountOnFileType = $object->paymentAccountOnFileType;
        }
        if (property_exists($object, 'paymentActivity')) {
            if (!is_object($object->paymentActivity)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentActivity, true) . '\' is not an object');
            }
            $value = new CustomerPaymentActivity();
            $this->paymentActivity = $value->fromObject($object->paymentActivity);
        }
        return $this;
    }
}
