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
class PaymentProduct863SpecificData extends DataObject
{
    /**
     * @var string[]|null
     */
    public ?array $integrationTypes = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->integrationTypes)) {
            $object->integrationTypes = [];
            foreach ($this->integrationTypes as $element) {
                if (!is_null($element)) {
                    $object->integrationTypes[] = $element;
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
    public function fromObject(object $object): PaymentProduct863SpecificData
    {
        parent::fromObject($object);
        if (property_exists($object, 'integrationTypes')) {
            if (!is_array($object->integrationTypes) && !is_object($object->integrationTypes)) {
                throw new UnexpectedValueException('value \'' . print_r($object->integrationTypes, true) . '\' is not an array or object');
            }
            $this->integrationTypes = [];
            foreach ($object->integrationTypes as $element) {
                $this->integrationTypes[] = $element;
            }
        }
        return $this;
    }
}
