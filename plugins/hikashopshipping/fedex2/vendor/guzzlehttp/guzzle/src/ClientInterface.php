<?php

namespace GuzzleHttp;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

interface ClientInterface
{
    public const MAJOR_VERSION = 7;

    public function send(RequestInterface $request, array $options = []): ResponseInterface;

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface;

    public function request(string $method, $uri, array $options = []): ResponseInterface;

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface;

    public function getConfig(?string $option = null);
}
