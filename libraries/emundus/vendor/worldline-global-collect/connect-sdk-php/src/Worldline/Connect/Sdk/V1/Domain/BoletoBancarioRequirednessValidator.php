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
class BoletoBancarioRequirednessValidator extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $fiscalNumberLength = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->fiscalNumberLength)) {
            $object->fiscalNumberLength = $this->fiscalNumberLength;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): BoletoBancarioRequirednessValidator
    {
        parent::fromObject($object);
        if (property_exists($object, 'fiscalNumberLength')) {
            $this->fiscalNumberLength = $object->fiscalNumberLength;
        }
        return $this;
    }
}
