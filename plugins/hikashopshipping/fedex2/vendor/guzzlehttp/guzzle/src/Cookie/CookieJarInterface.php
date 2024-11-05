<?php

namespace GuzzleHttp\Cookie;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface CookieJarInterface extends \Countable, \IteratorAggregate
{
    public function withCookieHeader(RequestInterface $request): RequestInterface;

    public function extractCookies(RequestInterface $request, ResponseInterface $response): void;

    public function setCookie(SetCookie $cookie): bool;

    public function clear(?string $domain = null, ?string $path = null, ?string $name = null): void;

    public function clearSessionCookies(): void;

    public function toArray(): array;
}
