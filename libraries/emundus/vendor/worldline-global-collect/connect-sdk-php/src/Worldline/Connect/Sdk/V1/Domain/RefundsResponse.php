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
class RefundsResponse extends DataObject
{
    /**
     * @var RefundResult[]|null
     */
    public ?array $refunds = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->refunds)) {
            $object->refunds = [];
            foreach ($this->refunds as $element) {
                if (!is_null($element)) {
                    $object->refunds[] = $element->toObject();
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
    public function fromObject(object $object): RefundsResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'refunds')) {
            if (!is_array($object->refunds) && !is_object($object->refunds)) {
                throw new UnexpectedValueException('value \'' . print_r($object->refunds, true) . '\' is not an array or object');
            }
            $this->refunds = [];
            foreach ($object->refunds as $element) {
                $value = new RefundResult();
                $this->refunds[] = $value->fromObject($element);
            }
        }
        return $this;
    }
}
