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
class ClickToPayConfiguration extends DataObject
{
    /**
     * @var ClickToPayDisplayHints|null
     */
    public ?ClickToPayDisplayHints $displayHints = null;

    /**
     * @var ClickToPayConfigurationMastercard|null
     */
    public ?ClickToPayConfigurationMastercard $mastercard = null;

    /**
     * @var ClickToPayConfigurationVisa|null
     */
    public ?ClickToPayConfigurationVisa $visa = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->displayHints)) {
            $object->displayHints = $this->displayHints->toObject();
        }
        if (!is_null($this->mastercard)) {
            $object->mastercard = $this->mastercard->toObject();
        }
        if (!is_null($this->visa)) {
            $object->visa = $this->visa->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ClickToPayConfiguration
    {
        parent::fromObject($object);
        if (property_exists($object, 'displayHints')) {
            if (!is_object($object->displayHints)) {
                throw new UnexpectedValueException('value \'' . print_r($object->displayHints, true) . '\' is not an object');
            }
            $value = new ClickToPayDisplayHints();
            $this->displayHints = $value->fromObject($object->displayHints);
        }
        if (property_exists($object, 'mastercard')) {
            if (!is_object($object->mastercard)) {
                throw new UnexpectedValueException('value \'' . print_r($object->mastercard, true) . '\' is not an object');
            }
            $value = new ClickToPayConfigurationMastercard();
            $this->mastercard = $value->fromObject($object->mastercard);
        }
        if (property_exists($object, 'visa')) {
            if (!is_object($object->visa)) {
                throw new UnexpectedValueException('value \'' . print_r($object->visa, true) . '\' is not an object');
            }
            $value = new ClickToPayConfigurationVisa();
            $this->visa = $value->fromObject($object->visa);
        }
        return $this;
    }
}
