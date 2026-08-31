<?php
namespace Worldline\Connect\Sdk\Communication;

use PHPUnit\Framework\TestCase;

/**
 * @group obfuscation
 */
class HttpObfuscatorTest extends TestCase
{
    /**
     * @dataProvider rawObfuscatedRequestProvider
     * @param string $requestMethod
     * @param string $relativeRequestUri
     * @param array $requestHeaders
     * @param string $requestBody
     * @param string $expectedRawObfuscatedRequest
     */
    public function testRawObfuscatedRequest(
        string $requestMethod,
        string $relativeRequestUri,
        array  $requestHeaders,
        string $requestBody,
        string $expectedRawObfuscatedRequest
    ) {
        $httpObfuscator = new HttpObfuscator();
        $this->assertEquals(
            $expectedRawObfuscatedRequest,
            $httpObfuscator->getRawObfuscatedRequest($requestMethod, $relativeRequestUri, $requestHeaders, $requestBody)
        );
    }

    /**
     * @return array
     */
    public function rawObfuscatedRequestProvider(): array
    {
        return array(
            array(
                'GET',
                '/foo',
                array(),
                '',
                "GET /foo HTTP/1.1"
            ),
            array(
                'DELETE',
                '/foo',
                array('Content-Type' => 'application/json', 'Authorization' => '123'),
                '',
                "DELETE /foo HTTP/1.1" . PHP_EOL . "Content-Type: application/json" . PHP_EOL . "Authorization: ********"
            ),
            array(
                'POST',
                '/foo',
                array('Content-Type' => 'application/json'),
                json_encode(array('name' => 'bar', 'value' => 'bar'), JSON_PRETTY_PRINT),
                "POST /foo HTTP/1.1" . PHP_EOL . "Content-Type: application/json" . PHP_EOL . PHP_EOL .
                json_encode(array('name' => 'bar', 'value' => '***'), JSON_PRETTY_PRINT)
            ),
            array(
                'PUT',
                '/foo',
                array('Content-Type' => 'application/json', 'Authorization' => '123'),
                json_encode(array('value' => 'baz'), JSON_PRETTY_PRINT),
                "PUT /foo HTTP/1.1" . PHP_EOL . "Content-Type: application/json" . PHP_EOL . "Authorization: ********" . PHP_EOL . PHP_EOL .
                json_encode(array('value' => '***'), JSON_PRETTY_PRINT)
            ),
            array(
                'PUT',
                '/foo',
                array('Content-Type' => 'text/html', 'Authorization' => '123'),
                json_encode(array('value' => 'baz'), JSON_PRETTY_PRINT),
                "PUT /foo HTTP/1.1" . PHP_EOL . "Content-Type: text/html" . PHP_EOL . "Authorization: ********" . PHP_EOL . PHP_EOL .
                json_encode(array('value' => 'baz'), JSON_PRETTY_PRINT)
            ),
        );
    }

    /**
     * @dataProvider rawObfuscatedResponseProvider
     * @param ConnectionResponse $response
     * @param string $expectedRawObfuscatedResponse
     */
    public function testRawObfuscatedResponse(ConnectionResponse $response, string $expectedRawObfuscatedResponse)
    {
        $httpObfuscator = new HttpObfuscator();
        $this->assertEquals(
            $expectedRawObfuscatedResponse,
            $httpObfuscator->getRawObfuscatedResponse($response)
        );
    }

    /**
     * @return array
     */
    public function rawObfuscatedResponseProvider(): array
    {
        return array(
            array(
                new ConnectionResponse(0, array(), ''),
                ""
            ),
            array(
                new ConnectionResponse(
                    200,
                    array(0 => 'HTTP/1.1 200 OK', 'Content-Type' => 'application/json', 'Authorization' => '123'),
                    ''
                ),
                "HTTP/1.1 200 OK" . PHP_EOL . "Content-Type: application/json" . PHP_EOL . "Authorization: ********"
            ),
            array(
                new ConnectionResponse(
                    404,
                    array(0 => 'HTTP/1.1 404 Not Found', 'Content-Type' => 'text/html'),
                    json_encode(array('value' => 'foo'), JSON_PRETTY_PRINT)
                ),
                "HTTP/1.1 404 Not Found" . PHP_EOL . "Content-Type: text/html" . PHP_EOL . PHP_EOL .
                json_encode(array('value' => 'foo'), JSON_PRETTY_PRINT)
            ),
            array(
                new ConnectionResponse(
                    201,
                    array(0 => 'HTTP/1.1 201 Created', 'Content-Type' => 'application/json'),
                    json_encode(array('name' => 'foo', 'value' => 'baz'), JSON_PRETTY_PRINT)
                ),
                "HTTP/1.1 201 Created" . PHP_EOL . "Content-Type: application/json" . PHP_EOL . PHP_EOL .
                json_encode(array('name' => 'foo', 'value' => '***'), JSON_PRETTY_PRINT)
            ),
        );
    }
}
