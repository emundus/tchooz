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
class Dispute extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $captureId = null;

    /**
     * @var DisputeOutput|null
     */
    public ?DisputeOutput $disputeOutput = null;

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var string|null
     */
    public ?string $paymentId = null;

    /**
     * @var string|null
     */
    public ?string $status = null;

    /**
     * @var DisputeStatusOutput|null
     */
    public ?DisputeStatusOutput $statusOutput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->captureId)) {
            $object->captureId = $this->captureId;
        }
        if (!is_null($this->disputeOutput)) {
            $object->disputeOutput = $this->disputeOutput->toObject();
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        if (!is_null($this->paymentId)) {
            $object->paymentId = $this->paymentId;
        }
        if (!is_null($this->status)) {
            $object->status = $this->status;
        }
        if (!is_null($this->statusOutput)) {
            $object->statusOutput = $this->statusOutput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): Dispute
    {
        parent::fromObject($object);
        if (property_exists($object, 'captureId')) {
            $this->captureId = $object->captureId;
        }
        if (property_exists($object, 'disputeOutput')) {
            if (!is_object($object->disputeOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->disputeOutput, true) . '\' is not an object');
            }
            $value = new DisputeOutput();
            $this->disputeOutput = $value->fromObject($object->disputeOutput);
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        if (property_exists($object, 'paymentId')) {
            $this->paymentId = $object->paymentId;
        }
        if (property_exists($object, 'status')) {
            $this->status = $object->status;
        }
        if (property_exists($object, 'statusOutput')) {
            if (!is_object($object->statusOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->statusOutput, true) . '\' is not an object');
            }
            $value = new DisputeStatusOutput();
            $this->statusOutput = $value->fromObject($object->statusOutput);
        }
        return $this;
    }
}
