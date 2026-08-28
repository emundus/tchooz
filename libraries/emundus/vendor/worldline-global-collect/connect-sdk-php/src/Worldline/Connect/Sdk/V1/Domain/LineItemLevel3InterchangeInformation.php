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
class LineItemLevel3InterchangeInformation extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $discountAmount = null;

    /**
     * @var int|null
     */
    public ?int $lineAmountTotal = null;

    /**
     * @var string|null
     */
    public ?string $productCode = null;

    /**
     * @var int|null
     */
    public ?int $productPrice = null;

    /**
     * @var string|null
     */
    public ?string $productType = null;

    /**
     * @var int|null
     */
    public ?int $quantity = null;

    /**
     * @var int|null
     */
    public ?int $taxAmount = null;

    /**
     * @var string|null
     */
    public ?string $unit = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->discountAmount)) {
            $object->discountAmount = $this->discountAmount;
        }
        if (!is_null($this->lineAmountTotal)) {
            $object->lineAmountTotal = $this->lineAmountTotal;
        }
        if (!is_null($this->productCode)) {
            $object->productCode = $this->productCode;
        }
        if (!is_null($this->productPrice)) {
            $object->productPrice = $this->productPrice;
        }
        if (!is_null($this->productType)) {
            $object->productType = $this->productType;
        }
        if (!is_null($this->quantity)) {
            $object->quantity = $this->quantity;
        }
        if (!is_null($this->taxAmount)) {
            $object->taxAmount = $this->taxAmount;
        }
        if (!is_null($this->unit)) {
            $object->unit = $this->unit;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): LineItemLevel3InterchangeInformation
    {
        parent::fromObject($object);
        if (property_exists($object, 'discountAmount')) {
            $this->discountAmount = $object->discountAmount;
        }
        if (property_exists($object, 'lineAmountTotal')) {
            $this->lineAmountTotal = $object->lineAmountTotal;
        }
        if (property_exists($object, 'productCode')) {
            $this->productCode = $object->productCode;
        }
        if (property_exists($object, 'productPrice')) {
            $this->productPrice = $object->productPrice;
        }
        if (property_exists($object, 'productType')) {
            $this->productType = $object->productType;
        }
        if (property_exists($object, 'quantity')) {
            $this->quantity = $object->quantity;
        }
        if (property_exists($object, 'taxAmount')) {
            $this->taxAmount = $object->taxAmount;
        }
        if (property_exists($object, 'unit')) {
            $this->unit = $object->unit;
        }
        return $this;
    }
}
