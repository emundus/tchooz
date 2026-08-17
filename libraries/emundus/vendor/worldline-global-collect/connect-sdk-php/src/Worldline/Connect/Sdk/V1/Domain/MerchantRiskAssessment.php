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
class MerchantRiskAssessment extends DataObject
{
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
    public function fromObject(object $object): MerchantRiskAssessment
    {
        parent::fromObject($object);
        if (property_exists($object, 'websiteUrl')) {
            $this->websiteUrl = $object->websiteUrl;
        }
        return $this;
    }
}
