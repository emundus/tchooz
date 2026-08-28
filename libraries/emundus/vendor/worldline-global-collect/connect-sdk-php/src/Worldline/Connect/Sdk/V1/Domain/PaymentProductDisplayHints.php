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
class PaymentProductDisplayHints extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $displayOrder = null;

    /**
     * @var string|null
     */
    public ?string $label = null;

    /**
     * @var string|null
     */
    public ?string $logo = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->displayOrder)) {
            $object->displayOrder = $this->displayOrder;
        }
        if (!is_null($this->label)) {
            $object->label = $this->label;
        }
        if (!is_null($this->logo)) {
            $object->logo = $this->logo;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProductDisplayHints
    {
        parent::fromObject($object);
        if (property_exists($object, 'displayOrder')) {
            $this->displayOrder = $object->displayOrder;
        }
        if (property_exists($object, 'label')) {
            $this->label = $object->label;
        }
        if (property_exists($object, 'logo')) {
            $this->logo = $object->logo;
        }
        return $this;
    }
}
