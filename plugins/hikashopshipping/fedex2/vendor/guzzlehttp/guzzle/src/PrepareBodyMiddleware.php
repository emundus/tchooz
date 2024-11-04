<?php

namespace GuzzleHttp;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

class PrepareBodyMiddleware
{
    private $nextHandler;

    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $fn = $this->nextHandler;

        if ($request->getBody()->getSize() === 0) {
            return $fn($request, $options);
        }

        $modify = [];

        if (!$request->hasHeader('Content-Type')) {
            if ($uri = $request->getBody()->getMetadata('uri')) {
                if (is_string($uri) && $type = Psr7\MimeType::fromFilename($uri)) {
                    $modify['set_headers']['Content-Type'] = $type;
                }
            }
        }

        if (!$request->hasHeader('Content-Length')
            && !$request->hasHeader('Transfer-Encoding')
        ) {
            $size = $request->getBody()->getSize();
            if ($size !== null) {
                $modify['set_headers']['Content-Length'] = $size;
            } else {
                $modify['set_headers']['Transfer-Encoding'] = 'chunked';
            }
        }

        $this->addExpectHeader($request, $options, $modify);

        return $fn(Psr7\Utils::modifyRequest($request, $modify), $options);
    }

    private function addExpectHeader(RequestInterface $request, array $options, array &$modify): void
    {
        if ($request->hasHeader('Expect')) {
            return;
        }

        $expect = $options['expect'] ?? null;

        if ($expect === false || $request->getProtocolVersion() === '1.0') {
            return;
        }

        if ($expect === true) {
            $modify['set_headers']['Expect'] = '100-Continue';

            return;
        }

        if ($expect === null) {
            $expect = 1048576;
        }

        $body = $request->getBody();
        $size = $body->getSize();

        if ($size === null || $size >= (int) $expect || !$body->isSeekable()) {
            $modify['set_headers']['Expect'] = '100-Continue';
        }
    }
}
