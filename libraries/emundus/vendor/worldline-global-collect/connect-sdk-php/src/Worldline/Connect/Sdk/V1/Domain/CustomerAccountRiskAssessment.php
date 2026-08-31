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
class CustomerAccountRiskAssessment extends DataObject
{
    /**
     * @var bool|null
     */
    public ?bool $hasForgottenPassword = null;

    /**
     * @var bool|null
     */
    public ?bool $hasPassword = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->hasForgottenPassword)) {
            $object->hasForgottenPassword = $this->hasForgottenPassword;
        }
        if (!is_null($this->hasPassword)) {
            $object->hasPassword = $this->hasPassword;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CustomerAccountRiskAssessment
    {
        parent::fromObject($object);
        if (property_exists($object, 'hasForgottenPassword')) {
            $this->hasForgottenPassword = $object->hasForgottenPassword;
        }
        if (property_exists($object, 'hasPassword')) {
            $this->hasPassword = $object->hasPassword;
        }
        return $this;
    }
}
