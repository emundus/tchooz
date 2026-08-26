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
class BrowserData extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $colorDepth = null;

    /**
     * @var string|null
     */
    public ?string $innerHeight = null;

    /**
     * @var string|null
     */
    public ?string $innerWidth = null;

    /**
     * @var bool|null
     */
    public ?bool $javaEnabled = null;

    /**
     * @var bool|null
     */
    public ?bool $javaScriptEnabled = null;

    /**
     * @var string|null
     */
    public ?string $screenHeight = null;

    /**
     * @var string|null
     */
    public ?string $screenWidth = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->colorDepth)) {
            $object->colorDepth = $this->colorDepth;
        }
        if (!is_null($this->innerHeight)) {
            $object->innerHeight = $this->innerHeight;
        }
        if (!is_null($this->innerWidth)) {
            $object->innerWidth = $this->innerWidth;
        }
        if (!is_null($this->javaEnabled)) {
            $object->javaEnabled = $this->javaEnabled;
        }
        if (!is_null($this->javaScriptEnabled)) {
            $object->javaScriptEnabled = $this->javaScriptEnabled;
        }
        if (!is_null($this->screenHeight)) {
            $object->screenHeight = $this->screenHeight;
        }
        if (!is_null($this->screenWidth)) {
            $object->screenWidth = $this->screenWidth;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): BrowserData
    {
        parent::fromObject($object);
        if (property_exists($object, 'colorDepth')) {
            $this->colorDepth = $object->colorDepth;
        }
        if (property_exists($object, 'innerHeight')) {
            $this->innerHeight = $object->innerHeight;
        }
        if (property_exists($object, 'innerWidth')) {
            $this->innerWidth = $object->innerWidth;
        }
        if (property_exists($object, 'javaEnabled')) {
            $this->javaEnabled = $object->javaEnabled;
        }
        if (property_exists($object, 'javaScriptEnabled')) {
            $this->javaScriptEnabled = $object->javaScriptEnabled;
        }
        if (property_exists($object, 'screenHeight')) {
            $this->screenHeight = $object->screenHeight;
        }
        if (property_exists($object, 'screenWidth')) {
            $this->screenWidth = $object->screenWidth;
        }
        return $this;
    }
}
