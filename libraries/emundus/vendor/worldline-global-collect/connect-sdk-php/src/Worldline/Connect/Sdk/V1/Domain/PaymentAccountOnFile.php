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
class PaymentAccountOnFile extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $createDate = null;

    /**
     * @var int|null
     */
    public ?int $numberOfCardOnFileCreationAttemptsLast24Hours = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->createDate)) {
            $object->createDate = $this->createDate;
        }
        if (!is_null($this->numberOfCardOnFileCreationAttemptsLast24Hours)) {
            $object->numberOfCardOnFileCreationAttemptsLast24Hours = $this->numberOfCardOnFileCreationAttemptsLast24Hours;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentAccountOnFile
    {
        parent::fromObject($object);
        if (property_exists($object, 'createDate')) {
            $this->createDate = $object->createDate;
        }
        if (property_exists($object, 'numberOfCardOnFileCreationAttemptsLast24Hours')) {
            $this->numberOfCardOnFileCreationAttemptsLast24Hours = $object->numberOfCardOnFileCreationAttemptsLast24Hours;
        }
        return $this;
    }
}
