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
class CapturePaymentRequest extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $amount = null;

    /**
     * @var bool|null
     */
    public ?bool $isFinal = null;

    /**
     * @var CapturePaymentOrder|null
     */
    public ?CapturePaymentOrder $order = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->amount)) {
            $object->amount = $this->amount;
        }
        if (!is_null($this->isFinal)) {
            $object->isFinal = $this->isFinal;
        }
        if (!is_null($this->order)) {
            $object->order = $this->order->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CapturePaymentRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'amount')) {
            $this->amount = $object->amount;
        }
        if (property_exists($object, 'isFinal')) {
            $this->isFinal = $object->isFinal;
        }
        if (property_exists($object, 'order')) {
            if (!is_object($object->order)) {
                throw new UnexpectedValueException('value \'' . print_r($object->order, true) . '\' is not an object');
            }
            $value = new CapturePaymentOrder();
            $this->order = $value->fromObject($object->order);
        }
        return $this;
    }
}
