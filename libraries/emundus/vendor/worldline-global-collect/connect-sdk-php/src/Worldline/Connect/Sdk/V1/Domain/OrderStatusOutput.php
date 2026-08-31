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
class OrderStatusOutput extends DataObject
{
    /**
     * @var APIError[]|null
     */
    public ?array $errors = null;

    /**
     * @var bool|null
     */
    public ?bool $isCancellable = null;

    /**
     * @var bool|null
     */
    public ?bool $isRetriable = null;

    /**
     * @var KeyValuePair[]|null
     */
    public ?array $providerRawOutput = null;

    /**
     * @var string|null
     */
    public ?string $statusCategory = null;

    /**
     * @var int|null
     */
    public ?int $statusCode = null;

    /**
     * @var string|null
     */
    public ?string $statusCodeChangeDateTime = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->errors)) {
            $object->errors = [];
            foreach ($this->errors as $element) {
                if (!is_null($element)) {
                    $object->errors[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->isCancellable)) {
            $object->isCancellable = $this->isCancellable;
        }
        if (!is_null($this->isRetriable)) {
            $object->isRetriable = $this->isRetriable;
        }
        if (!is_null($this->providerRawOutput)) {
            $object->providerRawOutput = [];
            foreach ($this->providerRawOutput as $element) {
                if (!is_null($element)) {
                    $object->providerRawOutput[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->statusCategory)) {
            $object->statusCategory = $this->statusCategory;
        }
        if (!is_null($this->statusCode)) {
            $object->statusCode = $this->statusCode;
        }
        if (!is_null($this->statusCodeChangeDateTime)) {
            $object->statusCodeChangeDateTime = $this->statusCodeChangeDateTime;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): OrderStatusOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'errors')) {
            if (!is_array($object->errors) && !is_object($object->errors)) {
                throw new UnexpectedValueException('value \'' . print_r($object->errors, true) . '\' is not an array or object');
            }
            $this->errors = [];
            foreach ($object->errors as $element) {
                $value = new APIError();
                $this->errors[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'isCancellable')) {
            $this->isCancellable = $object->isCancellable;
        }
        if (property_exists($object, 'isRetriable')) {
            $this->isRetriable = $object->isRetriable;
        }
        if (property_exists($object, 'providerRawOutput')) {
            if (!is_array($object->providerRawOutput) && !is_object($object->providerRawOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->providerRawOutput, true) . '\' is not an array or object');
            }
            $this->providerRawOutput = [];
            foreach ($object->providerRawOutput as $element) {
                $value = new KeyValuePair();
                $this->providerRawOutput[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'statusCategory')) {
            $this->statusCategory = $object->statusCategory;
        }
        if (property_exists($object, 'statusCode')) {
            $this->statusCode = $object->statusCode;
        }
        if (property_exists($object, 'statusCodeChangeDateTime')) {
            $this->statusCodeChangeDateTime = $object->statusCodeChangeDateTime;
        }
        return $this;
    }
}
