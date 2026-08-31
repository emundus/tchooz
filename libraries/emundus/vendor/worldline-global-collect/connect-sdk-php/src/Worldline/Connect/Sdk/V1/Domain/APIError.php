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
class APIError extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $category = null;

    /**
     * @var string|null
     */
    public ?string $code = null;

    /**
     * @var int|null
     */
    public ?int $httpStatusCode = null;

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var string|null
     */
    public ?string $message = null;

    /**
     * @var string|null
     */
    public ?string $propertyName = null;

    /**
     * @var string|null
     */
    public ?string $requestId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->category)) {
            $object->category = $this->category;
        }
        if (!is_null($this->code)) {
            $object->code = $this->code;
        }
        if (!is_null($this->httpStatusCode)) {
            $object->httpStatusCode = $this->httpStatusCode;
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        if (!is_null($this->message)) {
            $object->message = $this->message;
        }
        if (!is_null($this->propertyName)) {
            $object->propertyName = $this->propertyName;
        }
        if (!is_null($this->requestId)) {
            $object->requestId = $this->requestId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): APIError
    {
        parent::fromObject($object);
        if (property_exists($object, 'category')) {
            $this->category = $object->category;
        }
        if (property_exists($object, 'code')) {
            $this->code = $object->code;
        }
        if (property_exists($object, 'httpStatusCode')) {
            $this->httpStatusCode = $object->httpStatusCode;
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        if (property_exists($object, 'message')) {
            $this->message = $object->message;
        }
        if (property_exists($object, 'propertyName')) {
            $this->propertyName = $object->propertyName;
        }
        if (property_exists($object, 'requestId')) {
            $this->requestId = $object->requestId;
        }
        return $this;
    }
}
