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
class CustomerDevice extends DataObject
{
    /**
     * @var string|null
     */
    public ?string $acceptHeader = null;

    /**
     * @var BrowserData|null
     */
    public ?BrowserData $browserData = null;

    /**
     * @var string|null
     */
    public ?string $defaultFormFill = null;

    /**
     * @var string|null
     */
    public ?string $deviceFingerprintTransactionId = null;

    /**
     * @var string|null
     */
    public ?string $ipAddress = null;

    /**
     * @var string|null
     */
    public ?string $locale = null;

    /**
     * @var string|null
     */
    public ?string $timezoneOffsetUtcMinutes = null;

    /**
     * @var string|null
     */
    public ?string $userAgent = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->acceptHeader)) {
            $object->acceptHeader = $this->acceptHeader;
        }
        if (!is_null($this->browserData)) {
            $object->browserData = $this->browserData->toObject();
        }
        if (!is_null($this->defaultFormFill)) {
            $object->defaultFormFill = $this->defaultFormFill;
        }
        if (!is_null($this->deviceFingerprintTransactionId)) {
            $object->deviceFingerprintTransactionId = $this->deviceFingerprintTransactionId;
        }
        if (!is_null($this->ipAddress)) {
            $object->ipAddress = $this->ipAddress;
        }
        if (!is_null($this->locale)) {
            $object->locale = $this->locale;
        }
        if (!is_null($this->timezoneOffsetUtcMinutes)) {
            $object->timezoneOffsetUtcMinutes = $this->timezoneOffsetUtcMinutes;
        }
        if (!is_null($this->userAgent)) {
            $object->userAgent = $this->userAgent;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): CustomerDevice
    {
        parent::fromObject($object);
        if (property_exists($object, 'acceptHeader')) {
            $this->acceptHeader = $object->acceptHeader;
        }
        if (property_exists($object, 'browserData')) {
            if (!is_object($object->browserData)) {
                throw new UnexpectedValueException('value \'' . print_r($object->browserData, true) . '\' is not an object');
            }
            $value = new BrowserData();
            $this->browserData = $value->fromObject($object->browserData);
        }
        if (property_exists($object, 'defaultFormFill')) {
            $this->defaultFormFill = $object->defaultFormFill;
        }
        if (property_exists($object, 'deviceFingerprintTransactionId')) {
            $this->deviceFingerprintTransactionId = $object->deviceFingerprintTransactionId;
        }
        if (property_exists($object, 'ipAddress')) {
            $this->ipAddress = $object->ipAddress;
        }
        if (property_exists($object, 'locale')) {
            $this->locale = $object->locale;
        }
        if (property_exists($object, 'timezoneOffsetUtcMinutes')) {
            $this->timezoneOffsetUtcMinutes = $object->timezoneOffsetUtcMinutes;
        }
        if (property_exists($object, 'userAgent')) {
            $this->userAgent = $object->userAgent;
        }
        return $this;
    }
}
