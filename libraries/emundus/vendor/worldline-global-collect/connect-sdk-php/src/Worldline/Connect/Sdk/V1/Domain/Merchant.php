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
class Merchant extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $configurationId = null;

    /**
     * @var string|null
     */
    public ?string $contactWebsiteUrl = null;

    /**
     * @var Seller|null
     */
    public ?Seller $seller = null;

    /**
     * @var string|null
     */
    public ?string $websiteUrl = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->configurationId)) {
            $object->configurationId = $this->configurationId;
        }
        if (!is_null($this->contactWebsiteUrl)) {
            $object->contactWebsiteUrl = $this->contactWebsiteUrl;
        }
        if (!is_null($this->seller)) {
            $object->seller = $this->seller->toObject();
        }
        if (!is_null($this->websiteUrl)) {
            $object->websiteUrl = $this->websiteUrl;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): Merchant
    {
        parent::fromObject($object);
        if (property_exists($object, 'configurationId')) {
            $this->configurationId = $object->configurationId;
        }
        if (property_exists($object, 'contactWebsiteUrl')) {
            $this->contactWebsiteUrl = $object->contactWebsiteUrl;
        }
        if (property_exists($object, 'seller')) {
            if (!is_object($object->seller)) {
                throw new UnexpectedValueException('value \'' . print_r($object->seller, true) . '\' is not an object');
            }
            $value = new Seller();
            $this->seller = $value->fromObject($object->seller);
        }
        if (property_exists($object, 'websiteUrl')) {
            $this->websiteUrl = $object->websiteUrl;
        }
        return $this;
    }
}
