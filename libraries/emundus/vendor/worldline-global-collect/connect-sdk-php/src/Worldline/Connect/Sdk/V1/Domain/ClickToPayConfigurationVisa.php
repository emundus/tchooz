<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1\Domain;

use UnexpectedValueException;

/**
 * @package Worldline\Connect\Sdk\V1\Domain
 */
class ClickToPayConfigurationVisa extends ClickToPaySchemeConfigurationBase
{
    /**
     * @var string|null
     */
    public ?string $encryptionKey = null;

    /**
     * @var string|null
     */
    public ?string $nModulus = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->encryptionKey)) {
            $object->encryptionKey = $this->encryptionKey;
        }
        if (!is_null($this->nModulus)) {
            $object->nModulus = $this->nModulus;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ClickToPayConfigurationVisa
    {
        parent::fromObject($object);
        if (property_exists($object, 'encryptionKey')) {
            $this->encryptionKey = $object->encryptionKey;
        }
        if (property_exists($object, 'nModulus')) {
            $this->nModulus = $object->nModulus;
        }
        return $this;
    }
}
