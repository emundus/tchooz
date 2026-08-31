<?php
/*
 * This class was auto-generated from the API references found at
 * https://apireference.connect.worldline-solutions.com/
 */
namespace Worldline\Connect\Sdk\V1;

use Worldline\Connect\Sdk\Domain\DataObject;

/**
 * Class PlatformException
 *
 * @package Worldline\Connect\Sdk\V1
 */
class PlatformException extends ApiException
{
    /**
     * @param int         $httpStatusCode
     * @param DataObject  $response
     * @param string|null $message
     */
    public function __construct(int $httpStatusCode, DataObject $response, ?string $message = null)
    {
        if (is_null($message)) {
            $message = 'the Worldline Global Collect platform returned an error response';
        }
        parent::__construct($httpStatusCode, $response, $message);
    }
}
