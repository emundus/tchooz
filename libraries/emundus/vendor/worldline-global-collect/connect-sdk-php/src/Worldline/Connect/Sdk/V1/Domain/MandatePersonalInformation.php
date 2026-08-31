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
class MandatePersonalInformation extends DataObject
{
    /**
     * @var MandatePersonalName|null
     */
    public ?MandatePersonalName $name = null;

    /**
     * @var string|null
     */
    public ?string $title = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->name)) {
            $object->name = $this->name->toObject();
        }
        if (!is_null($this->title)) {
            $object->title = $this->title;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MandatePersonalInformation
    {
        parent::fromObject($object);
        if (property_exists($object, 'name')) {
            if (!is_object($object->name)) {
                throw new UnexpectedValueException('value \'' . print_r($object->name, true) . '\' is not an object');
            }
            $value = new MandatePersonalName();
            $this->name = $value->fromObject($object->name);
        }
        if (property_exists($object, 'title')) {
            $this->title = $object->title;
        }
        return $this;
    }
}
