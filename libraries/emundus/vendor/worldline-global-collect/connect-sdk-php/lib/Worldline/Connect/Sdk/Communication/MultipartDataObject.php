<?php
namespace Worldline\Connect\Sdk\Communication;

use Exception;

/**
 * Class MultipartDataObject
 *
 * @package Worldline\Connect\Sdk\Communication
 */
abstract class MultipartDataObject
{
    /**
     * @return MultipartFormDataObject
     */
    abstract public function toMultipartFormDataObject(): MultipartFormDataObject;

    public function __set($name, $value)
    {
        throw new Exception('Cannot add new property ' . $name . ' to instances of class ' . get_class($this));
    }
}
