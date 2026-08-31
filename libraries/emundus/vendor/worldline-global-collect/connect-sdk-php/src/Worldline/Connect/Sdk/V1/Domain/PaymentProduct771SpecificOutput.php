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
class PaymentProduct771SpecificOutput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $mandateReference = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->mandateReference)) {
            $object->mandateReference = $this->mandateReference;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProduct771SpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'mandateReference')) {
            $this->mandateReference = $object->mandateReference;
        }
        return $this;
    }
}
