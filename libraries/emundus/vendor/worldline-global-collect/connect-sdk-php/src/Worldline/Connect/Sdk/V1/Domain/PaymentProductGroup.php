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
class PaymentProductGroup extends DataObject
{
    /**
     * @var AccountOnFile[]|null
     */
    public ?array $accountsOnFile = null;

    /**
     * @var bool|null
     */
    public ?bool $allowsClickToPay = null;

    /**
     * @var bool|null
     */
    public ?bool $allowsInstallments = null;

    /**
     * @var ClickToPayConfiguration|null
     */
    public ?ClickToPayConfiguration $clickToPayConfiguration = null;

    /**
     * @var bool|null
     */
    public ?bool $deviceFingerprintEnabled = null;

    /**
     * @var PaymentProductDisplayHints|null
     */
    public ?PaymentProductDisplayHints $displayHints = null;

    /**
     * @var PaymentProductField[]|null
     */
    public ?array $fields = null;

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->accountsOnFile)) {
            $object->accountsOnFile = [];
            foreach ($this->accountsOnFile as $element) {
                if (!is_null($element)) {
                    $object->accountsOnFile[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->allowsClickToPay)) {
            $object->allowsClickToPay = $this->allowsClickToPay;
        }
        if (!is_null($this->allowsInstallments)) {
            $object->allowsInstallments = $this->allowsInstallments;
        }
        if (!is_null($this->clickToPayConfiguration)) {
            $object->clickToPayConfiguration = $this->clickToPayConfiguration->toObject();
        }
        if (!is_null($this->deviceFingerprintEnabled)) {
            $object->deviceFingerprintEnabled = $this->deviceFingerprintEnabled;
        }
        if (!is_null($this->displayHints)) {
            $object->displayHints = $this->displayHints->toObject();
        }
        if (!is_null($this->fields)) {
            $object->fields = [];
            foreach ($this->fields as $element) {
                if (!is_null($element)) {
                    $object->fields[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->id)) {
            $object->id = $this->id;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProductGroup
    {
        parent::fromObject($object);
        if (property_exists($object, 'accountsOnFile')) {
            if (!is_array($object->accountsOnFile) && !is_object($object->accountsOnFile)) {
                throw new UnexpectedValueException('value \'' . print_r($object->accountsOnFile, true) . '\' is not an array or object');
            }
            $this->accountsOnFile = [];
            foreach ($object->accountsOnFile as $element) {
                $value = new AccountOnFile();
                $this->accountsOnFile[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'allowsClickToPay')) {
            $this->allowsClickToPay = $object->allowsClickToPay;
        }
        if (property_exists($object, 'allowsInstallments')) {
            $this->allowsInstallments = $object->allowsInstallments;
        }
        if (property_exists($object, 'clickToPayConfiguration')) {
            if (!is_object($object->clickToPayConfiguration)) {
                throw new UnexpectedValueException('value \'' . print_r($object->clickToPayConfiguration, true) . '\' is not an object');
            }
            $value = new ClickToPayConfiguration();
            $this->clickToPayConfiguration = $value->fromObject($object->clickToPayConfiguration);
        }
        if (property_exists($object, 'deviceFingerprintEnabled')) {
            $this->deviceFingerprintEnabled = $object->deviceFingerprintEnabled;
        }
        if (property_exists($object, 'displayHints')) {
            if (!is_object($object->displayHints)) {
                throw new UnexpectedValueException('value \'' . print_r($object->displayHints, true) . '\' is not an object');
            }
            $value = new PaymentProductDisplayHints();
            $this->displayHints = $value->fromObject($object->displayHints);
        }
        if (property_exists($object, 'fields')) {
            if (!is_array($object->fields) && !is_object($object->fields)) {
                throw new UnexpectedValueException('value \'' . print_r($object->fields, true) . '\' is not an array or object');
            }
            $this->fields = [];
            foreach ($object->fields as $element) {
                $value = new PaymentProductField();
                $this->fields[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'id')) {
            $this->id = $object->id;
        }
        return $this;
    }
}
