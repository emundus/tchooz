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
class DeviceFingerprintRequest extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $collectorCallback = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->collectorCallback)) {
            $object->collectorCallback = $this->collectorCallback;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DeviceFingerprintRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'collectorCallback')) {
            $this->collectorCallback = $object->collectorCallback;
        }
        return $this;
    }
}
