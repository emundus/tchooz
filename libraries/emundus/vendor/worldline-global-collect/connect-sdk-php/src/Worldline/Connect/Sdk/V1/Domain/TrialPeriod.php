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
class TrialPeriod extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $duration = null;

    /**
     * @var string|null
     */
    public ?string $interval = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->duration)) {
            $object->duration = $this->duration;
        }
        if (!is_null($this->interval)) {
            $object->interval = $this->interval;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): TrialPeriod
    {
        parent::fromObject($object);
        if (property_exists($object, 'duration')) {
            $this->duration = $object->duration;
        }
        if (property_exists($object, 'interval')) {
            $this->interval = $object->interval;
        }
        return $this;
    }
}
