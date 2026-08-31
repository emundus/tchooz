<?php
namespace Worldline\Connect\Sdk\V1;

use ReflectionMethod;
use Worldline\Connect\Sdk\CallContext;
use Worldline\Connect\Sdk\Client;
use Worldline\Connect\Sdk\Communication\ConnectionResponse;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\Communication\ResponseFactory;
use Worldline\Connect\Sdk\Communicator;
use Worldline\Connect\Sdk\TestCase;
use Worldline\Connect\Sdk\TestUtil\TestingConnection;
use Worldline\Connect\Sdk\V1\Domain\CreatePaymentRequest;

class IdempotenceTest extends TestCase
{
    public function testRequestHeadersWithIdempotenceKey()
    {
        $callContext = new CallContext();
        $idempotenceKey = 'test';
        $idempotenceKeyHeaderName = 'X-GCS-Idempotence-Key';
        $authorizationHeaderName = 'Authorization';
        $requestHeaders = $this->getRequestHeaders($callContext);
        $this->assertArrayNotHasKey($idempotenceKeyHeaderName, $requestHeaders);
        $this->assertArrayHasKey($authorizationHeaderName, $requestHeaders);
        $authorizationValueWithoutIdempotence = $requestHeaders[$authorizationHeaderName];
        $callContext->setIdempotenceKey('test');
        $requestHeaders = $this->getRequestHeaders($callContext);
        $this->assertArrayHasKey($idempotenceKeyHeaderName, $requestHeaders);
        $this->assertEquals($idempotenceKey, $requestHeaders[$idempotenceKeyHeaderName]);
        $this->assertArrayHasKey($authorizationHeaderName, $requestHeaders);
        $authorizationValueWithIdempotence = $requestHeaders[$authorizationHeaderName];
        $this->assertNotEquals($authorizationValueWithoutIdempotence, $authorizationValueWithIdempotence);
    }

    public function testGetWithCallContext()
    {
        $callContext = new CallContext();
        $idempotenceKey = 'test';
        $idempotenceRequestTimestamp = '12345';
        $callContext->setIdempotenceKey($idempotenceKey);
        $responseHeaders = array(
            'Content-Type' => 'application/json',
            'X-GCS-Idempotence-Request-Timestamp' => $idempotenceRequestTimestamp
        );
        $responseBody = <<<EOD
{
    "creationOutput": {
        "additionalReference": "00000200002014254946",
        "externalReference": "000002000020142549460000100001"
    },
    "payment": {
        "id": "000002000020142549460000100001",
        "paymentOutput": {
            "amountOfMoney": {
                "amount": 2345,
                "currencyCode": "CAD"
            },
            "references": {
                "paymentReference": "0"
            },
            "paymentMethod": "card",
            "cardPaymentMethodSpecificOutput": {
                "paymentProductId": 1,
                "authorisationCode": "OK1131",
                "card": {
                    "cardNumber": "************9176",
                    "expiryDate": "1220"
                },
                "fraudResults": {
                    "fraudServiceResult": "error",
                    "avsResult": "X",
                    "cvvResult": "M"
                }
            }
        },
        "status": "PENDING_APPROVAL",
        "statusOutput": {
            "isCancellable": true,
            "statusCode": 600,
            "statusCodeChangeDateTime": "20150331120036",
            "isAuthorized": true
        }
    }
}
EOD;
        $response = new ConnectionResponse(201, $responseHeaders, $responseBody);
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicator = new Communicator($communicatorConfiguration, null, new TestingConnection($response));
        $communicator->get(new ResponseClassMap(), '/', '', null, $callContext);
        $this->assertEquals($idempotenceKey, $callContext->getIdempotenceKey());
        $this->assertEquals($idempotenceRequestTimestamp, $callContext->getIdempotenceRequestTimestamp());
    }


    public function testIdempotenceException()
    {
        $callContext = new CallContext();
        $callContext->setIdempotenceKey('test');
        $responseHeaders = array(
            'Content-Type' => 'application/json',
            'X-GCS-Idempotence-Request-Timestamp' => '12345'
        );
        $responseBody = <<<EOD
{
   "errorId" : "75b0f13a-04a5-41b3-83b8-b295ddb23439-000013c6",
   "errors" : [ {
      "code" : "1409",
      "message" : "DUPLICATE REQUEST IN PROGRESS",
      "httpStatusCode" : 409
   } ]
}
EOD;
        $connectionResponse = new ConnectionResponse(409, $responseHeaders, $responseBody);
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicator = new Communicator($communicatorConfiguration, null, new TestingConnection($connectionResponse));
        $client = new Client($communicator);
        try {
            $client->v1()->merchant($this->getMerchantId())->payments()->create(new CreatePaymentRequest(), $callContext);
            $this->fail('expected IdempotenceException');
        } catch (IdempotenceException $exception) {
            $this->assertEquals($callContext->getIdempotenceKey(), $exception->getIdempotenceKey());
            $this->assertNotEmpty($exception->getIdempotenceRequestTimestamp());
            $this->assertEquals($callContext->getIdempotenceRequestTimestamp(), $exception->getIdempotenceRequestTimestamp());
        }
    }

    public function testReferenceException()
    {
        $callContext = new CallContext();
        $callContext->setIdempotenceKey('test');
        $responseHeaders = array(
            'Content-Type' => 'application/json',
            'X-GCS-Idempotence-Request-Timestamp' => '12345'
        );
        $responseBody = <<<EOD
{
   "errorId" : "75b0f13a-04a5-41b3-83b8-b295ddb23439-000013c6",
   "errors" : [ {
      "code" : "1400",
      "message" : "DUPLICATE REQUEST IN PROGRESS",
      "httpStatusCode" : 409
   } ]
}
EOD;
        $connectionResponse = new ConnectionResponse(409, $responseHeaders, $responseBody);
        $responseFactory = new ResponseFactory();
        $responseExceptionFactory = new ExceptionFactory();
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
        $exception = $responseExceptionFactory->createException(
            $connectionResponse->getHttpStatusCode(),
            $responseFactory->createResponse($connectionResponse, $responseClassMap),
            $callContext
        );
        $this->assertInstanceOf('\Worldline\Connect\Sdk\V1\ReferenceException', $exception);
    }

    private function getRequestHeaders(CallContext $context)
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicator = new Communicator($communicatorConfiguration);
        $method = new ReflectionMethod($communicator, 'getRequestHeaders');
        $method->setAccessible(true);
        return $method->invoke($communicator, 'GET', 'https://example.com', '', '', $context);
    }
}
