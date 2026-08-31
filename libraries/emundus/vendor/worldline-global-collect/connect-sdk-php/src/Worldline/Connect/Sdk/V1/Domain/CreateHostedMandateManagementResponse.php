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
class CreateHostedMandateManagementResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $RETURNMAC = null;

    /**
     * @var string|null
     */
    public ?string $hostedMandateManagementId = null;

    /**
     * @var string|null
     */
    public ?string $partialRedirectUrl = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->RETURNMAC)) {
            $object->RETURNMAC = $this->RETURNMAC;
        }
        if (!is_null($this->hostedMandateManagementId)) {
            $object->hostedMandateManagementId = $this->hostedMandateManagementId;
        }
        if (!is_null($this->partialRedirectUrl)) {
            $object->partialRedirectUrl = $this->partialRedirectUrl;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CreateHostedMandateManagementResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'RETURNMAC')) {
            $this->RETURNMAC = $object->RETURNMAC;
        }
        if (property_exists($object, 'hostedMandateManagementId')) {
            $this->hostedMandateManagementId = $object->hostedMandateManagementId;
        }
        if (property_exists($object, 'partialRedirectUrl')) {
            $this->partialRedirectUrl = $object->partialRedirectUrl;
        }
        return $this;
    }
}
