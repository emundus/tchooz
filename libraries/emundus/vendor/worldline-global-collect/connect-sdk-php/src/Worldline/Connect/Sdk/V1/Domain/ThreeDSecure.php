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
class ThreeDSecure extends AbstractThreeDSecure
{
    /**
     * @var ExternalCardholderAuthenticationData|null
     */
    public ?ExternalCardholderAuthenticationData $externalCardholderAuthenticationData = null;

    /**
     * @var RedirectionData|null
     */
    public ?RedirectionData $redirectionData = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->externalCardholderAuthenticationData)) {
            $object->externalCardholderAuthenticationData = $this->externalCardholderAuthenticationData->toObject();
        }
        if (!is_null($this->redirectionData)) {
            $object->redirectionData = $this->redirectionData->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ThreeDSecure
    {
        parent::fromObject($object);
        if (property_exists($object, 'externalCardholderAuthenticationData')) {
            if (!is_object($object->externalCardholderAuthenticationData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->externalCardholderAuthenticationData, true) . '\' is not an object');
            }
            $value = new ExternalCardholderAuthenticationData();
            $this->externalCardholderAuthenticationData = $value->fromObject($object->externalCardholderAuthenticationData);
        }
        if (property_exists($object, 'redirectionData')) {
            if (!is_object($object->redirectionData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->redirectionData, true) . '\' is not an object');
            }
            $value = new RedirectionData();
            $this->redirectionData = $value->fromObject($object->redirectionData);
        }
        return $this;
    }
}
