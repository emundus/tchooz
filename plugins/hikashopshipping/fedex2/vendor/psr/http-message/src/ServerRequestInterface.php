<?php

namespace Psr\Http\Message;

interface ServerRequestInterface extends RequestInterface
{
    public function getServerParams(): array;

    public function getCookieParams(): array;

    public function withCookieParams(array $cookies): ServerRequestInterface;

    public function getQueryParams(): array;

    public function withQueryParams(array $query): ServerRequestInterface;

    public function getUploadedFiles(): array;

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface;

    public function getParsedBody();

    public function withParsedBody($data): ServerRequestInterface;

    public function getAttributes(): array;

    public function getAttribute(string $name, $default = null);

    public function withAttribute(string $name, $value): ServerRequestInterface;

    public function withoutAttribute(string $name): ServerRequestInterface;
}
