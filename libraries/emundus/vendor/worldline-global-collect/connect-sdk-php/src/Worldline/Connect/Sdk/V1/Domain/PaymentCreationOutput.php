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
class PaymentCreationOutput extends PaymentCreationReferences
{
    /**
     * @var bool|null
     */
    public ?bool $isCheckedRememberMe = null;

    /**
     * @var bool|null
     */
    public ?bool $isNewToken = null;

    /**
     * @var string|null
     */
    public ?string $token = null;

    /**
     * @var bool|null
     */
    public ?bool $tokenizationSucceeded = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->isCheckedRememberMe)) {
            $object->isCheckedRememberMe = $this->isCheckedRememberMe;
        }
        if (!is_null($this->isNewToken)) {
            $object->isNewToken = $this->isNewToken;
        }
        if (!is_null($this->token)) {
            $object->token = $this->token;
        }
        if (!is_null($this->tokenizationSucceeded)) {
            $object->tokenizationSucceeded = $this->tokenizationSucceeded;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentCreationOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'isCheckedRememberMe')) {
            $this->isCheckedRememberMe = $object->isCheckedRememberMe;
        }
        if (property_exists($object, 'isNewToken')) {
            $this->isNewToken = $object->isNewToken;
        }
        if (property_exists($object, 'token')) {
            $this->token = $object->token;
        }
        if (property_exists($object, 'tokenizationSucceeded')) {
            $this->tokenizationSucceeded = $object->tokenizationSucceeded;
        }
        return $this;
    }
}
