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
class PaymentProduct320SpecificData extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $gateway = null;

    /**
     * @var string[]|null
     */
    public ?array $networks = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->gateway)) {
            $object->gateway = $this->gateway;
        }
        if (!is_null($this->networks)) {
            $object->networks = [];
            foreach ($this->networks as $element) {
                if (!is_null($element)) {
                    $object->networks[] = $element;
                }
            }
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProduct320SpecificData
    {
        parent::fromObject($object);
        if (property_exists($object, 'gateway')) {
            $this->gateway = $object->gateway;
        }
        if (property_exists($object, 'networks')) {
            if (!is_array($object->networks) && !is_object($object->networks)) {
                throw new UnexpectedValueException('value \'' . print_r($object->networks, true) . '\' is not an array or object');
            }
            $this->networks = [];
            foreach ($object->networks as $element) {
                $this->networks[] = $element;
            }
        }
        return $this;
    }
}
