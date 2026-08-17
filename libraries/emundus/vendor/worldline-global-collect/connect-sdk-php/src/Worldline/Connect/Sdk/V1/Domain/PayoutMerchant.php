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
class PayoutMerchant extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $configurationId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->configurationId)) {
            $object->configurationId = $this->configurationId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PayoutMerchant
    {
        parent::fromObject($object);
        if (property_exists($object, 'configurationId')) {
            $this->configurationId = $object->configurationId;
        }
        return $this;
    }
}
