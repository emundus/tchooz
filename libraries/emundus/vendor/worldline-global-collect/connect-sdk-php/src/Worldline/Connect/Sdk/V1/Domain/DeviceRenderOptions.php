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
class DeviceRenderOptions extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $sdkInterface = null;

    /**
     * @var string|null
     *
     * @deprecated Use deviceRenderOptions.sdkUiTypes instead
     */
    public ?string $sdkUiType = null;

    /**
     * @var string[]|null
     */
    public ?array $sdkUiTypes = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->sdkInterface)) {
            $object->sdkInterface = $this->sdkInterface;
        }
        if (!is_null($this->sdkUiType)) {
            $object->sdkUiType = $this->sdkUiType;
        }
        if (!is_null($this->sdkUiTypes)) {
            $object->sdkUiTypes = [];
            foreach ($this->sdkUiTypes as $element) {
                if (!is_null($element)) {
                    $object->sdkUiTypes[] = $element;
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
    public function fromObject(object $object): DeviceRenderOptions
    {
        parent::fromObject($object);
        if (property_exists($object, 'sdkInterface')) {
            $this->sdkInterface = $object->sdkInterface;
        }
        if (property_exists($object, 'sdkUiType')) {
            $this->sdkUiType = $object->sdkUiType;
        }
        if (property_exists($object, 'sdkUiTypes')) {
            if (!is_array($object->sdkUiTypes) && !is_object($object->sdkUiTypes)) {
                throw new UnexpectedValueException('value \'' . print_r($object->sdkUiTypes, true) . '\' is not an array or object');
            }
            $this->sdkUiTypes = [];
            foreach ($object->sdkUiTypes as $element) {
                $this->sdkUiTypes[] = $element;
            }
        }
        return $this;
    }
}
