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
class Card extends CardWithoutCvv
{
    /**
     * @var string|null
     */
    public ?string $cvv = null;

    /**
     * @var string|null
     */
    public ?string $partialPin = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->cvv)) {
            $object->cvv = $this->cvv;
        }
        if (!is_null($this->partialPin)) {
            $object->partialPin = $this->partialPin;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): Card
    {
        parent::fromObject($object);
        if (property_exists($object, 'cvv')) {
            $this->cvv = $object->cvv;
        }
        if (property_exists($object, 'partialPin')) {
            $this->partialPin = $object->partialPin;
        }
        return $this;
    }
}
