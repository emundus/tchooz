<?php

namespace GuzzleHttp;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

final class TransferStats
{
    private $request;

    private $response;

    private $transferTime;

    private $handlerStats;

    private $handlerErrorData;

    public function __construct(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?float $transferTime = null,
        $handlerErrorData = null,
        array $handlerStats = []
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->transferTime = $transferTime;
        $this->handlerErrorData = $handlerErrorData;
        $this->handlerStats = $handlerStats;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    public function getHandlerErrorData()
    {
        return $this->handlerErrorData;
    }

    public function getEffectiveUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function getTransferTime(): ?float
    {
        return $this->transferTime;
    }

    public function getHandlerStats(): array
    {
        return $this->handlerStats;
    }

    public function getHandlerStat(string $stat)
    {
        return $this->handlerStats[$stat] ?? null;
    }
}
