<?php

namespace GuzzleHttp\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BadResponseException extends RequestException
{
    public function __construct(
        string $message,
        RequestInterface $request,
        ResponseInterface $response,
        ?\Throwable $previous = null,
        array $handlerContext = []
    ) {
        parent::__construct($message, $request, $response, $previous, $handlerContext);
    }

    public function hasResponse(): bool
    {
        return true;
    }

    public function getResponse(): ResponseInterface
    {

        return parent::getResponse();
    }
}
