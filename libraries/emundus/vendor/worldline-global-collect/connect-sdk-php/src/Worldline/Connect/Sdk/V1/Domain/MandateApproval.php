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
class MandateApproval extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $mandateSignatureDate = null;

    /**
     * @var string|null
     */
    public ?string $mandateSignaturePlace = null;

    /**
     * @var bool|null
     */
    public ?bool $mandateSigned = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->mandateSignatureDate)) {
            $object->mandateSignatureDate = $this->mandateSignatureDate;
        }
        if (!is_null($this->mandateSignaturePlace)) {
            $object->mandateSignaturePlace = $this->mandateSignaturePlace;
        }
        if (!is_null($this->mandateSigned)) {
            $object->mandateSigned = $this->mandateSigned;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MandateApproval
    {
        parent::fromObject($object);
        if (property_exists($object, 'mandateSignatureDate')) {
            $this->mandateSignatureDate = $object->mandateSignatureDate;
        }
        if (property_exists($object, 'mandateSignaturePlace')) {
            $this->mandateSignaturePlace = $object->mandateSignaturePlace;
        }
        if (property_exists($object, 'mandateSigned')) {
            $this->mandateSigned = $object->mandateSigned;
        }
        return $this;
    }
}
