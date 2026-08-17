<?php
namespace Worldline\Connect\Sdk\Authentication;

use Worldline\Connect\Sdk\TestCase;

/**
 * @group authentication
 *
 */
class V1HMACAuthenticatorTest extends TestCase
{
    public function testGetAuthorization()
    {
        $configuration = $this->getCommunicatorConfiguration();
        $configuration->setApiKeyId('apiKeyId');
        $configuration->setApiSecret('secretApiKey');

        $authenticator = new V1HMACAuthenticator($configuration);

        $httpMethod = 'DELETE';
        $uriPath = '/v1/9991/tokens/123456789';
        $requestHeaders = array(
            'Content-Type' => 'application/json',
            'Date' => 'Fri, 06 Jun 2014 13:39:43 GMT',
            'X-GCS-ServerMetainfo' => 'processed header value',
            'X-GCS-ClientMetainfo' => 'processed header value',
            'X-GCS-CustomerHeader' => 'processed header value'
        );

        $authorization = $authenticator->getAuthorization($httpMethod, $uriPath, $requestHeaders);

        $this->assertEquals('GCS v1HMAC:apiKeyId:VfnXpPBQQoHZivTcAg0JvOWkhnzlPnaCPKpTQn/uMJM=', $authorization);
    }

    public function testGetAuthorizationMinimal()
    {
        $configuration = $this->getCommunicatorConfiguration();
        $configuration->setApiKeyId('EC36A74A98D21');
        $configuration->setApiSecret('6Kj5HT0MQKC6D8eb7W3lTg71kVKVDSt1');

        $authenticator = new V1HMACAuthenticator($configuration);

        $httpMethod = 'GET';
        $uriPath = '/v1/9991/tokens/123456789';
        $requestHeaders = array('Date' => 'Fri, 06 Jun 2014 13:39:43 GMT');

        $authorization = $authenticator->getAuthorization($httpMethod, $uriPath, $requestHeaders);

        $this->assertEquals('GCS v1HMAC:EC36A74A98D21:9ond5EIN05dBXJGCLRK5om9pxHsyrh/12pZJ7bvmwNM=', $authorization);
    }
}
