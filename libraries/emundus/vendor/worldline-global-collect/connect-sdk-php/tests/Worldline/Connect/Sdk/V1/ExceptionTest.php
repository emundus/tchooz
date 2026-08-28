<?php
namespace Worldline\Connect\Sdk\V1;

use Worldline\Connect\Sdk\Client;
use Worldline\Connect\Sdk\Communication\ConnectionResponse;
use Worldline\Connect\Sdk\Communicator;
use Worldline\Connect\Sdk\TestCase;
use Worldline\Connect\Sdk\TestUtil\TestingConnection;
use Worldline\Connect\Sdk\V1\Domain\CreatePaymentResult;
use Worldline\Connect\Sdk\V1\Domain\ErrorResponse;
use Worldline\Connect\Sdk\V1\Domain\GetIINDetailsRequest;
use Worldline\Connect\Sdk\V1\Domain\PaymentErrorResponse;
use Worldline\Connect\Sdk\V1\Domain\PayoutErrorResponse;
use Worldline\Connect\Sdk\V1\Domain\PayoutResult;
use Worldline\Connect\Sdk\V1\Domain\RefundErrorResponse;
use Worldline\Connect\Sdk\V1\Domain\RefundResult;

/**
 * @group exceptions
 */
class ExceptionTest extends TestCase
{
    public function testExceptionWithoutErrors()
    {
        $responseException = new ResponseException(0, new ErrorResponse());
        $this->assertEmpty($responseException->getErrorId());
        $this->assertCount(0, $responseException->getErrors());
    }

    public function testExceptionStringRepresentation()
    {
        $httpStatusCode = 400;
        $errorResponseJsonString = <<<'EOD'
{
    "errorId": "8a41a5dd-7366-4026-a41b-e98c56808edd",
    "errors": [
        {
            "category": "CONNECT_PLATFORM_ERROR",
            "code": "20000000",
            "httpStatusCode": 400,
            "id": "PARAMETER_NOT_FOUND_IN_REQUEST",
            "message": "The parameter shown above was not found in the request received by connect.",
            "propertyName": "bin",
            "requestId": ""
        }
    ]
}
EOD;
        $errorResponse = new ErrorResponse();
        $errorResponse->fromJson($errorResponseJsonString);
        $responseException = new ResponseException($httpStatusCode, $errorResponse);
        $expectedResponseExceptionString = sprintf(
            "exception '%s' with message '%s'. in %s:%d\nHTTP status code: %s\nResponse:\n%s\nStack trace:\n%s",
            'Worldline\Connect\Sdk\V1\ResponseException',
            $responseException->getMessage(),
            $responseException->getFile(),
            $responseException->getLine(),
            $httpStatusCode,
            $errorResponseJsonString,
            $responseException->getTraceAsString()
        );
        // replace Windows \r\n with Unix \n before comparing
        $this->assertEquals(str_replace("\r\n", "\n", $expectedResponseExceptionString), str_replace("\r\n", "\n", (string) $responseException));
    }

    public function testValidationException()
    {
        $this->expectException(ValidationException::class);

        $errorResponseJsonString = <<<'EOD'
{
    "errorId": "8a41a5dd-7366-4026-a41b-e98c56808edd",
    "errors": [
        {
            "category": "CONNECT_PLATFORM_ERROR",
            "code": "20000000",
            "httpStatusCode": 400,
            "id": "PARAMETER_NOT_FOUND_IN_REQUEST",
            "message": "The parameter shown above was not found in the request received by connect.",
            "propertyName": "bin",
            "requestId": ""
        }
    ]
}
EOD;
        $responseHeaders = array('Content-Type' => 'application/json');
        $connectionResponse = new ConnectionResponse(400, $responseHeaders, $errorResponseJsonString);
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicator = new Communicator($communicatorConfiguration, null, new TestingConnection($connectionResponse));
        $client = new Client($communicator);
        $emptyBody = new GetIINDetailsRequest();
        $client->v1()->merchant($this->getMerchantId())->services()->getIINdetails($emptyBody);
    }

    public function testDeclinedPaymentException()
    {
        $paymentErrorResponse = new PaymentErrorResponse();
        $declinedPaymentException = new DeclinedPaymentException(0, $paymentErrorResponse);
        $this->assertInstanceOf('\Worldline\Connect\Sdk\V1\Domain\CreatePaymentResult', $declinedPaymentException->getCreatePaymentResult());
        $paymentErrorResponse->paymentResult = new CreatePaymentResult();
        $this->assertInstanceOf('\Worldline\Connect\Sdk\V1\Domain\CreatePaymentResult', $declinedPaymentException->getCreatePaymentResult());
    }

    public function testDeclinedPayoutException()
    {
        $payoutErrorResponse = new PayoutErrorResponse();
        $declinedPayoutException = new DeclinedPayoutException(0, $payoutErrorResponse);
        $this->assertInstanceOf('\Worldline\Connect\Sdk\V1\Domain\PayoutResult', $declinedPayoutException->getPayoutResult());
        $payoutErrorResponse->payoutResult = new PayoutResult();
        $this->assertInstanceOf('\Worldline\Connect\Sdk\V1\Domain\PayoutResult', $declinedPayoutException->getPayoutResult());
    }

    public function testDeclinedRefundException()
    {
        $refundErrorResponse = new RefundErrorResponse();
        $declinedRefundException = new DeclinedRefundException(0, $refundErrorResponse);
        $this->assertInstanceOf('\Worldline\Connect\Sdk\V1\Domain\RefundResult', $declinedRefundException->getRefundResult());
        $refundErrorResponse->refundResult = new RefundResult();
        $this->assertInstanceOf('\Worldline\Connect\Sdk\V1\Domain\RefundResult', $declinedRefundException->getRefundResult());
    }
}
