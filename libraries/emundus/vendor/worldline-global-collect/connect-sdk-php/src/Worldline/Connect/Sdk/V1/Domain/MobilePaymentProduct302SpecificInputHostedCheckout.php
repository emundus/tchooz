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
class MobilePaymentProduct302SpecificInputHostedCheckout extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $businessName = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->businessName)) {
            $object->businessName = $this->businessName;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MobilePaymentProduct302SpecificInputHostedCheckout
    {
        parent::fromObject($object);
        if (property_exists($object, 'businessName')) {
            $this->businessName = $object->businessName;
        }
        return $this;
    }
}
