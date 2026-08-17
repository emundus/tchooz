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
 *
 * @deprecated No replacement
 */
class LoanRecipient extends DataObject
{
    /**
     * @var string|null
     *
     * @deprecated No replacement
     */
    public ?string $accountNumber = null;

    /**
     * @var string|null
     *
     * @deprecated No replacement
     */
    public ?string $dateOfBirth = null;

    /**
     * @var string|null
     *
     * @deprecated No replacement
     */
    public ?string $partialPan = null;

    /**
     * @var string|null
     *
     * @deprecated No replacement
     */
    public ?string $surname = null;

    /**
     * @var string|null
     *
     * @deprecated No replacement
     */
    public ?string $zip = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->accountNumber)) {
            $object->accountNumber = $this->accountNumber;
        }
        if (!is_null($this->dateOfBirth)) {
            $object->dateOfBirth = $this->dateOfBirth;
        }
        if (!is_null($this->partialPan)) {
            $object->partialPan = $this->partialPan;
        }
        if (!is_null($this->surname)) {
            $object->surname = $this->surname;
        }
        if (!is_null($this->zip)) {
            $object->zip = $this->zip;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): LoanRecipient
    {
        parent::fromObject($object);
        if (property_exists($object, 'accountNumber')) {
            $this->accountNumber = $object->accountNumber;
        }
        if (property_exists($object, 'dateOfBirth')) {
            $this->dateOfBirth = $object->dateOfBirth;
        }
        if (property_exists($object, 'partialPan')) {
            $this->partialPan = $object->partialPan;
        }
        if (property_exists($object, 'surname')) {
            $this->surname = $object->surname;
        }
        if (property_exists($object, 'zip')) {
            $this->zip = $object->zip;
        }
        return $this;
    }
}
