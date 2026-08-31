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
class PersonalIdentification extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $idIssuingCountryCode = null;

    /**
     * @var string|null
     */
    public ?string $idType = null;

    /**
     * @var string|null
     */
    public ?string $idValue = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->idIssuingCountryCode)) {
            $object->idIssuingCountryCode = $this->idIssuingCountryCode;
        }
        if (!is_null($this->idType)) {
            $object->idType = $this->idType;
        }
        if (!is_null($this->idValue)) {
            $object->idValue = $this->idValue;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PersonalIdentification
    {
        parent::fromObject($object);
        if (property_exists($object, 'idIssuingCountryCode')) {
            $this->idIssuingCountryCode = $object->idIssuingCountryCode;
        }
        if (property_exists($object, 'idType')) {
            $this->idType = $object->idType;
        }
        if (property_exists($object, 'idValue')) {
            $this->idValue = $object->idValue;
        }
        return $this;
    }
}
