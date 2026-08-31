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
class RefundMobileMethodSpecificOutput extends RefundMethodSpecificOutput
{
    /**
     * @var string|null
     */
    public ?string $network = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->network)) {
            $object->network = $this->network;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RefundMobileMethodSpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'network')) {
            $this->network = $object->network;
        }
        return $this;
    }
}
