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
class DisputesResponse extends DataObject
{
    /**
     * @var Dispute[]|null
     */
    public ?array $disputes = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->disputes)) {
            $object->disputes = [];
            foreach ($this->disputes as $element) {
                if (!is_null($element)) {
                    $object->disputes[] = $element->toObject();
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
    public function fromObject(object $object): DisputesResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'disputes')) {
            if (!is_array($object->disputes) && !is_object($object->disputes)) {
                throw new UnexpectedValueException('value \'' . print_r($object->disputes, true) . '\' is not an array or object');
            }
            $this->disputes = [];
            foreach ($object->disputes as $element) {
                $value = new Dispute();
                $this->disputes[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
