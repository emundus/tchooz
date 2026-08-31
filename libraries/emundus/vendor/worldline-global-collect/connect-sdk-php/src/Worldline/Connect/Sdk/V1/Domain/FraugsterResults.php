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
class FraugsterResults extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $fraudInvestigationPoints = null;

    /**
     * @var int|null
     */
    public ?int $fraudScore = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->fraudInvestigationPoints)) {
            $object->fraudInvestigationPoints = $this->fraudInvestigationPoints;
        }
        if (!is_null($this->fraudScore)) {
            $object->fraudScore = $this->fraudScore;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): FraugsterResults
    {
        parent::fromObject($object);
        if (property_exists($object, 'fraudInvestigationPoints')) {
            $this->fraudInvestigationPoints = $object->fraudInvestigationPoints;
        }
        if (property_exists($object, 'fraudScore')) {
            $this->fraudScore = $object->fraudScore;
        }
        return $this;
    }
}
