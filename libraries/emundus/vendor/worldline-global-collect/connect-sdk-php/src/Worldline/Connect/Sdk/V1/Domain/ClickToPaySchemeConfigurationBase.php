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
class ClickToPaySchemeConfigurationBase extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $srcInitiatorId = null;

    /**
     * @var string|null
     */
    public ?string $srciDpaId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->srcInitiatorId)) {
            $object->srcInitiatorId = $this->srcInitiatorId;
        }
        if (!is_null($this->srciDpaId)) {
            $object->srciDpaId = $this->srciDpaId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): ClickToPaySchemeConfigurationBase
    {
        parent::fromObject($object);
        if (property_exists($object, 'srcInitiatorId')) {
            $this->srcInitiatorId = $object->srcInitiatorId;
        }
        if (property_exists($object, 'srciDpaId')) {
            $this->srciDpaId = $object->srciDpaId;
        }
        return $this;
    }
}
