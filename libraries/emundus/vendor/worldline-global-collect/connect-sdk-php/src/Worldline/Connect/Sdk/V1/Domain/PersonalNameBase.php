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
class PersonalNameBase extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $firstName = null;

    /**
     * @var string|null
     */
    public ?string $surname = null;

    /**
     * @var string|null
     */
    public ?string $surnamePrefix = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->firstName)) {
            $object->firstName = $this->firstName;
        }
        if (!is_null($this->surname)) {
            $object->surname = $this->surname;
        }
        if (!is_null($this->surnamePrefix)) {
            $object->surnamePrefix = $this->surnamePrefix;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PersonalNameBase
    {
        parent::fromObject($object);
        if (property_exists($object, 'firstName')) {
            $this->firstName = $object->firstName;
        }
        if (property_exists($object, 'surname')) {
            $this->surname = $object->surname;
        }
        if (property_exists($object, 'surnamePrefix')) {
            $this->surnamePrefix = $object->surnamePrefix;
        }
        return $this;
    }
}
