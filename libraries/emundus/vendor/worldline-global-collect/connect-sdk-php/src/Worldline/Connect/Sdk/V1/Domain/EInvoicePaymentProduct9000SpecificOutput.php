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
class EInvoicePaymentProduct9000SpecificOutput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $installmentId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->installmentId)) {
            $object->installmentId = $this->installmentId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): EInvoicePaymentProduct9000SpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'installmentId')) {
            $this->installmentId = $object->installmentId;
        }
        return $this;
    }
}
