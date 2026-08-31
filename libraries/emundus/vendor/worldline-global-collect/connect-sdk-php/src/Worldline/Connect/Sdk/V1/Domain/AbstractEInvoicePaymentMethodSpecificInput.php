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
class AbstractEInvoicePaymentMethodSpecificInput extends AbstractPaymentMethodSpecificInput
{
    /**
     * @var bool|null
     */
    public ?bool $requiresApproval = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->requiresApproval)) {
            $object->requiresApproval = $this->requiresApproval;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AbstractEInvoicePaymentMethodSpecificInput
    {
        parent::fromObject($object);
        if (property_exists($object, 'requiresApproval')) {
            $this->requiresApproval = $object->requiresApproval;
        }
        return $this;
    }
}
