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
class PayoutReferences extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $invoiceNumber = null;

    /**
     * @var int|null
     */
    public ?int $merchantOrderId = null;

    /**
     * @var string|null
     */
    public ?string $merchantReference = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->invoiceNumber)) {
            $object->invoiceNumber = $this->invoiceNumber;
        }
        if (!is_null($this->merchantOrderId)) {
            $object->merchantOrderId = $this->merchantOrderId;
        }
        if (!is_null($this->merchantReference)) {
            $object->merchantReference = $this->merchantReference;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PayoutReferences
    {
        parent::fromObject($object);
        if (property_exists($object, 'invoiceNumber')) {
            $this->invoiceNumber = $object->invoiceNumber;
        }
        if (property_exists($object, 'merchantOrderId')) {
            $this->merchantOrderId = $object->merchantOrderId;
        }
        if (property_exists($object, 'merchantReference')) {
            $this->merchantReference = $object->merchantReference;
        }
        return $this;
    }
}
