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
class LodgingData extends DataObject
{
    /**
     * @var LodgingCharge[]|null
     */
    public ?array $charges = null;

    /**
     * @var string|null
     */
    public ?string $checkInDate = null;

    /**
     * @var string|null
     */
    public ?string $checkOutDate = null;

    /**
     * @var string|null
     */
    public ?string $folioNumber = null;

    /**
     * @var bool|null
     */
    public ?bool $isConfirmedReservation = null;

    /**
     * @var bool|null
     */
    public ?bool $isFacilityFireSafetyConform = null;

    /**
     * @var bool|null
     */
    public ?bool $isNoShow = null;

    /**
     * @var bool|null
     */
    public ?bool $isPreferenceSmokingRoom = null;

    /**
     * @var int|null
     */
    public ?int $numberOfAdults = null;

    /**
     * @var int|null
     */
    public ?int $numberOfNights = null;

    /**
     * @var int|null
     */
    public ?int $numberOfRooms = null;

    /**
     * @var string|null
     */
    public ?string $programCode = null;

    /**
     * @var string|null
     */
    public ?string $propertyCustomerServicePhoneNumber = null;

    /**
     * @var string|null
     */
    public ?string $propertyPhoneNumber = null;

    /**
     * @var string|null
     */
    public ?string $renterName = null;

    /**
     * @var LodgingRoom[]|null
     */
    public ?array $rooms = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->charges)) {
            $object->charges = [];
            foreach ($this->charges as $element) {
                if (!is_null($element)) {
                    $object->charges[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->checkInDate)) {
            $object->checkInDate = $this->checkInDate;
        }
        if (!is_null($this->checkOutDate)) {
            $object->checkOutDate = $this->checkOutDate;
        }
        if (!is_null($this->folioNumber)) {
            $object->folioNumber = $this->folioNumber;
        }
        if (!is_null($this->isConfirmedReservation)) {
            $object->isConfirmedReservation = $this->isConfirmedReservation;
        }
        if (!is_null($this->isFacilityFireSafetyConform)) {
            $object->isFacilityFireSafetyConform = $this->isFacilityFireSafetyConform;
        }
        if (!is_null($this->isNoShow)) {
            $object->isNoShow = $this->isNoShow;
        }
        if (!is_null($this->isPreferenceSmokingRoom)) {
            $object->isPreferenceSmokingRoom = $this->isPreferenceSmokingRoom;
        }
        if (!is_null($this->numberOfAdults)) {
            $object->numberOfAdults = $this->numberOfAdults;
        }
        if (!is_null($this->numberOfNights)) {
            $object->numberOfNights = $this->numberOfNights;
        }
        if (!is_null($this->numberOfRooms)) {
            $object->numberOfRooms = $this->numberOfRooms;
        }
        if (!is_null($this->programCode)) {
            $object->programCode = $this->programCode;
        }
        if (!is_null($this->propertyCustomerServicePhoneNumber)) {
            $object->propertyCustomerServicePhoneNumber = $this->propertyCustomerServicePhoneNumber;
        }
        if (!is_null($this->propertyPhoneNumber)) {
            $object->propertyPhoneNumber = $this->propertyPhoneNumber;
        }
        if (!is_null($this->renterName)) {
            $object->renterName = $this->renterName;
        }
        if (!is_null($this->rooms)) {
            $object->rooms = [];
            foreach ($this->rooms as $element) {
                if (!is_null($element)) {
                    $object->rooms[] = $element->toObject();
                }
            }
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): LodgingData
    {
        parent::fromObject($object);
        if (property_exists($object, 'charges')) {
            if (!is_array($object->charges) && !is_object($object->charges)) {
                throw new UnexpectedValueException('value \'' . print_r($object->charges, true) . '\' is not an array or object');
            }
            $this->charges = [];
            foreach ($object->charges as $element) {
                $value = new LodgingCharge();
                $this->charges[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'checkInDate')) {
            $this->checkInDate = $object->checkInDate;
        }
        if (property_exists($object, 'checkOutDate')) {
            $this->checkOutDate = $object->checkOutDate;
        }
        if (property_exists($object, 'folioNumber')) {
            $this->folioNumber = $object->folioNumber;
        }
        if (property_exists($object, 'isConfirmedReservation')) {
            $this->isConfirmedReservation = $object->isConfirmedReservation;
        }
        if (property_exists($object, 'isFacilityFireSafetyConform')) {
            $this->isFacilityFireSafetyConform = $object->isFacilityFireSafetyConform;
        }
        if (property_exists($object, 'isNoShow')) {
            $this->isNoShow = $object->isNoShow;
        }
        if (property_exists($object, 'isPreferenceSmokingRoom')) {
            $this->isPreferenceSmokingRoom = $object->isPreferenceSmokingRoom;
        }
        if (property_exists($object, 'numberOfAdults')) {
            $this->numberOfAdults = $object->numberOfAdults;
        }
        if (property_exists($object, 'numberOfNights')) {
            $this->numberOfNights = $object->numberOfNights;
        }
        if (property_exists($object, 'numberOfRooms')) {
            $this->numberOfRooms = $object->numberOfRooms;
        }
        if (property_exists($object, 'programCode')) {
            $this->programCode = $object->programCode;
        }
        if (property_exists($object, 'propertyCustomerServicePhoneNumber')) {
            $this->propertyCustomerServicePhoneNumber = $object->propertyCustomerServicePhoneNumber;
        }
        if (property_exists($object, 'propertyPhoneNumber')) {
            $this->propertyPhoneNumber = $object->propertyPhoneNumber;
        }
        if (property_exists($object, 'renterName')) {
            $this->renterName = $object->renterName;
        }
        if (property_exists($object, 'rooms')) {
            if (!is_array($object->rooms) && !is_object($object->rooms)) {
                throw new UnexpectedValueException('value \'' . print_r($object->rooms, true) . '\' is not an array or object');
            }
            $this->rooms = [];
            foreach ($object->rooms as $element) {
                $value = new LodgingRoom();
                $this->rooms[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
