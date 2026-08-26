<?php
namespace Worldline\Connect\Sdk;

use ReflectionMethod;
use Worldline\Connect\Sdk\Communication\HttpHeaderHelper;
use Worldline\Connect\Sdk\Communication\MetadataProvider;
use Worldline\Connect\Sdk\Domain\ShoppingCartExtension;

/**
 * @group request_header
 *
 */
class CommunicatorRequestHeaderTest extends TestCase
{
    public function testV1HMAC()
    {
        foreach (array('GET','POST','PUT','DELETE') as $httpMethod) {
            $requestHeaders = $this->getRequestHeaders(
                $this->getCommunicatorConfiguration(),
                $httpMethod,
                '/v1/consumer/ANDR%C3%89E/?q=na%20me'
            );
            $curlHeaders = HttpHeaderHelper::generateRawHeaders($requestHeaders);

            $this->assertCount(3, $curlHeaders);

            $result = false;
            foreach ($curlHeaders as $curlHeader) {
                $result |= preg_match(
                    '/^Authorization: GCS v1HMAC:' . $this->getApiKey() . ':[a-zA-Z0-9+\/]+={0,2}/',
                    $curlHeader
                );
            }
            $this->assertEquals(1, $result);
        }
    }

    public function testV1HMACWithContentType()
    {
        foreach (array('POST','PUT') as $httpMethod) {
            $requestHeaders = $this->getRequestHeaders(
                $this->getCommunicatorConfiguration(),
                $httpMethod,
                '/v1/consumer/ANDR%C3%89E/?q=na%20me',
                'application/json'
            );
            $curlHeaders = HttpHeaderHelper::generateRawHeaders($requestHeaders);

            $this->assertCount(4, $curlHeaders);

            $result = false;
            foreach ($curlHeaders as $curlHeader) {
                $result |= preg_match(
                    '/^Authorization: GCS v1HMAC:' . $this->getApiKey() . ':[a-zA-Z0-9+\/]+={0,2}/',
                    $curlHeader
                );
            }
            $this->assertEquals(1, $result);
        }
    }

    public function testAddHeaderV1HMACAddHeaders()
    {
        foreach (array('GET','POST','PUT','DELETE') as $httpMethod) {
            $clientMetaInfo = base64_encode('{ "test": "test" }');
            $requestHeaders = $this->getRequestHeaders(
                $this->getCommunicatorConfiguration(),
                $httpMethod,
                '/v1/consumer/ANDR%C3%89E/?q=na%20me',
                '',
                $clientMetaInfo
            );

            $this->assertCount(4, $requestHeaders);
            $result = false;
            foreach ($requestHeaders as $curlHeader) {
                $splitHeader = explode(':', $curlHeader);
                $result |= base64_encode(base64_decode(end($splitHeader))) == end($splitHeader);
            }
            if (!$result) {
                print_r($requestHeaders);
            }
            $this->assertEquals(1, $result);

            $xGcsHeaders = array_slice($requestHeaders, 1, 3, true);
            $xGcsHeadersSorted = $xGcsHeaders;
            ksort($xGcsHeadersSorted);
            $this->assertEquals($xGcsHeadersSorted, $xGcsHeaders);
        }
    }

    public function testAddHeaderV1HMACAddHeadersWithContentType()
    {
        foreach (array('POST','PUT') as $httpMethod) {
            $clientMetaInfo = base64_encode('{ "test": "test" }');
            $requestHeaders = $this->getRequestHeaders(
                $this->getCommunicatorConfiguration(),
                $httpMethod,
                '/v1/consumer/ANDR%C3%89E/?q=na%20me',
                'application/json',
                $clientMetaInfo
            );

            $this->assertCount(5, $requestHeaders);
            $result = false;
            foreach ($requestHeaders as $curlHeader) {
                $splitHeader = explode(':', $curlHeader);
                $result |= base64_encode(base64_decode(end($splitHeader))) == end($splitHeader);
            }
            if (!$result) {
                print_r($requestHeaders);
            }
            $this->assertEquals(1, $result);

            $xGcsHeaders = array_slice($requestHeaders, 1, 3, true);
            $xGcsHeadersSorted = $xGcsHeaders;
            ksort($xGcsHeadersSorted);
            $this->assertEquals($xGcsHeadersSorted, $xGcsHeaders);
        }
    }

    public function testMultiLineHeader()
    {
        $gcsHeaderValue = " some value  \r\n \n with  some \r\n \t spaces ";
        $gcsEncodedHeaderValue = trim(preg_replace('/\r?\n[\h]*/', ' ', $gcsHeaderValue));
        $this->assertEquals('some value    with  some  spaces', $gcsEncodedHeaderValue);
    }

    public function testServerMetaInfoHeaderSimple()
    {
        $requestHeaders = $this->getRequestHeaders(
            $this->getCommunicatorConfiguration(),
            'GET',
            '/v1/consumer/ANDR%C3%89E/?q=na%20me'
        );
        $serverMetaInfoJson = $requestHeaders['X-GCS-ServerMetaInfo'];
        $serverMetaInfo = json_decode(base64_decode($serverMetaInfoJson));
        $this->assertInstanceOf('\stdClass', $serverMetaInfo);

        $this->assertObjectHasProperty('platformIdentifier', $serverMetaInfo);
        $this->assertStringContainsString(php_uname(), $serverMetaInfo->platformIdentifier);
        $this->assertStringContainsString(phpversion(), $serverMetaInfo->platformIdentifier);

        $this->assertObjectHasProperty('sdkIdentifier', $serverMetaInfo);
        $this->assertEquals('PHPServerSDK/v' . MetadataProvider::SDK_VERSION, $serverMetaInfo->sdkIdentifier);

        $this->assertObjectHasProperty('sdkCreator', $serverMetaInfo);
        $this->assertEquals('Worldline', $serverMetaInfo->sdkCreator);

        $this->assertObjectHasProperty('integrator', $serverMetaInfo);
        $this->assertEquals('Worldline', $serverMetaInfo->integrator);

        $this->assertObjectNotHasProperty('shoppingCartExtension', $serverMetaInfo);
    }

    public function testServerMetaInfoHeaderFull()
    {
        // create a new CommunicatorConfiguration to not modify the field
        $communicatorConfiguration = new CommunicatorConfiguration(
            $this->getApiKey(),
            $this->getApiSecret(),
            $this->getApiEndpoint(),
            'Worldline.Integrator'
        );
        $communicatorConfiguration->setShoppingCartExtension(new ShoppingCartExtension('Worldline.Creator', 'Extension', '1.0', 'ExtensionId'));

        $requestHeaders = $this->getRequestHeaders(
            $communicatorConfiguration,
            'GET',
            '/v1/consumer/ANDR%C3%89E/?q=na%20me'
        );
        $serverMetaInfoJson = $requestHeaders['X-GCS-ServerMetaInfo'];
        $serverMetaInfo = json_decode(base64_decode($serverMetaInfoJson));
        $this->assertInstanceOf('\stdClass', $serverMetaInfo);

        $this->assertObjectHasProperty('platformIdentifier', $serverMetaInfo);
        $this->assertStringContainsString(php_uname(), $serverMetaInfo->platformIdentifier);
        $this->assertStringContainsString(phpversion(), $serverMetaInfo->platformIdentifier);

        $this->assertObjectHasProperty('sdkIdentifier', $serverMetaInfo);
        $this->assertEquals('PHPServerSDK/v' . MetadataProvider::SDK_VERSION, $serverMetaInfo->sdkIdentifier);

        $this->assertObjectHasProperty('sdkCreator', $serverMetaInfo);
        $this->assertEquals('Worldline', $serverMetaInfo->sdkCreator);

        $this->assertObjectHasProperty('integrator', $serverMetaInfo);
        $this->assertEquals('Worldline.Integrator', $serverMetaInfo->integrator);

        $this->assertObjectHasProperty('shoppingCartExtension', $serverMetaInfo);
        $this->assertInstanceOf('\stdClass', $serverMetaInfo->shoppingCartExtension);

        $this->assertObjectHasProperty('extensionId', $serverMetaInfo->shoppingCartExtension);
        $this->assertEquals('ExtensionId', $serverMetaInfo->shoppingCartExtension->extensionId);
        $this->assertObjectHasProperty('creator', $serverMetaInfo->shoppingCartExtension);
        $this->assertEquals('Worldline.Creator', $serverMetaInfo->shoppingCartExtension->creator);
        $this->assertObjectHasProperty('name', $serverMetaInfo->shoppingCartExtension);
        $this->assertEquals('Extension', $serverMetaInfo->shoppingCartExtension->name);
        $this->assertObjectHasProperty('version', $serverMetaInfo->shoppingCartExtension);
        $this->assertEquals('1.0', $serverMetaInfo->shoppingCartExtension->version);
    }

    public function testServerMetaInfoHeaderFullNoShoppingCartExtensionId()
    {
        // create a new CommunicatorConfiguration to not modify the field
        $communicatorConfiguration = new CommunicatorConfiguration(
            $this->getApiKey(),
            $this->getApiSecret(),
            $this->getApiEndpoint(),
            'Worldline.Integrator'
        );
        $communicatorConfiguration->setShoppingCartExtension(new ShoppingCartExtension('Worldline.Creator', 'Extension', '1.0'));

        $requestHeaders = $this->getRequestHeaders(
            $communicatorConfiguration,
            'GET',
            '/v1/consumer/ANDR%C3%89E/?q=na%20me'
        );
        $serverMetaInfoJson = $requestHeaders['X-GCS-ServerMetaInfo'];
        $serverMetaInfo = json_decode(base64_decode($serverMetaInfoJson));
        $this->assertInstanceOf('\stdClass', $serverMetaInfo);

        $this->assertObjectHasProperty('platformIdentifier', $serverMetaInfo);
        $this->assertStringContainsString(php_uname(), $serverMetaInfo->platformIdentifier);
        $this->assertStringContainsString(phpversion(), $serverMetaInfo->platformIdentifier);

        $this->assertObjectHasProperty('sdkIdentifier', $serverMetaInfo);
        $this->assertEquals('PHPServerSDK/v' . MetadataProvider::SDK_VERSION, $serverMetaInfo->sdkIdentifier);

        $this->assertObjectHasProperty('sdkCreator', $serverMetaInfo);
        $this->assertEquals('Worldline', $serverMetaInfo->sdkCreator);

        $this->assertObjectHasProperty('integrator', $serverMetaInfo);
        $this->assertEquals('Worldline.Integrator', $serverMetaInfo->integrator);

        $this->assertObjectHasProperty('shoppingCartExtension', $serverMetaInfo);
        $this->assertInstanceOf('\stdClass', $serverMetaInfo->shoppingCartExtension);

        $this->assertObjectNotHasProperty('extensionId', $serverMetaInfo->shoppingCartExtension);
        $this->assertObjectHasProperty('creator', $serverMetaInfo->shoppingCartExtension);
        $this->assertEquals('Worldline.Creator', $serverMetaInfo->shoppingCartExtension->creator);
        $this->assertObjectHasProperty('name', $serverMetaInfo->shoppingCartExtension);
        $this->assertEquals('Extension', $serverMetaInfo->shoppingCartExtension->name);
        $this->assertObjectHasProperty('version', $serverMetaInfo->shoppingCartExtension);
        $this->assertEquals('1.0', $serverMetaInfo->shoppingCartExtension->version);
    }

    private function getRequestHeaders(
        CommunicatorConfiguration $communicatorConfiguration,
        string                    $httpMethod,
        string                    $relativeUri,
        string                    $contentType = '',
        string                    $clientMetaInfo = ''
    ): array {
        $communicator = new Communicator($communicatorConfiguration);
        $method = new ReflectionMethod($communicator, 'getRequestHeaders');
        $method->setAccessible(true);
        return $method->invoke($communicator, $httpMethod, $relativeUri, $contentType, $clientMetaInfo);
    }
}
