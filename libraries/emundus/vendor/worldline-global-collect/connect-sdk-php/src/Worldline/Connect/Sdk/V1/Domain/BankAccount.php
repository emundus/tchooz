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
class BankAccount extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $accountHolderName = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->accountHolderName)) {
            $object->accountHolderName = $this->accountHolderName;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): BankAccount
    {
        parent::fromObject($object);
        if (property_exists($object, 'accountHolderName')) {
            $this->accountHolderName = $object->accountHolderName;
        }
        return $this;
    }
}
