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
class ResultDoRiskAssessment extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $category = null;

    /**
     * @var string|null
     */
    public ?string $result = null;

    /**
     * @var RetailDecisionsCCFraudCheckOutput|null
     */
    public ?RetailDecisionsCCFraudCheckOutput $retaildecisionsCCFraudCheckOutput = null;

    /**
     * @var ValidationBankAccountOutput|null
     */
    public ?ValidationBankAccountOutput $validationBankAccountOutput = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->category)) {
            $object->category = $this->category;
        }
        if (!is_null($this->result)) {
            $object->result = $this->result;
        }
        if (!is_null($this->retaildecisionsCCFraudCheckOutput)) {
            $object->retaildecisionsCCFraudCheckOutput = $this->retaildecisionsCCFraudCheckOutput->toObject();
        }
        if (!is_null($this->validationBankAccountOutput)) {
            $object->validationBankAccountOutput = $this->validationBankAccountOutput->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ResultDoRiskAssessment
    {
        parent::fromObject($object);
        if (property_exists($object, 'category')) {
            $this->category = $object->category;
        }
        if (property_exists($object, 'result')) {
            $this->result = $object->result;
        }
        if (property_exists($object, 'retaildecisionsCCFraudCheckOutput')) {
            if (!is_object($object->retaildecisionsCCFraudCheckOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->retaildecisionsCCFraudCheckOutput, true) . '\' is not an object');
            }
            $value = new RetailDecisionsCCFraudCheckOutput();
            $this->retaildecisionsCCFraudCheckOutput = $value->fromObject($object->retaildecisionsCCFraudCheckOutput);
        }
        if (property_exists($object, 'validationBankAccountOutput')) {
            if (!is_object($object->validationBankAccountOutput)) {
                throw new UnexpectedValueException('value \'' . print_r($object->validationBankAccountOutput, true) . '\' is not an object');
            }
            $value = new ValidationBankAccountOutput();
            $this->validationBankAccountOutput = $value->fromObject($object->validationBankAccountOutput);
        }
        return $this;
    }
}
