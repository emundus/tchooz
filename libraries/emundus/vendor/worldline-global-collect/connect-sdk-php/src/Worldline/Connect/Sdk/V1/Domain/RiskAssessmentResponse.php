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
class RiskAssessmentResponse extends DataObject
{
    /**
     * @var ResultDoRiskAssessment[]|null
     */
    public ?array $results = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->results)) {
            $object->results = [];
            foreach ($this->results as $element) {
                if (!is_null($element)) {
                    $object->results[] = $element->toObject();
                }
            }
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RiskAssessmentResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'results')) {
            if (!is_array($object->results) && !is_object($object->results)) {
                throw new UnexpectedValueException('value \'' . print_r($object->results, true) . '\' is not an array or object');
            }
            $this->results = [];
            foreach ($object->results as $element) {
                $value = new ResultDoRiskAssessment();
                $this->results[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
