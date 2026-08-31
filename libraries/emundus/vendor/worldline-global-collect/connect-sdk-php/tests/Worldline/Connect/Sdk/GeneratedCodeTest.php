<?php
namespace Worldline\Connect\Sdk;

use Worldline\Connect\Sdk\V1\Domain\APIError;
use Worldline\Connect\Sdk\V1\Domain\ErrorResponse;

/**
 * @group generated_code
 *
 */
class GeneratedCodeTest extends \PHPUnit\Framework\TestCase
{
    public function testJsonMarshalling()
    {
        $errorResponse = new ErrorResponse();
        $errorResponse->errorId = '123';
        $apiError = new APIError();
        $apiError->code = '1';
        $apiError->message = 'Test message';
        $apiError->propertyName = 'test';
        $errorResponse->errors = array($apiError);
        $jsonErrorResponse = $errorResponse->toJson();
        $this->assertEquals('{"errorId":"123","errors":[{"code":"1","message":"Test message","propertyName":"test"}]}', $jsonErrorResponse);
        $actualErrorResponse = new ErrorResponse();
        $actualErrorResponse->fromJson($jsonErrorResponse);
        $this->assertEquals($errorResponse, $actualErrorResponse);
    }
}
