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
class DisplayedData extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $displayedDataType = null;

    /**
     * @var string|null
     */
    public ?string $renderingData = null;

    /**
     * @var KeyValuePair[]|null
     */
    public ?array $showData = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->displayedDataType)) {
            $object->displayedDataType = $this->displayedDataType;
        }
        if (!is_null($this->renderingData)) {
            $object->renderingData = $this->renderingData;
        }
        if (!is_null($this->showData)) {
            $object->showData = [];
            foreach ($this->showData as $element) {
                if (!is_null($element)) {
                    $object->showData[] = $element->toObject();
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
    public function fromObject(object $object): DisplayedData
    {
        parent::fromObject($object);
        if (property_exists($object, 'displayedDataType')) {
            $this->displayedDataType = $object->displayedDataType;
        }
        if (property_exists($object, 'renderingData')) {
            $this->renderingData = $object->renderingData;
        }
        if (property_exists($object, 'showData')) {
            if (!is_array($object->showData) && !is_object($object->showData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->showData, true) . '\' is not an array or object');
            }
            $this->showData = [];
            foreach ($object->showData as $element) {
                $value = new KeyValuePair();
                $this->showData[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
