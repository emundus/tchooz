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
class PaymentProductFieldDisplayHints extends DataObject
{
    /**
     * @var bool|null
     */
    public ?bool $alwaysShow = null;

    /**
     * @var int|null
     */
    public ?int $displayOrder = null;

    /**
     * @var PaymentProductFieldFormElement|null
     */
    public ?PaymentProductFieldFormElement $formElement = null;

    /**
     * @var string|null
     */
    public ?string $label = null;

    /**
     * @var string|null
     */
    public ?string $link = null;

    /**
     * @var string|null
     */
    public ?string $mask = null;

    /**
     * @var bool|null
     */
    public ?bool $obfuscate = null;

    /**
     * @var string|null
     */
    public ?string $placeholderLabel = null;

    /**
     * @var string|null
     */
    public ?string $preferredInputType = null;

    /**
     * @var PaymentProductFieldTooltip|null
     */
    public ?PaymentProductFieldTooltip $tooltip = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->alwaysShow)) {
            $object->alwaysShow = $this->alwaysShow;
        }
        if (!is_null($this->displayOrder)) {
            $object->displayOrder = $this->displayOrder;
        }
        if (!is_null($this->formElement)) {
            $object->formElement = $this->formElement->toObject();
        }
        if (!is_null($this->label)) {
            $object->label = $this->label;
        }
        if (!is_null($this->link)) {
            $object->link = $this->link;
        }
        if (!is_null($this->mask)) {
            $object->mask = $this->mask;
        }
        if (!is_null($this->obfuscate)) {
            $object->obfuscate = $this->obfuscate;
        }
        if (!is_null($this->placeholderLabel)) {
            $object->placeholderLabel = $this->placeholderLabel;
        }
        if (!is_null($this->preferredInputType)) {
            $object->preferredInputType = $this->preferredInputType;
        }
        if (!is_null($this->tooltip)) {
            $object->tooltip = $this->tooltip->toObject();
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): PaymentProductFieldDisplayHints
    {
        parent::fromObject($object);
        if (property_exists($object, 'alwaysShow')) {
            $this->alwaysShow = $object->alwaysShow;
        }
        if (property_exists($object, 'displayOrder')) {
            $this->displayOrder = $object->displayOrder;
        }
        if (property_exists($object, 'formElement')) {
            if (!is_object($object->formElement)) {
                throw new UnexpectedValueException('value \'' . print_r($object->formElement, true) . '\' is not an object');
            }
            $value = new PaymentProductFieldFormElement();
            $this->formElement = $value->fromObject($object->formElement);
        }
        if (property_exists($object, 'label')) {
            $this->label = $object->label;
        }
        if (property_exists($object, 'link')) {
            $this->link = $object->link;
        }
        if (property_exists($object, 'mask')) {
            $this->mask = $object->mask;
        }
        if (property_exists($object, 'obfuscate')) {
            $this->obfuscate = $object->obfuscate;
        }
        if (property_exists($object, 'placeholderLabel')) {
            $this->placeholderLabel = $object->placeholderLabel;
        }
        if (property_exists($object, 'preferredInputType')) {
            $this->preferredInputType = $object->preferredInputType;
        }
        if (property_exists($object, 'tooltip')) {
            if (!is_object($object->tooltip)) {
                throw new UnexpectedValueException('value \'' . print_r($object->tooltip, true) . '\' is not an object');
            }
            $value = new PaymentProductFieldTooltip();
            $this->tooltip = $value->fromObject($object->tooltip);
        }
        return $this;
    }
}
