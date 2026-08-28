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
class PaymentProducts extends DataObject
{
    /**
     * @var PaymentProduct[]|null
     */
    public ?array $paymentProducts = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->paymentProducts)) {
            $object->paymentProducts = [];
            foreach ($this->paymentProducts as $element) {
                if (!is_null($element)) {
                    $object->paymentProducts[] = $element->toObject();
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
    public function fromObject(object $object): PaymentProducts
    {
        parent::fromObject($object);
        if (property_exists($object, 'paymentProducts')) {
            if (!is_array($object->paymentProducts) && !is_object($object->paymentProducts)) {
                throw new UnexpectedValueException('value \'' . print_r($object->paymentProducts, true) . '\' is not an array or object');
            }
            $this->paymentProducts = [];
            foreach ($object->paymentProducts as $element) {
                $value = new PaymentProduct();
                $this->paymentProducts[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
