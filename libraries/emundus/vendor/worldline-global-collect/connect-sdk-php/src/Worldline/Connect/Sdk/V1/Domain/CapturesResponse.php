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
class CapturesResponse extends DataObject
{
    /**
     * @var Capture[]|null
     */
    public ?array $captures = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->captures)) {
            $object->captures = [];
            foreach ($this->captures as $element) {
                if (!is_null($element)) {
                    $object->captures[] = $element->toObject();
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
    public function fromObject(object $object): CapturesResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'captures')) {
            if (!is_array($object->captures) && !is_object($object->captures)) {
                throw new UnexpectedValueException('value \'' . print_r($object->captures, true) . '\' is not an array or object');
            }
            $this->captures = [];
            foreach ($object->captures as $element) {
                $value = new Capture();
                $this->captures[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
