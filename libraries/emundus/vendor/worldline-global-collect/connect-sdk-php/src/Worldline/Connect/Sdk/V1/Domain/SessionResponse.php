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
class SessionResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $assetUrl = null;

    /**
     * @var string|null
     */
    public ?string $clientApiUrl = null;

    /**
     * @var string|null
     */
    public ?string $clientSessionId = null;

    /**
     * @var string|null
     */
    public ?string $customerId = null;

    /**
     * @var string[]|null
     */
    public ?array $invalidTokens = null;

    /**
     * @var string|null
     */
    public ?string $region = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->assetUrl)) {
            $object->assetUrl = $this->assetUrl;
        }
        if (!is_null($this->clientApiUrl)) {
            $object->clientApiUrl = $this->clientApiUrl;
        }
        if (!is_null($this->clientSessionId)) {
            $object->clientSessionId = $this->clientSessionId;
        }
        if (!is_null($this->customerId)) {
            $object->customerId = $this->customerId;
        }
        if (!is_null($this->invalidTokens)) {
            $object->invalidTokens = [];
            foreach ($this->invalidTokens as $element) {
                if (!is_null($element)) {
                    $object->invalidTokens[] = $element;
                }
            }
        }
        if (!is_null($this->region)) {
            $object->region = $this->region;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): SessionResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'assetUrl')) {
            $this->assetUrl = $object->assetUrl;
        }
        if (property_exists($object, 'clientApiUrl')) {
            $this->clientApiUrl = $object->clientApiUrl;
        }
        if (property_exists($object, 'clientSessionId')) {
            $this->clientSessionId = $object->clientSessionId;
        }
        if (property_exists($object, 'customerId')) {
            $this->customerId = $object->customerId;
        }
        if (property_exists($object, 'invalidTokens')) {
            if (!is_array($object->invalidTokens) && !is_object($object->invalidTokens)) {
                throw new UnexpectedValueException('value \'' . print_r($object->invalidTokens, true) . '\' is not an array or object');
            }
            $this->invalidTokens = [];
            foreach ($object->invalidTokens as $element) {
                $this->invalidTokens[] = $element;
            }
        }
        if (property_exists($object, 'region')) {
            $this->region = $object->region;
        }
        return $this;
    }
}
