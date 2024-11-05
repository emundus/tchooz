<?php

namespace GuzzleHttp\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

class ConnectException extends TransferException implements NetworkExceptionInterface
{
    private $request;

    private $handlerContext;

    public function __construct(
        string $message,
        RequestInterface $request,
        ?\Throwable $previous = null,
        array $handlerContext = []
    ) {
        parent::__construct($message, 0, $previous);
        $this->request = $request;
        $this->handlerContext = $handlerContext;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getHandlerContext(): array
    {
        return $this->handlerContext;
    }
}
