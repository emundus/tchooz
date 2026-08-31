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
class RefundEWalletMethodSpecificOutput extends RefundMethodSpecificOutput
{
    /**
     * @var RefundPaymentProduct840SpecificOutput|null
     */
    public ?RefundPaymentProduct840SpecificOutput $paymentProduct840SpecificOutput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProduct840SpecificOutput)) {
            $object->paymentProduct840SpecificOutput = $this->paymentProduct840SpecificOutput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RefundEWalletMethodSpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProduct840SpecificOutput')) {
            if (!is_object($object->paymentProduct840SpecificOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProduct840SpecificOutput, true) . '\' is not an object');
            }
            $value = new RefundPaymentProduct840SpecificOutput();
            $this->paymentProduct840SpecificOutput = $value->fromObject($object->paymentProduct840SpecificOutput);
        }
        return $this;
    }
}
