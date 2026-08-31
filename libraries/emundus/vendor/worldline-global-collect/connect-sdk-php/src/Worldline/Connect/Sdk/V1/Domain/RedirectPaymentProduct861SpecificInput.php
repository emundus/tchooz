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
class RedirectPaymentProduct861SpecificInput extends DataObject
{
    /**
     * @var bool|null
     */
    public ?bool $mobileDevice = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->mobileDevice)) {
            $object->mobileDevice = $this->mobileDevice;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RedirectPaymentProduct861SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'mobileDevice')) {
            $this->mobileDevice = $object->mobileDevice;
        }
        return $this;
    }
}
