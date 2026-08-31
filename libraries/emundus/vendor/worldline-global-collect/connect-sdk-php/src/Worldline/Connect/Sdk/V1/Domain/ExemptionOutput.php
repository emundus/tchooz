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
class ExemptionOutput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $exemptionRaised = null;

    /**
     * @var string|null
     */
    public ?string $exemptionRejectionReason = null;

    /**
     * @var string|null
     */
    public ?string $exemptionRequest = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->exemptionRaised)) {
            $object->exemptionRaised = $this->exemptionRaised;
        }
        if (!is_null($this->exemptionRejectionReason)) {
            $object->exemptionRejectionReason = $this->exemptionRejectionReason;
        }
        if (!is_null($this->exemptionRequest)) {
            $object->exemptionRequest = $this->exemptionRequest;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ExemptionOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'exemptionRaised')) {
            $this->exemptionRaised = $object->exemptionRaised;
        }
        if (property_exists($object, 'exemptionRejectionReason')) {
            $this->exemptionRejectionReason = $object->exemptionRejectionReason;
        }
        if (property_exists($object, 'exemptionRequest')) {
            $this->exemptionRequest = $object->exemptionRequest;
        }
        return $this;
    }
}
