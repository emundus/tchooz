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
class GetIINDetailsResponse extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $binProductType = null;

    /**
     * @var IINDetail[]|null
     */
    public ?array $coBrands = null;

    /**
     * @var string|null
     */
    public ?string $countryCode = null;

    /**
     * @var bool|null
     */
    public ?bool $isAllowedInContext = null;

    /**
     * @var string|null
     */
    public ?string $issuerName = null;

    /**
     * @var int|null
     */
    public ?int $paymentProductId = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->binProductType)) {
            $object->binProductType = $this->binProductType;
        }
        if (!is_null($this->coBrands)) {
            $object->coBrands = [];
            foreach ($this->coBrands as $element) {
                if (!is_null($element)) {
                    $object->coBrands[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->countryCode)) {
            $object->countryCode = $this->countryCode;
        }
        if (!is_null($this->isAllowedInContext)) {
            $object->isAllowedInContext = $this->isAllowedInContext;
        }
        if (!is_null($this->issuerName)) {
            $object->issuerName = $this->issuerName;
        }
        if (!is_null($this->paymentProductId)) {
            $object->paymentProductId = $this->paymentProductId;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): GetIINDetailsResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'binProductType')) {
            $this->binProductType = $object->binProductType;
        }
        if (property_exists($object, 'coBrands')) {
            if (!is_array($object->coBrands) && !is_object($object->coBrands)) {
                throw new UnexpectedValueException('value \'' . print_r($object->coBrands, true) . '\' is not an array or object');
            }
            $this->coBrands = [];
            foreach ($object->coBrands as $element) {
                $value = new IINDetail();
                $this->coBrands[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'countryCode')) {
            $this->countryCode = $object->countryCode;
        }
        if (property_exists($object, 'isAllowedInContext')) {
            $this->isAllowedInContext = $object->isAllowedInContext;
        }
        if (property_exists($object, 'issuerName')) {
            $this->issuerName = $object->issuerName;
        }
        if (property_exists($object, 'paymentProductId')) {
            $this->paymentProductId = $object->paymentProductId;
        }
        return $this;
    }
}
