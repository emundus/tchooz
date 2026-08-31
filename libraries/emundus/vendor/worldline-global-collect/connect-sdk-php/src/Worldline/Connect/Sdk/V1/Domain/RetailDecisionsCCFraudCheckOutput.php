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
class RetailDecisionsCCFraudCheckOutput extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $fraudCode = null;

    /**
     * @var string|null
     */
    public ?string $fraudNeural = null;

    /**
     * @var string|null
     */
    public ?string $fraudRCF = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->fraudCode)) {
            $object->fraudCode = $this->fraudCode;
        }
        if (!is_null($this->fraudNeural)) {
            $object->fraudNeural = $this->fraudNeural;
        }
        if (!is_null($this->fraudRCF)) {
            $object->fraudRCF = $this->fraudRCF;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RetailDecisionsCCFraudCheckOutput
    {
        parent::fromObject($object);
        if (property_exists($object, 'fraudCode')) {
            $this->fraudCode = $object->fraudCode;
        }
        if (property_exists($object, 'fraudNeural')) {
            $this->fraudNeural = $object->fraudNeural;
        }
        if (property_exists($object, 'fraudRCF')) {
            $this->fraudRCF = $object->fraudRCF;
        }
        return $this;
    }
}
