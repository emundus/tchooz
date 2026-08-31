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
class RedirectPaymentProduct840SpecificInput extends AbstractRedirectPaymentProduct840SpecificInput
{
    /**
     * @var string|null
     *
     * @deprecated Use Order.references.descriptor instead
     */
    public ?string $custom = null;

    /**
     * @var bool|null
     */
    public ?bool $isShortcut = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->custom)) {
            $object->custom = $this->custom;
        }
        if (!is_null($this->isShortcut)) {
            $object->isShortcut = $this->isShortcut;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RedirectPaymentProduct840SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'custom')) {
            $this->custom = $object->custom;
        }
        if (property_exists($object, 'isShortcut')) {
            $this->isShortcut = $object->isShortcut;
        }
        return $this;
    }
}
