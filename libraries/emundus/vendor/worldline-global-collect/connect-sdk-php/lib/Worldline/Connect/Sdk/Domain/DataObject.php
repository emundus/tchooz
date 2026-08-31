<?php
namespace Worldline\Connect\Sdk\Domain;

use Exception;
use stdClass;
use UnexpectedValueException;
use Worldline\Connect\Sdk\JSON\JSONUtil;

/**
 * Class DataObject
 *
 * @package Worldline\Connect\Sdk\Domain
 */
abstract class DataObject
{
    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toObject());
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromJson(string $value): DataObject
    {
        $object = JSONUtil::decode($value);
        return $this->fromObject($object);
    }

    /**
     * @return object
     */
    public function toObject(): object
    {
        return new stdClass();
    }

    /**
     * @param object $object
     *
     * @return $this
     * @throws UnexpectedValueException
     */
    public function fromObject(object $object): DataObject
    {
        return $this;
    }

    public function __set($name, $value)
    {
        throw new Exception('Cannot add new property ' . $name . ' to instances of class ' . get_class($this));
    }
}
