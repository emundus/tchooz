<?php
namespace Worldline\Connect\Sdk\Communication;

use PHPUnit\Framework\TestCase;

/**
 * @group obfuscation
 */
class CommunicatorLoggerHelperTest extends TestCase
{
    /**
     * @dataProvider uriProvider
     * @param string $requestUri
     * @param string $endpoint
     * @param string $relativeUriPathWithRequestParameters
     */
    public function testUriSplitting(string $requestUri, string $endpoint, string $relativeUriPathWithRequestParameters)
    {
        $communicatorLoggingHelper = new CommunicatorLoggerHelper();
        $this->assertEquals($endpoint, $communicatorLoggingHelper->getEndpoint($requestUri));
        $this->assertEquals(
            $relativeUriPathWithRequestParameters,
            $communicatorLoggingHelper->getRelativeUriPathWithRequestParameters($requestUri)
        );
        $this->assertEquals($requestUri, $endpoint . $relativeUriPathWithRequestParameters);
    }

    /**
     * @return array
     */
    public function uriProvider(): array
    {
        return array(
            array(
                'https://api.preprod.connect.worldline-solutions.com/v1/20000/services/testconnection',
                'https://api.preprod.connect.worldline-solutions.com',
                '/v1/20000/services/testconnection',
            ),
            array(
                'https://api.preprod.connect.worldline-solutions.com/v1/20000/services/convert/amount?source=EUR&target=USD&amount=1000',
                'https://api.preprod.connect.worldline-solutions.com',
                '/v1/20000/services/convert/amount?source=EUR&target=USD&amount=1000',
            ),
            array(
                'https://api.preprod.connect.worldline-solutions.com',
                'https://api.preprod.connect.worldline-solutions.com',
                ''
            ),
            array(
                '/v1/20000/services/testconnection',
                '',
                '/v1/20000/services/testconnection'
            ),
        );
    }
}
