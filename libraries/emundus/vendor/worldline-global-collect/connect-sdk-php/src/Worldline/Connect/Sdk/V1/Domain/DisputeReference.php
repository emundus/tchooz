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
class DisputeReference extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $merchantOrderId = null;

    /**
     * @var string|null
     */
    public ?string $merchantReference = null;

    /**
     * @var string|null
     */
    public ?string $paymentReference = null;

    /**
     * @var string|null
     */
    public ?string $providerId = null;

    /**
     * @var string|null
     */
    public ?string $providerReference = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->merchantOrderId)) {
            $object->merchantOrderId = $this->merchantOrderId;
        }
        if (!is_null($this->merchantReference)) {
            $object->merchantReference = $this->merchantReference;
        }
        if (!is_null($this->paymentReference)) {
            $object->paymentReference = $this->paymentReference;
        }
        if (!is_null($this->providerId)) {
            $object->providerId = $this->providerId;
        }
        if (!is_null($this->providerReference)) {
            $object->providerReference = $this->providerReference;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DisputeReference
    {
        parent::fromObject($object);
        if (property_exists($object, 'merchantOrderId')) {
            $this->merchantOrderId = $object->merchantOrderId;
        }
        if (property_exists($object, 'merchantReference')) {
            $this->merchantReference = $object->merchantReference;
        }
        if (property_exists($object, 'paymentReference')) {
            $this->paymentReference = $object->paymentReference;
        }
        if (property_exists($object, 'providerId')) {
            $this->providerId = $object->providerId;
        }
        if (property_exists($object, 'providerReference')) {
            $this->providerReference = $object->providerReference;
        }
        return $this;
    }
}
