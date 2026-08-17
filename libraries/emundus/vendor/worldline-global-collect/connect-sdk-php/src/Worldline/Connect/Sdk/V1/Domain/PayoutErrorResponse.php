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
class PayoutErrorResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $errorId = null;

    /**
     * @var APIError[]|null
     */
    public ?array $errors = null;

    /**
     * @var PayoutResult|null
     */
    public ?PayoutResult $payoutResult = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->errorId)) {
            $object->errorId = $this->errorId;
        }
        if (!is_null($this->errors)) {
            $object->errors = [];
            foreach ($this->errors as $element) {
                if (!is_null($element)) {
                    $object->errors[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->payoutResult)) {
            $object->payoutResult = $this->payoutResult->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PayoutErrorResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'errorId')) {
            $this->errorId = $object->errorId;
        }
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
        if (property_exists($object, 'payoutResult')) {
            if (!is_object($object->payoutResult)) {
                throw new UnexpectedValueException('value \'' . print_r($object->payoutResult, true) . '\' is not an object');
            }
            $value = new PayoutResult();
            $this->payoutResult = $value->fromObject($object->payoutResult);
        }
        return $this;
    }
}
