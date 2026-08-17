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
class MobilePaymentProductSession302SpecificOutput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $sessionObject = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->sessionObject)) {
            $object->sessionObject = $this->sessionObject;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): MobilePaymentProductSession302SpecificOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'sessionObject')) {
            $this->sessionObject = $object->sessionObject;
        }
        return $this;
    }
}
