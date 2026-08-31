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
class ValidationBankAccountCheck extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $code = null;

    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * @var string|null
     */
    public ?string $result = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->code)) {
            $object->code = $this->code;
        }
        if (!is_null($this->description)) {
            $object->description = $this->description;
        }
        if (!is_null($this->result)) {
            $object->result = $this->result;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ValidationBankAccountCheck
    {
        parent::fromObject($object);
        if (property_exists($object, 'code')) {
            $this->code = $object->code;
        }
        if (property_exists($object, 'description')) {
            $this->description = $object->description;
        }
        if (property_exists($object, 'result')) {
            $this->result = $object->result;
        }
        return $this;
    }
}
