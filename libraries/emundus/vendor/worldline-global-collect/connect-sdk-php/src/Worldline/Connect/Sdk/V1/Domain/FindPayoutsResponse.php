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
class FindPayoutsResponse extends DataObject
{
    /**
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * @var int|null
     */
    public ?int $offset = null;

    /**
     * @var PayoutResult[]|null
     */
    public ?array $payouts = null;

    /**
     * @var int|null
     */
    public ?int $totalCount = null;

    /**
     * @return object
     */
    public function toObject(): object
    {
        $object = parent::toObject();
        if (!is_null($this->limit)) {
            $object->limit = $this->limit;
        }
        if (!is_null($this->offset)) {
            $object->offset = $this->offset;
        }
        if (!is_null($this->payouts)) {
            $object->payouts = [];
            foreach ($this->payouts as $element) {
                if (!is_null($element)) {
                    $object->payouts[] = $element->toObject();
                }
            }
        }
        if (!is_null($this->totalCount)) {
            $object->totalCount = $this->totalCount;
        }
        return $object;
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): FindPayoutsResponse
    {
        parent::fromObject($object);
        if (property_exists($object, 'limit')) {
            $this->limit = $object->limit;
        }
        if (property_exists($object, 'offset')) {
            $this->offset = $object->offset;
        }
        if (property_exists($object, 'payouts')) {
            if (!is_array($object->payouts) && !is_object($object->payouts)) {
                throw new UnexpectedValueException('value \'' . print_r($object->payouts, true) . '\' is not an array or object');
            }
            $this->payouts = [];
            foreach ($object->payouts as $element) {
                $value = new PayoutResult();
                $this->payouts[] = $value->fromObject($element);
            }
        }
        if (property_exists($object, 'totalCount')) {
            $this->totalCount = $object->totalCount;
        }
        return $this;
    }
}
