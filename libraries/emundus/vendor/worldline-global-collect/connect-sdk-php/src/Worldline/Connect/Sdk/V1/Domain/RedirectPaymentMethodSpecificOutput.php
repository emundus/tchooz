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
class RedirectPaymentMethodSpecificOutput extends AbstractPaymentMethodSpecificOutput
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
     * @var string|null
     */
    public ?string $bic = null;

    /**
     * @var FraudResults|null
     */
    public ?FraudResults $fraudResults = null;

    /**
     * @var PaymentProduct3201SpecificOutput|null
     */
    public ?PaymentProduct3201SpecificOutput $paymentProduct3201SpecificOutput = null;

    /**
     * @var PaymentProduct806SpecificOutput|null
     */
    public ?PaymentProduct806SpecificOutput $paymentProduct806SpecificOutput = null;

    /**
     * @var PaymentProduct840SpecificOutput|null
     */
    public ?PaymentProduct840SpecificOutput $paymentProduct840SpecificOutput = null;

    /**
     * @var PaymentProduct866SpecificOutput|null
     */
    public ?PaymentProduct866SpecificOutput $paymentProduct866SpecificOutput = null;

    /**
     * @var string|null
     */
    public ?string $token = null;

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
        if (!is_null($this->bic)) {
            $object->bic = $this->bic;
        }
        if (!is_null($this->fraudResults)) {
            $object->fraudResults = $this->fraudResults->toObject();
        }
        if (!is_null($this->paymentProduct3201SpecificOutput)) {
            $object->paymentProduct3201SpecificOutput = $this->paymentProduct3201SpecificOutput->toObject();
        }
        if (!is_null($this->paymentProduct806SpecificOutput)) {
            $object->paymentProduct806SpecificOutput = $this->paymentProduct806SpecificOutput->toObject();
        }
        if (!is_null($this->paymentProduct840SpecificOutput)) {
            $object->paymentProduct840SpecificOutput = $this->paymentProduct840SpecificOutput->toObject();
        }
        if (!is_null($this->paymentProduct866SpecificOutput)) {
            $object->paymentProduct866SpecificOutput = $this->paymentProduct866SpecificOutput->toObject();
        }
        if (!is_null($this->token)) {
            $object->token = $this->token;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RedirectPaymentMethodSpecificOutput
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
        if (property_exists($object, 'bic')) {
            $this->bic = $object->bic;
        }
        if (property_exists($object, 'fraudResults')) {
            if (!is_object($object->fraudResults)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fraudResults, true) . '\' is not an object');
            }
            $value = new FraudResults();
            $this->fraudResults = $value->fromObject($object->fraudResults);
        }
        if (property_exists($object, 'paymentProduct3201SpecificOutput')) {
            if (!is_object($object->paymentProduct3201SpecificOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct3201SpecificOutput, true) . '\' is not an object');
            }
            $value = new PaymentProduct3201SpecificOutput();
            $this->paymentProduct3201SpecificOutput = $value->fromObject($object->paymentProduct3201SpecificOutput);
        }
        if (property_exists($object, 'paymentProduct806SpecificOutput')) {
            if (!is_object($object->paymentProduct806SpecificOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct806SpecificOutput, true) . '\' is not an object');
            }
            $value = new PaymentProduct806SpecificOutput();
            $this->paymentProduct806SpecificOutput = $value->fromObject($object->paymentProduct806SpecificOutput);
        }
        if (property_exists($object, 'paymentProduct840SpecificOutput')) {
            if (!is_object($object->paymentProduct840SpecificOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct840SpecificOutput, true) . '\' is not an object');
            }
            $value = new PaymentProduct840SpecificOutput();
            $this->paymentProduct840SpecificOutput = $value->fromObject($object->paymentProduct840SpecificOutput);
        }
        if (property_exists($object, 'paymentProduct866SpecificOutput')) {
            if (!is_object($object->paymentProduct866SpecificOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct866SpecificOutput, true) . '\' is not an object');
            }
            $value = new PaymentProduct866SpecificOutput();
            $this->paymentProduct866SpecificOutput = $value->fromObject($object->paymentProduct866SpecificOutput);
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        return $this;
    }
}
