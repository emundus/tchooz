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
class SepaDirectDebitPaymentProduct771SpecificInput extends AbstractSepaDirectDebitPaymentProduct771SpecificInput
{
    /**
     * @var string|null
     */
    public ?string $existingUniqueMandateReference = null;

    /**
     * @var CreateMandateWithReturnUrl|null
     */
    public ?CreateMandateWithReturnUrl $mandate = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->existingUniqueMandateReference)) {
            $object->existingUniqueMandateReference = $this->existingUniqueMandateReference;
        }
        if (!is_null($this->mandate)) {
            $object->mandate = $this->mandate->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): SepaDirectDebitPaymentProduct771SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'existingUniqueMandateReference')) {
            $this->existingUniqueMandateReference = $object->existingUniqueMandateReference;
        }
        if (property_exists($object, 'mandate')) {
            if (!is_object($object->mandate)) {
                throw new UnexpectedValueException('value \'' . print_r($object->mandate, true) . '\' is not an object');
            }
            $value = new CreateMandateWithReturnUrl();
            $this->mandate = $value->fromObject($object->mandate);
        }
        return $this;
    }
}
