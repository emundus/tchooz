<?php

namespace GuzzleHttp;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

trait ClientTrait
{
    abstract public function request(string $method, $uri, array $options = []): ResponseInterface;

    public function get($uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    public function head($uri, array $options = []): ResponseInterface
    {
        return $this->request('HEAD', $uri, $options);
    }

    public function put($uri, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }

    public function post($uri, array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    public function patch($uri, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }

    public function delete($uri, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }

    abstract public function requestAsync(string $method, $uri, array $options = []): PromiseInterface;

    public function getAsync($uri, array $options = []): PromiseInterface
    {
        return $this->requestAsync('GET', $uri, $options);
    }

    public function headAsync($uri, array $options = []): PromiseInterface
    {
        return $this->requestAsync('HEAD', $uri, $options);
    }

    public function putAsync($uri, array $options = []): PromiseInterface
    {
        return $this->requestAsync('PUT', $uri, $options);
    }

    public function postAsync($uri, array $options = []): PromiseInterface
    {
        return $this->requestAsync('POST', $uri, $options);
    }

    public function patchAsync($uri, array $options = []): PromiseInterface
    {
        return $this->requestAsync('PATCH', $uri, $options);
    }

    public function deleteAsync($uri, array $options = []): PromiseInterface
    {
        return $this->requestAsync('DELETE', $uri, $options);
    }
}
