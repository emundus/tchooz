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
 *
 * @deprecated No replacement
 */
class FraudFieldsShippingDetails extends DataObject
{
    /**
     * @var string|null
     *
     * @deprecated No replacement
     */
    public ?string $methodDetails = null;

    /**
     * @var int|null
     *
     * @deprecated No replacement
     */
    public ?int $methodSpeed = null;

    /**
     * @var int|null
     *
     * @deprecated No replacement
     */
    public ?int $methodType = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->methodDetails)) {
            $object->methodDetails = $this->methodDetails;
        }
        if (!is_null($this->methodSpeed)) {
            $object->methodSpeed = $this->methodSpeed;
        }
        if (!is_null($this->methodType)) {
            $object->methodType = $this->methodType;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): FraudFieldsShippingDetails
    {
        parent::fromObject($object);
        if (property_exists($object, 'methodDetails')) {
            $this->methodDetails = $object->methodDetails;
        }
        if (property_exists($object, 'methodSpeed')) {
            $this->methodSpeed = $object->methodSpeed;
        }
        if (property_exists($object, 'methodType')) {
            $this->methodType = $object->methodType;
        }
        return $this;
    }
}
