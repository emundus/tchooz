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
class PaymentProductField extends DataObject
{
    /**
     * @var PaymentProductFieldDataRestrictions|null
     */
    public ?PaymentProductFieldDataRestrictions $dataRestrictions = null;

    /**
     * @var PaymentProductFieldDisplayHints|null
     */
    public ?PaymentProductFieldDisplayHints $displayHints = null;

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var string|null
     */
    public ?string $type = null;

    /**
     * @var bool|null
     */
    public ?bool $usedForLookup = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->dataRestrictions)) {
            $object->dataRestrictions = $this->dataRestrictions->toObject();
        }
        if (!is_null($this->displayHints)) {
            $object->displayHints = $this->displayHints->toObject();
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        if (!is_null($this->type)) {
            $object->type = $this->type;
        }
        if (!is_null($this->usedForLookup)) {
            $object->usedForLookup = $this->usedForLookup;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProductField
    {
        parent::fromObject($object);
        if (property_exists($object, 'dataRestrictions')) {
            if (!is_object($object->dataRestrictions)) {
                throw new UnexpectedValueException('value \'' . print_r($object->dataRestrictions, true) . '\' is not an object');
            }
            $value = new PaymentProductFieldDataRestrictions();
            $this->dataRestrictions = $value->fromObject($object->dataRestrictions);
        }
        if (property_exists($object, 'displayHints')) {
            if (!is_object($object->displayHints)) {
                throw new UnexpectedValueException('value \'' . print_r($object->displayHints, true) . '\' is not an object');
            }
            $value = new PaymentProductFieldDisplayHints();
            $this->displayHints = $value->fromObject($object->displayHints);
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        if (property_exists($object, 'type')) {
            $this->type = $object->type;
        }
        if (property_exists($object, 'usedForLookup')) {
            $this->usedForLookup = $object->usedForLookup;
        }
        return $this;
    }
}
