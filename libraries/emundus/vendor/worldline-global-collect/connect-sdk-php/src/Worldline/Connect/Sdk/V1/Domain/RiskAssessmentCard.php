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
class RiskAssessmentCard extends RiskAssessment
{
    /**
     * @var Card|null
     */
    public ?Card $card = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->card)) {
            $object->card = $this->card->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RiskAssessmentCard
    {
        parent::fromObject($object);
        if (property_exists($object, 'card')) {
            if (!is_object($object->card)) {
                throw new UnexpectedValueException('value \'' . print_r($object->card, true) . '\' is not an object');
            }
            $value = new Card();
            $this->card = $value->fromObject($object->card);
        }
        return $this;
    }
}
