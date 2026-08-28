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
class CustomerAccountAuthentication extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $data = null;

    /**
     * @var string|null
     */
    public ?string $method = null;

    /**
     * @var string|null
     */
    public ?string $utcTimestamp = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->data)) {
            $object->data = $this->data;
        }
        if (!is_null($this->method)) {
            $object->method = $this->method;
        }
        if (!is_null($this->utcTimestamp)) {
            $object->utcTimestamp = $this->utcTimestamp;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CustomerAccountAuthentication
    {
        parent::fromObject($object);
        if (property_exists($object, 'data')) {
            $this->data = $object->data;
        }
        if (property_exists($object, 'method')) {
            $this->method = $object->method;
        }
        if (property_exists($object, 'utcTimestamp')) {
            $this->utcTimestamp = $object->utcTimestamp;
        }
        return $this;
    }
}
