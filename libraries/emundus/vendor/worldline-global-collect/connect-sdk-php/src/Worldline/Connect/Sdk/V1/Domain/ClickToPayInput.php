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
class ClickToPayInput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $checkoutResponseSignature = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->checkoutResponseSignature)) {
            $object->checkoutResponseSignature = $this->checkoutResponseSignature;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ClickToPayInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'checkoutResponseSignature')) {
            $this->checkoutResponseSignature = $object->checkoutResponseSignature;
        }
        return $this;
    }
}
