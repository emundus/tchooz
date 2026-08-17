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
class LineItemInvoiceData extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * @var string|null
     */
    public ?string $merchantLinenumber = null;

    /**
     * @var string|null
     */
    public ?string $merchantPagenumber = null;

    /**
     * @var string|null
     */
    public ?string $nrOfItems = null;

    /**
     * @var int|null
     */
    public ?int $pricePerItem = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->description)) {
            $object->description = $this->description;
        }
        if (!is_null($this->merchantLinenumber)) {
            $object->merchantLinenumber = $this->merchantLinenumber;
        }
        if (!is_null($this->merchantPagenumber)) {
            $object->merchantPagenumber = $this->merchantPagenumber;
        }
        if (!is_null($this->nrOfItems)) {
            $object->nrOfItems = $this->nrOfItems;
        }
        if (!is_null($this->pricePerItem)) {
            $object->pricePerItem = $this->pricePerItem;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): LineItemInvoiceData
    {
        parent::fromObject($object);
        if (property_exists($object, 'description')) {
            $this->description = $object->description;
        }
        if (property_exists($object, 'merchantLinenumber')) {
            $this->merchantLinenumber = $object->merchantLinenumber;
        }
        if (property_exists($object, 'merchantPagenumber')) {
            $this->merchantPagenumber = $object->merchantPagenumber;
        }
        if (property_exists($object, 'nrOfItems')) {
            $this->nrOfItems = $object->nrOfItems;
        }
        if (property_exists($object, 'pricePerItem')) {
            $this->pricePerItem = $object->pricePerItem;
        }
        return $this;
    }
}
