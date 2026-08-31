<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1;

use Worldline\Connect\Sdk\Domain\DataObject;

/**
 * Class ReferenceException
 *
 * @package Worldline\Connect\Sdk\V1
 */
class ReferenceException extends ResponseException
{
    /**
     * @param int         $httpStatusCode
     * @param DataObject  $response
     * @param string|null $message
     */
    public function __construct(int $httpStatusCode, DataObject $response, ?string $message = null)
    {
        if (is_null($message)) {
            $message = 'the Worldline Global Collect platform returned a reference error response';
        }
        parent::__construct($httpStatusCode, $response, $message);
    }
}
