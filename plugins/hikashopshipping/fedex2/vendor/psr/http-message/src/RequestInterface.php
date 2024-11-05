<?php

namespace Psr\Http\Message;

interface RequestInterface extends MessageInterface
{
    public function getRequestTarget(): string;

    public function withRequestTarget(string $requestTarget): RequestInterface;


    public function getMethod(): string;

    public function withMethod(string $method): RequestInterface;

    public function getUri(): UriInterface;

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface;
}
