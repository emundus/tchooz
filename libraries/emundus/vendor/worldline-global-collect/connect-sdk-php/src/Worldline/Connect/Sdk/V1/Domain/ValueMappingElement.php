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
class ValueMappingElement extends DataObject
{
    /**
     * @var PaymentProductFieldDisplayElement[]|null
     */
    public ?array $displayElements = null;

    /**
     * @var string|null
     *
     * @deprecated Use displayElements instead with ID 'displayName'
     */
    public ?string $displayName = null;

    /**
     * @var string|null
     */
    public ?string $value = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->displayElements)) {
            $object->displayElements = [];
            foreach ($this->displayElements as $element) {
                if (!is_null($element)) {
                    $object->displayElements[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->displayName)) {
            $object->displayName = $this->displayName;
        }
        if (!is_null($this->value)) {
            $object->value = $this->value;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ValueMappingElement
    {
        parent::fromObject($object);
        if (property_exists($object, 'displayElements')) {
            if (!is_array($object->displayElements) && !is_object($object->displayElements)) {
                throw new UnexpectedValueException('value \'' . print_r($object->displayElements, true) . '\' is not an array or object');
            }
            $this->displayElements = [];
            foreach ($object->displayElements as $element) {
                $value = new PaymentProductFieldDisplayElement();
                $this->displayElements[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'displayName')) {
            $this->displayName = $object->displayName;
        }
        if (property_exists($object, 'value')) {
            $this->value = $object->value;
        }
        return $this;
    }
}
