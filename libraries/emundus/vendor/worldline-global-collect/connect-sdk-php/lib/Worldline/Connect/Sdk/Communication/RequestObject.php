<?php
namespace Worldline\Connect\Sdk\Communication;

use Exception;

/**
 * Class RequestObject
 *
 * @package Worldline\Connect\Sdk\Communication
 */
abstract class RequestObject
{
    public function __set($name, $value)
    {
        throw new Exception('Cannot add new property ' . $name . ' to instances of class ' . get_class($this));
    }
}
