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
class PersonalInformation extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $dateOfBirth = null;

    /**
     * @var string|null
     */
    public ?string $gender = null;

    /**
     * @var PersonalIdentification|null
     */
    public ?PersonalIdentification $identification = null;

    /**
     * @var PersonalName|null
     */
    public ?PersonalName $name = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->dateOfBirth)) {
            $object->dateOfBirth = $this->dateOfBirth;
        }
        if (!is_null($this->gender)) {
            $object->gender = $this->gender;
        }
        if (!is_null($this->identification)) {
            $object->identification = $this->identification->toObject();
        }
        if (!is_null($this->name)) {
            $object->name = $this->name->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PersonalInformation
    {
        parent::fromObject($object);
        if (property_exists($object, 'dateOfBirth')) {
            $this->dateOfBirth = $object->dateOfBirth;
        }
        if (property_exists($object, 'gender')) {
            $this->gender = $object->gender;
        }
        if (property_exists($object, 'identification')) {
            if (!is_object($object->identification)) {
                throw new UnexpectedValueException('value \'' . print_r($object->identification, true) . '\' is not an object');
            }
            $value = new PersonalIdentification();
            $this->identification = $value->fromObject($object->identification);
        }
        if (property_exists($object, 'name')) {
            if (!is_object($object->name)) {
                throw new UnexpectedValueException('value \'' . print_r($object->name, true) . '\' is not an object');
            }
            $value = new PersonalName();
            $this->name = $value->fromObject($object->name);
        }
        return $this;
    }
}
