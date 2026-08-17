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
class AbstractRedirectPaymentProduct838SpecificInput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $networkData = null;

    /**
     * @var string|null
     */
    public ?string $networkSessionToken = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->networkData)) {
            $object->networkData = $this->networkData;
        }
        if (!is_null($this->networkSessionToken)) {
            $object->networkSessionToken = $this->networkSessionToken;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AbstractRedirectPaymentProduct838SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'networkData')) {
            $this->networkData = $object->networkData;
        }
        if (property_exists($object, 'networkSessionToken')) {
            $this->networkSessionToken = $object->networkSessionToken;
        }
        return $this;
    }
}
