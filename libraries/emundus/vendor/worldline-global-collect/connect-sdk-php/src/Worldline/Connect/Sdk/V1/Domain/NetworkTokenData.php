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
class NetworkTokenData extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $networkToken = null;

    /**
     * @var string|null
     */
    public ?string $tokenExpiryDate = null;

    /**
     * @var string|null
     */
    public ?string $tokenReferenceId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->networkToken)) {
            $object->networkToken = $this->networkToken;
        }
        if (!is_null($this->tokenExpiryDate)) {
            $object->tokenExpiryDate = $this->tokenExpiryDate;
        }
        if (!is_null($this->tokenReferenceId)) {
            $object->tokenReferenceId = $this->tokenReferenceId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): NetworkTokenData
    {
        parent::fromObject($object);
        if (property_exists($object, 'networkToken')) {
            $this->networkToken = $object->networkToken;
        }
        if (property_exists($object, 'tokenExpiryDate')) {
            $this->tokenExpiryDate = $object->tokenExpiryDate;
        }
        if (property_exists($object, 'tokenReferenceId')) {
            $this->tokenReferenceId = $object->tokenReferenceId;
        }
        return $this;
    }
}
