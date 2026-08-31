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
class DisputeCreationDetail extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $disputeCreationDate = null;

    /**
     * @var string|null
     */
    public ?string $disputeOriginator = null;

    /**
     * @var string|null
     */
    public ?string $userName = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->disputeCreationDate)) {
            $object->disputeCreationDate = $this->disputeCreationDate;
        }
        if (!is_null($this->disputeOriginator)) {
            $object->disputeOriginator = $this->disputeOriginator;
        }
        if (!is_null($this->userName)) {
            $object->userName = $this->userName;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DisputeCreationDetail
    {
        parent::fromObject($object);
        if (property_exists($object, 'disputeCreationDate')) {
            $this->disputeCreationDate = $object->disputeCreationDate;
        }
        if (property_exists($object, 'disputeOriginator')) {
            $this->disputeOriginator = $object->disputeOriginator;
        }
        if (property_exists($object, 'userName')) {
            $this->userName = $object->userName;
        }
        return $this;
    }
}
