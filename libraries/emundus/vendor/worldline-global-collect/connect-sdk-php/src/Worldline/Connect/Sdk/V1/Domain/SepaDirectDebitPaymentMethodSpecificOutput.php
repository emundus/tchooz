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
class SepaDirectDebitPaymentMethodSpecificOutput extends AbstractPaymentMethodSpecificOutput
{
    /**
     * @var FraudResults|null
     */
    public ?FraudResults $fraudResults = null;

    /**
     * @var PaymentProduct771SpecificOutput|null
     */
    public ?PaymentProduct771SpecificOutput $paymentProduct771SpecificOutput = null;

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
        if (!is_null($this->fraudResults)) {
            $object->fraudResults = $this->fraudResults->toObject();
        }
        if (!is_null($this->paymentProduct771SpecificOutput)) {
            $object->paymentProduct771SpecificOutput = $this->paymentProduct771SpecificOutput->toObject();
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
    public function fromObject(object $object): SepaDirectDebitPaymentMethodSpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'fraudResults')) {
            if (!is_object($object->fraudResults)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fraudResults, true) . '\' is not an object');
            }
            $value = new FraudResults();
            $this->fraudResults = $value->fromObject($object->fraudResults);
        }
        if (property_exists($object, 'paymentProduct771SpecificOutput')) {
            if (!is_object($object->paymentProduct771SpecificOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct771SpecificOutput, true) . '\' is not an object');
            }
            $value = new PaymentProduct771SpecificOutput();
            $this->paymentProduct771SpecificOutput = $value->fromObject($object->paymentProduct771SpecificOutput);
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        return $this;
    }
}
