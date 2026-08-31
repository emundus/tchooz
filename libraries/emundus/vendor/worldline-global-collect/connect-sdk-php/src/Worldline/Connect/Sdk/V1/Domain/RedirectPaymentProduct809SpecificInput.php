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
class RedirectPaymentProduct809SpecificInput extends DataObject
{
    /**
     * @var string|null
     *
     * @deprecated Use RedirectPaymentMethodSpecificInput.expirationPeriod instead
     */
    public ?string $expirationPeriod = null;

    /**
     * @var string|null
     */
    public ?string $issuerId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->expirationPeriod)) {
            $object->expirationPeriod = $this->expirationPeriod;
        }
        if (!is_null($this->issuerId)) {
            $object->issuerId = $this->issuerId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): RedirectPaymentProduct809SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'expirationPeriod')) {
            $this->expirationPeriod = $object->expirationPeriod;
        }
        if (property_exists($object, 'issuerId')) {
            $this->issuerId = $object->issuerId;
        }
        return $this;
    }
}
