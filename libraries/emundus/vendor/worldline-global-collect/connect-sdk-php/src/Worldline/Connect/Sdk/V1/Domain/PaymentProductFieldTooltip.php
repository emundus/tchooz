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
class PaymentProductFieldTooltip extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $image = null;

    /**
     * @var string|null
     */
    public ?string $label = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->image)) {
            $object->image = $this->image;
        }
        if (!is_null($this->label)) {
            $object->label = $this->label;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProductFieldTooltip
    {
        parent::fromObject($object);
        if (property_exists($object, 'image')) {
            $this->image = $object->image;
        }
        if (property_exists($object, 'label')) {
            $this->label = $object->label;
        }
        return $this;
    }
}
