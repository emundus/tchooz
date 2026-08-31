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
class AccountOnFile extends DataObject
{
    /**
     * @var AccountOnFileAttribute[]|null
     */
    public ?array $attributes = null;

    /**
     * @var AccountOnFileDisplayHints|null
     */
    public ?AccountOnFileDisplayHints $displayHints = null;

    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var int|null
     */
    public ?int $paymentProductId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->attributes)) {
            $object->attributes = [];
            foreach ($this->attributes as $element) {
                if (!is_null($element)) {
                    $object->attributes[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->displayHints)) {
            $object->displayHints = $this->displayHints->toObject();
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        if (!is_null($this->paymentProductId)) {
            $object->paymentProductId = $this->paymentProductId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AccountOnFile
    {
        parent::fromObject($object);
        if (property_exists($object, 'attributes')) {
            if (!is_array($object->attributes) && !is_object($object->attributes)) {
                throw new UnexpectedValueException('value \'' . print_r($object->attributes, true) . '\' is not an array or object');
            }
            $this->attributes = [];
            foreach ($object->attributes as $element) {
                $value = new AccountOnFileAttribute();
                $this->attributes[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'displayHints')) {
            if (!is_object($object->displayHints)) {
                throw new UnexpectedValueException('value \'' . print_r($object->displayHints, true) . '\' is not an object');
            }
            $value = new AccountOnFileDisplayHints();
            $this->displayHints = $value->fromObject($object->displayHints);
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        if (property_exists($object, 'paymentProductId')) {
            $this->paymentProductId = $object->paymentProductId;
        }
        return $this;
    }
}
