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
class InstallmentOptionsResponse extends DataObject
{
    /**
     * @var InstallmentOptions[]|null
     */
    public ?array $installmentOptions = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->installmentOptions)) {
            $object->installmentOptions = [];
            foreach ($this->installmentOptions as $element) {
                if (!is_null($element)) {
                    $object->installmentOptions[] = $element->toObject();
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
    public function fromObject(object $object): InstallmentOptionsResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'installmentOptions')) {
            if (!is_array($object->installmentOptions) && !is_object($object->installmentOptions)) {
                throw new UnexpectedValueException('value \'' . print_r($object->installmentOptions, true) . '\' is not an array or object');
            }
            $this->installmentOptions = [];
            foreach ($object->installmentOptions as $element) {
                $value = new InstallmentOptions();
                $this->installmentOptions[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
