<?php

namespace Psr\Http\Message;

interface MessageInterface
{
    public function getProtocolVersion(): string;

    public function withProtocolVersion(string $version): MessageInterface;

    public function getHeaders(): array;

    public function hasHeader(string $name): bool;

    public function getHeader(string $name): array;

    public function getHeaderLine(string $name): string;

    public function withHeader(string $name, $value): MessageInterface;

    public function withAddedHeader(string $name, $value): MessageInterface;

    public function withoutHeader(string $name): MessageInterface;

    public function getBody(): StreamInterface;

    public function withBody(StreamInterface $body): MessageInterface;
}
