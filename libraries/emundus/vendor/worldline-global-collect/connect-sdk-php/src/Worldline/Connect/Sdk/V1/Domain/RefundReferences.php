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
class RefundReferences extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $descriptor = null;

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
        if (!is_null($this->descriptor)) {
            $object->descriptor = $this->descriptor;
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
    public function fromObject(object $object): RefundReferences
    {
        parent::fromObject($object);
        if (property_exists($object, 'descriptor')) {
            $this->descriptor = $object->descriptor;
        }
        if (property_exists($object, 'merchantReference')) {
            $this->merchantReference = $object->merchantReference;
        }
        return $this;
    }
}
