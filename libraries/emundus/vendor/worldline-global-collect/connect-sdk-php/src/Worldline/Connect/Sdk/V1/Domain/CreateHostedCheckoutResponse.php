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
class CreateHostedCheckoutResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $RETURNMAC = null;

    /**
     * @var string|null
     */
    public ?string $hostedCheckoutId = null;

    /**
     * @var string[]|null
     */
    public ?array $invalidTokens = null;

    /**
     * @var string|null
     */
    public ?string $merchantReference = null;

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
        if (!is_null($this->hostedCheckoutId)) {
            $object->hostedCheckoutId = $this->hostedCheckoutId;
        }
        if (!is_null($this->invalidTokens)) {
            $object->invalidTokens = [];
            foreach ($this->invalidTokens as $element) {
                if (!is_null($element)) {
                    $object->invalidTokens[] = $element;
                }
            }
        }
        if (!is_null($this->merchantReference)) {
            $object->merchantReference = $this->merchantReference;
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
    public function fromObject(object $object): CreateHostedCheckoutResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'RETURNMAC')) {
            $this->RETURNMAC = $object->RETURNMAC;
        }
        if (property_exists($object, 'hostedCheckoutId')) {
            $this->hostedCheckoutId = $object->hostedCheckoutId;
        }
        if (property_exists($object, 'invalidTokens')) {
            if (!is_array($object->invalidTokens) && !is_object($object->invalidTokens)) {
                throw new UnexpectedValueException('value \'' . print_r($object->invalidTokens, true) . '\' is not an array or object');
            }
            $this->invalidTokens = [];
            foreach ($object->invalidTokens as $element) {
                $this->invalidTokens[] = $element;
            }
        }
        if (property_exists($object, 'merchantReference')) {
            $this->merchantReference = $object->merchantReference;
        }
        if (property_exists($object, 'partialRedirectUrl')) {
            $this->partialRedirectUrl = $object->partialRedirectUrl;
        }
        return $this;
    }
}
