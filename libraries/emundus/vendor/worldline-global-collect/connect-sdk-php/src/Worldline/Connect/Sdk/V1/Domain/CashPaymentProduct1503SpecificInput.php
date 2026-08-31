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
 *
 * @deprecated No replacement
 */
class CashPaymentProduct1503SpecificInput extends DataObject
{
    /**
     * @var string|null
     *
     * @deprecated No replacement, since Boleto Bancario no longer needs a return URL
     */
    public ?string $returnUrl = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->returnUrl)) {
            $object->returnUrl = $this->returnUrl;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CashPaymentProduct1503SpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'returnUrl')) {
            $this->returnUrl = $object->returnUrl;
        }
        return $this;
    }
}
