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
class LodgingRoom extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $dailyRoomRate = null;

    /**
     * @var string|null
     */
    public ?string $dailyRoomRateCurrencyCode = null;

    /**
     * @var string|null
     */
    public ?string $dailyRoomTaxAmount = null;

    /**
     * @var string|null
     */
    public ?string $dailyRoomTaxAmountCurrencyCode = null;

    /**
     * @var int|null
     */
    public ?int $numberOfNightsAtRoomRate = null;

    /**
     * @var string|null
     */
    public ?string $roomLocation = null;

    /**
     * @var string|null
     */
    public ?string $roomNumber = null;

    /**
     * @var string|null
     */
    public ?string $typeOfBed = null;

    /**
     * @var string|null
     */
    public ?string $typeOfRoom = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->dailyRoomRate)) {
            $object->dailyRoomRate = $this->dailyRoomRate;
        }
        if (!is_null($this->dailyRoomRateCurrencyCode)) {
            $object->dailyRoomRateCurrencyCode = $this->dailyRoomRateCurrencyCode;
        }
        if (!is_null($this->dailyRoomTaxAmount)) {
            $object->dailyRoomTaxAmount = $this->dailyRoomTaxAmount;
        }
        if (!is_null($this->dailyRoomTaxAmountCurrencyCode)) {
            $object->dailyRoomTaxAmountCurrencyCode = $this->dailyRoomTaxAmountCurrencyCode;
        }
        if (!is_null($this->numberOfNightsAtRoomRate)) {
            $object->numberOfNightsAtRoomRate = $this->numberOfNightsAtRoomRate;
        }
        if (!is_null($this->roomLocation)) {
            $object->roomLocation = $this->roomLocation;
        }
        if (!is_null($this->roomNumber)) {
            $object->roomNumber = $this->roomNumber;
        }
        if (!is_null($this->typeOfBed)) {
            $object->typeOfBed = $this->typeOfBed;
        }
        if (!is_null($this->typeOfRoom)) {
            $object->typeOfRoom = $this->typeOfRoom;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): LodgingRoom
    {
        parent::fromObject($object);
        if (property_exists($object, 'dailyRoomRate')) {
            $this->dailyRoomRate = $object->dailyRoomRate;
        }
        if (property_exists($object, 'dailyRoomRateCurrencyCode')) {
            $this->dailyRoomRateCurrencyCode = $object->dailyRoomRateCurrencyCode;
        }
        if (property_exists($object, 'dailyRoomTaxAmount')) {
            $this->dailyRoomTaxAmount = $object->dailyRoomTaxAmount;
        }
        if (property_exists($object, 'dailyRoomTaxAmountCurrencyCode')) {
            $this->dailyRoomTaxAmountCurrencyCode = $object->dailyRoomTaxAmountCurrencyCode;
        }
        if (property_exists($object, 'numberOfNightsAtRoomRate')) {
            $this->numberOfNightsAtRoomRate = $object->numberOfNightsAtRoomRate;
        }
        if (property_exists($object, 'roomLocation')) {
            $this->roomLocation = $object->roomLocation;
        }
        if (property_exists($object, 'roomNumber')) {
            $this->roomNumber = $object->roomNumber;
        }
        if (property_exists($object, 'typeOfBed')) {
            $this->typeOfBed = $object->typeOfBed;
        }
        if (property_exists($object, 'typeOfRoom')) {
            $this->typeOfRoom = $object->typeOfRoom;
        }
        return $this;
    }
}
