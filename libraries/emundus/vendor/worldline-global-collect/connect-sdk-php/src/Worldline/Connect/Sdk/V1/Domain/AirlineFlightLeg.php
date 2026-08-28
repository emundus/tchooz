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
class AirlineFlightLeg extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $airlineClass = null;

    /**
     * @var string|null
     */
    public ?string $arrivalAirport = null;

    /**
     * @var string|null
     */
    public ?string $arrivalTime = null;

    /**
     * @var string|null
     */
    public ?string $carrierCode = null;

    /**
     * @var string|null
     */
    public ?string $conjunctionTicket = null;

    /**
     * @var string|null
     */
    public ?string $couponNumber = null;

    /**
     * @var string|null
     */
    public ?string $date = null;

    /**
     * @var string|null
     */
    public ?string $departureTime = null;

    /**
     * @var string|null
     */
    public ?string $endorsementOrRestriction = null;

    /**
     * @var string|null
     */
    public ?string $exchangeTicket = null;

    /**
     * @var string|null
     */
    public ?string $fare = null;

    /**
     * @var string|null
     */
    public ?string $fareBasis = null;

    /**
     * @var int|null
     */
    public ?int $fee = null;

    /**
     * @var string|null
     */
    public ?string $flightNumber = null;

    /**
     * @var int|null
     */
    public ?int $number = null;

    /**
     * @var string|null
     */
    public ?string $originAirport = null;

    /**
     * @var string|null
     */
    public ?string $passengerClass = null;

    /**
     * @var string|null
     *
     * @deprecated Use passengerClass instead
     */
    public ?string $serviceClass = null;

    /**
     * @var string|null
     */
    public ?string $stopoverCode = null;

    /**
     * @var int|null
     */
    public ?int $taxes = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->airlineClass)) {
            $object->airlineClass = $this->airlineClass;
        }
        if (!is_null($this->arrivalAirport)) {
            $object->arrivalAirport = $this->arrivalAirport;
        }
        if (!is_null($this->arrivalTime)) {
            $object->arrivalTime = $this->arrivalTime;
        }
        if (!is_null($this->carrierCode)) {
            $object->carrierCode = $this->carrierCode;
        }
        if (!is_null($this->conjunctionTicket)) {
            $object->conjunctionTicket = $this->conjunctionTicket;
        }
        if (!is_null($this->couponNumber)) {
            $object->couponNumber = $this->couponNumber;
        }
        if (!is_null($this->date)) {
            $object->date = $this->date;
        }
        if (!is_null($this->departureTime)) {
            $object->departureTime = $this->departureTime;
        }
        if (!is_null($this->endorsementOrRestriction)) {
            $object->endorsementOrRestriction = $this->endorsementOrRestriction;
        }
        if (!is_null($this->exchangeTicket)) {
            $object->exchangeTicket = $this->exchangeTicket;
        }
        if (!is_null($this->fare)) {
            $object->fare = $this->fare;
        }
        if (!is_null($this->fareBasis)) {
            $object->fareBasis = $this->fareBasis;
        }
        if (!is_null($this->fee)) {
            $object->fee = $this->fee;
        }
        if (!is_null($this->flightNumber)) {
            $object->flightNumber = $this->flightNumber;
        }
        if (!is_null($this->number)) {
            $object->number = $this->number;
        }
        if (!is_null($this->originAirport)) {
            $object->originAirport = $this->originAirport;
        }
        if (!is_null($this->passengerClass)) {
            $object->passengerClass = $this->passengerClass;
        }
        if (!is_null($this->serviceClass)) {
            $object->serviceClass = $this->serviceClass;
        }
        if (!is_null($this->stopoverCode)) {
            $object->stopoverCode = $this->stopoverCode;
        }
        if (!is_null($this->taxes)) {
            $object->taxes = $this->taxes;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): AirlineFlightLeg
    {
        parent::fromObject($object);
        if (property_exists($object, 'airlineClass')) {
            $this->airlineClass = $object->airlineClass;
        }
        if (property_exists($object, 'arrivalAirport')) {
            $this->arrivalAirport = $object->arrivalAirport;
        }
        if (property_exists($object, 'arrivalTime')) {
            $this->arrivalTime = $object->arrivalTime;
        }
        if (property_exists($object, 'carrierCode')) {
            $this->carrierCode = $object->carrierCode;
        }
        if (property_exists($object, 'conjunctionTicket')) {
            $this->conjunctionTicket = $object->conjunctionTicket;
        }
        if (property_exists($object, 'couponNumber')) {
            $this->couponNumber = $object->couponNumber;
        }
        if (property_exists($object, 'date')) {
            $this->date = $object->date;
        }
        if (property_exists($object, 'departureTime')) {
            $this->departureTime = $object->departureTime;
        }
        if (property_exists($object, 'endorsementOrRestriction')) {
            $this->endorsementOrRestriction = $object->endorsementOrRestriction;
        }
        if (property_exists($object, 'exchangeTicket')) {
            $this->exchangeTicket = $object->exchangeTicket;
        }
        if (property_exists($object, 'fare')) {
            $this->fare = $object->fare;
        }
        if (property_exists($object, 'fareBasis')) {
            $this->fareBasis = $object->fareBasis;
        }
        if (property_exists($object, 'fee')) {
            $this->fee = $object->fee;
        }
        if (property_exists($object, 'flightNumber')) {
            $this->flightNumber = $object->flightNumber;
        }
        if (property_exists($object, 'number')) {
            $this->number = $object->number;
        }
        if (property_exists($object, 'originAirport')) {
            $this->originAirport = $object->originAirport;
        }
        if (property_exists($object, 'passengerClass')) {
            $this->passengerClass = $object->passengerClass;
        }
        if (property_exists($object, 'serviceClass')) {
            $this->serviceClass = $object->serviceClass;
        }
        if (property_exists($object, 'stopoverCode')) {
            $this->stopoverCode = $object->stopoverCode;
        }
        if (property_exists($object, 'taxes')) {
            $this->taxes = $object->taxes;
        }
        return $this;
    }
}
