<?php

declare(strict_types=1);

namespace GuzzleHttp\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;


    private $method;


    private $requestTarget;


    private $uri;

    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ) {
        $this->assertMethod($method);
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;

        if (!isset($this->headerNames['host'])) {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null) {
            $this->stream = Utils::streamFor($body);
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }
        if ($this->uri->getQuery() != '') {
            $target .= '?'.$this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget($requestTarget): RequestInterface
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): RequestInterface
    {
        $this->assertMethod($method);
        $new = clone $this;
        $new->method = strtoupper($method);

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !isset($this->headerNames['host'])) {
            $new->updateHostFromUri();
        }

        return $new;
    }

    private function updateHostFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host == '') {
            return;
        }

        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':'.$port;
        }

        if (isset($this->headerNames['host'])) {
            $header = $this->headerNames['host'];
        } else {
            $header = 'Host';
            $this->headerNames['host'] = 'Host';
        }
        $this->headers = [$header => [$host]] + $this->headers;
    }

    private function assertMethod($method): void
    {
        if (!is_string($method) || $method === '') {
            throw new InvalidArgumentException('Method must be a non-empty string.');
        }
    }
}
