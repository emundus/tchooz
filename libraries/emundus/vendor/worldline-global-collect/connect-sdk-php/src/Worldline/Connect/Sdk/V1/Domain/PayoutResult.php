<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Domain;

use UnexpectedValueException;

/**
 * @package Worldline\Connect\Sdk\V1\Domain
 */
class PayoutResult extends AbstractOrderStatus
{
    /**
     * @var OrderOutput|null
     */
    public ?OrderOutput $payoutOutput = null;

    /**
     * @var string|null
     */
    public ?string $status = null;

    /**
     * @var OrderStatusOutput|null
     */
    public ?OrderStatusOutput $statusOutput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->payoutOutput)) {
            $object->payoutOutput = $this->payoutOutput->toObject();
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
    public function fromObject(object $object): PayoutResult
    {
        parent::fromObject($object);
        if (property_exists($object, 'payoutOutput')) {
            if (!is_object($object->payoutOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->payoutOutput, true) . '\' is not an object');
            }
            $value = new OrderOutput();
            $this->payoutOutput = $value->fromObject($object->payoutOutput);
        }
        if (property_exists($object, 'status')) {
            $this->status = $object->status;
        }
        if (property_exists($object, 'statusOutput')) {
            if (!is_object($object->statusOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->statusOutput, true) . '\' is not an object');
            }
            $value = new OrderStatusOutput();
            $this->statusOutput = $value->fromObject($object->statusOutput);
        }
        return $this;
    }
}
