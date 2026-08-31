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
class GetCustomerDetailsRequest extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $countryCode = null;

    /**
     * @var KeyValuePair[]|null
     */
    public ?array $values = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->countryCode)) {
            $object->countryCode = $this->countryCode;
        }
        if (!is_null($this->values)) {
            $object->values = [];
            foreach ($this->values as $element) {
                if (!is_null($element)) {
                    $object->values[] = $element->toObject();
                }
            }
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): GetCustomerDetailsRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'countryCode')) {
            $this->countryCode = $object->countryCode;
        }
        if (property_exists($object, 'values')) {
            if (!is_array($object->values) && !is_object($object->values)) {
                throw new UnexpectedValueException('value \'' . print_r($object->values, true) . '\' is not an array or object');
            }
            $this->values = [];
            foreach ($object->values as $element) {
                $value = new KeyValuePair();
                $this->values[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
