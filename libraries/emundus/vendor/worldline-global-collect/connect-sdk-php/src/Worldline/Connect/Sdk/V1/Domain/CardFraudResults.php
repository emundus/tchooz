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
class CardFraudResults extends FraudResults
{
    /**
     * @var string|null
     */
    public ?string $avsResult = null;

    /**
     * @var string|null
     */
    public ?string $cvvResult = null;

    /**
     * @var FraugsterResults|null
     */
    public ?FraugsterResults $fraugster = null;

    /**
     * @var FraudResultsRetailDecisions|null
     */
    public ?FraudResultsRetailDecisions $retailDecisions = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->avsResult)) {
            $object->avsResult = $this->avsResult;
        }
        if (!is_null($this->cvvResult)) {
            $object->cvvResult = $this->cvvResult;
        }
        if (!is_null($this->fraugster)) {
            $object->fraugster = $this->fraugster->toObject();
        }
        if (!is_null($this->retailDecisions)) {
            $object->retailDecisions = $this->retailDecisions->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CardFraudResults
    {
        parent::fromObject($object);
        if (property_exists($object, 'avsResult')) {
            $this->avsResult = $object->avsResult;
        }
        if (property_exists($object, 'cvvResult')) {
            $this->cvvResult = $object->cvvResult;
        }
        if (property_exists($object, 'fraugster')) {
            if (!is_object($object->fraugster)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fraugster, true) . '\' is not an object');
            }
            $value = new FraugsterResults();
            $this->fraugster = $value->fromObject($object->fraugster);
        }
        if (property_exists($object, 'retailDecisions')) {
            if (!is_object($object->retailDecisions)) {
                throw new UnexpectedValueException('value \'' . print_r($object->retailDecisions, true) . '\' is not an object');
            }
            $value = new FraudResultsRetailDecisions();
            $this->retailDecisions = $value->fromObject($object->retailDecisions);
        }
        return $this;
    }
}
