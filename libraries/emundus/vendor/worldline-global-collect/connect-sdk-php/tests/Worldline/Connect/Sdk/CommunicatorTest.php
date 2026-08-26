<?php
namespace Worldline\Connect\Sdk;

use Worldline\Connect\Sdk\Communication\ErrorResponseException;
use Worldline\Connect\Sdk\Communication\InvalidResponseException;
use Worldline\Connect\Sdk\Communication\ResponseClassMap;
use Worldline\Connect\Sdk\TestUtil\AppendingBodyHandler;
use Worldline\Connect\Sdk\V1\Merchant\Products\FindProductsParams;

/**
 * @group communicator
 *
 */
class CommunicatorTest extends TestCase
{

    /** @var Communicator|null */
    protected ?Communicator $defaultCommunicator = null;

    /** @var ResponseClassMap|null */
    protected ?ResponseClassMap $defaultResponseClassMap = null;

    public function setUp(): void
    {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $this->defaultCommunicator = new Communicator($communicatorConfiguration);
        $this->defaultResponseClassMap = new ResponseClassMap();
        $this->defaultResponseClassMap->defaultErrorResponseClassName = '\Worldline\Connect\Sdk\V1\Domain\ErrorResponse';
    }

    public function tearDown(): void
    {
    }

    public function testApiRequestGet()
    {
        // This method just tests that calling get doesn't fail
        $this->expectNotToPerformAssertions();

        $relativeUri = sprintf('/v1/%s/products', $this->getMerchantId());
        $findParams = new FindProductsParams();
        $findParams->countryCode = 'NL';
        $findParams->currencyCode = 'EUR';
        $clientMetaInfo = '';
        $this->defaultCommunicator->get($this->defaultResponseClassMap, $relativeUri, $clientMetaInfo, $findParams);
    }

    // skipped, as Connect now returns a JSON error response for invalid URLs, including non-numeric payment product ids
    public function skipExceptionInvalidUrl()
    {
        try {
            $relativeUri = sprintf('/v1/%s/foo', $this->getMerchantId());
            $this->defaultCommunicator->get($this->defaultResponseClassMap, $relativeUri);
        } catch (InvalidResponseException $e) {
            $this->assertEquals(404, $e->getResponse()->getHttpStatusCode());
            return;
        }
        $this->fail('an expected exception has not been raised');
    }

    public function testApiRequestPost()
    {
        $this->expectException(ErrorResponseException::class);

        $relativeUri = sprintf('/v1/%s/payments/1/tokenize', $this->getMerchantId());
        $this->defaultCommunicator->post($this->defaultResponseClassMap, $relativeUri);
    }

    public function testApiRequestPut()
    {
        $this->expectException(InvalidResponseException::class);

        $relativeUri = sprintf('/v1/%s/tokens/1', $this->getMerchantId());
        $this->defaultCommunicator->put($this->defaultResponseClassMap, $relativeUri);
    }

    public function testApiRequestDelete()
    {
        $this->expectException(ErrorResponseException::class);

        $relativeUri = sprintf('/v1/%s/tokens/1', $this->getMerchantId());
        $this->defaultCommunicator->delete($this->defaultResponseClassMap, $relativeUri);
    }

    public function testApiRequestGetWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        $relativeUri = sprintf('/v1/%s/products', $this->getMerchantId());
        $findParams = new FindProductsParams();
        $findParams->countryCode = 'NL';
        $findParams->currencyCode = 'EUR';
        $clientMetaInfo = '';
        $this->defaultCommunicator->getWithBinaryResponse(
            array($bodyHandler, 'handleBodyPart'),
            $this->defaultResponseClassMap,
            $relativeUri,
            $clientMetaInfo,
            $findParams
        );
        $this->assertNotEquals('', $bodyHandler->getBody());
        $this->assertStringStartsWith('{', $bodyHandler->getBody());
        $this->assertStringEndsWith('}', $bodyHandler->getBody());
    }

    public function testApiRequestPostWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        try {
            $relativeUri = sprintf('/v1/%s/payments/1/tokenize', $this->getMerchantId());
            $this->defaultCommunicator->postWithBinaryResponse(array($bodyHandler, 'handleBodyPart'), $this->defaultResponseClassMap, $relativeUri);
        } catch (ErrorResponseException $e) {
            // the body handler is not called
            $this->assertEquals('', $bodyHandler->getBody());
            return;
        }
        $this->fail();
    }

    public function testApiRequestPutWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        try {
            $relativeUri = sprintf('/v1/%s/tokens/1', $this->getMerchantId());
            $this->defaultCommunicator->putWithBinaryResponse(array($bodyHandler, 'handleBodyPart'), $this->defaultResponseClassMap, $relativeUri);
        } catch (InvalidResponseException $e) {
            // the body handler is not called
            $this->assertEquals('', $bodyHandler->getBody());
            return;
        }
        $this->fail();
    }

    public function testApiRequestDeleteWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        try {
            $relativeUri = sprintf('/v1/%s/tokens/1', $this->getMerchantId());
            $this->defaultCommunicator->deleteWithBinaryResponse(array($bodyHandler, 'handleBodyPart'), $this->defaultResponseClassMap, $relativeUri);
        } catch (ErrorResponseException $e) {
            // the body handler is not called
            $this->assertEquals('', $bodyHandler->getBody());
            return;
        }
        $this->fail();
    }
}
