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
class CreateHostedMandateManagementRequest extends DataObject
{
    /**
     * @var HostedMandateInfo|null
     */
    public ?HostedMandateInfo $createMandateInfo = null;

    /**
     * @var HostedMandateManagementSpecificInput|null
     */
    public ?HostedMandateManagementSpecificInput $hostedMandateManagementSpecificInput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->createMandateInfo)) {
            $object->createMandateInfo = $this->createMandateInfo->toObject();
        }
        if (!is_null($this->hostedMandateManagementSpecificInput)) {
            $object->hostedMandateManagementSpecificInput = $this->hostedMandateManagementSpecificInput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CreateHostedMandateManagementRequest
    {
        parent::fromObject($object);
        if (property_exists($object, 'createMandateInfo')) {
            if (!is_object($object->createMandateInfo)) {
                throw new UnexpectedValueException('value \'' . print_r($object->createMandateInfo, true) . '\' is not an object');
            }
            $value = new HostedMandateInfo();
            $this->createMandateInfo = $value->fromObject($object->createMandateInfo);
        }
        if (property_exists($object, 'hostedMandateManagementSpecificInput')) {
            if (!is_object($object->hostedMandateManagementSpecificInput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->hostedMandateManagementSpecificInput, true) . '\' is not an object');
            }
            $value = new HostedMandateManagementSpecificInput();
            $this->hostedMandateManagementSpecificInput = $value->fromObject($object->hostedMandateManagementSpecificInput);
        }
        return $this;
    }
}
