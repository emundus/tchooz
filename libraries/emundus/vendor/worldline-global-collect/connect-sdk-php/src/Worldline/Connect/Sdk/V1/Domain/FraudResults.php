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
class FraudResults extends DataObject
{
    /**
     * @var CybersourceDecisionManager|null
     */
    public ?CybersourceDecisionManager $cybersourceDecisionManager = null;

    /**
     * @var string|null
     */
    public ?string $fraudServiceResult = null;

    /**
     * @var InAuth|null
     */
    public ?InAuth $inAuth = null;

    /**
     * @var MicrosoftFraudResults|null
     */
    public ?MicrosoftFraudResults $microsoftFraudProtection = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->cybersourceDecisionManager)) {
            $object->cybersourceDecisionManager = $this->cybersourceDecisionManager->toObject();
        }
        if (!is_null($this->fraudServiceResult)) {
            $object->fraudServiceResult = $this->fraudServiceResult;
        }
        if (!is_null($this->inAuth)) {
            $object->inAuth = $this->inAuth->toObject();
        }
        if (!is_null($this->microsoftFraudProtection)) {
            $object->microsoftFraudProtection = $this->microsoftFraudProtection->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): FraudResults
    {
        parent::fromObject($object);
        if (property_exists($object, 'cybersourceDecisionManager')) {
            if (!is_object($object->cybersourceDecisionManager)) {
                throw new UnexpectedValueException('value \'' . print_r($object->cybersourceDecisionManager, true) . '\' is not an object');
            }
            $value = new CybersourceDecisionManager();
            $this->cybersourceDecisionManager = $value->fromObject($object->cybersourceDecisionManager);
        }
        if (property_exists($object, 'fraudServiceResult')) {
            $this->fraudServiceResult = $object->fraudServiceResult;
        }
        if (property_exists($object, 'inAuth')) {
            if (!is_object($object->inAuth)) {
                throw new UnexpectedValueException('value \'' . print_r($object->inAuth, true) . '\' is not an object');
            }
            $value = new InAuth();
            $this->inAuth = $value->fromObject($object->inAuth);
        }
        if (property_exists($object, 'microsoftFraudProtection')) {
            if (!is_object($object->microsoftFraudProtection)) {
                throw new UnexpectedValueException('value \'' . print_r($object->microsoftFraudProtection, true) . '\' is not an object');
            }
            $value = new MicrosoftFraudResults();
            $this->microsoftFraudProtection = $value->fromObject($object->microsoftFraudProtection);
        }
        return $this;
    }
}
