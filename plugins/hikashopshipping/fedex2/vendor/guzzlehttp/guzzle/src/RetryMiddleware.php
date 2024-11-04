<?php

namespace GuzzleHttp;

use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryMiddleware
{
    private $nextHandler;

    private $decider;

    private $delay;

    public function __construct(callable $decider, callable $nextHandler, ?callable $delay = null)
    {
        $this->decider = $decider;
        $this->nextHandler = $nextHandler;
        $this->delay = $delay ?: __CLASS__.'::exponentialDelay';
    }

    public static function exponentialDelay(int $retries): int
    {
        return (int) 2 ** ($retries - 1) * 1000;
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        if (!isset($options['retries'])) {
            $options['retries'] = 0;
        }

        $fn = $this->nextHandler;

        return $fn($request, $options)
            ->then(
                $this->onFulfilled($request, $options),
                $this->onRejected($request, $options)
            );
    }

    private function onFulfilled(RequestInterface $request, array $options): callable
    {
        return function ($value) use ($request, $options) {
            if (!($this->decider)(
                $options['retries'],
                $request,
                $value,
                null
            )) {
                return $value;
            }

            return $this->doRetry($request, $options, $value);
        };
    }

    private function onRejected(RequestInterface $req, array $options): callable
    {
        return function ($reason) use ($req, $options) {
            if (!($this->decider)(
                $options['retries'],
                $req,
                null,
                $reason
            )) {
                return P\Create::rejectionFor($reason);
            }

            return $this->doRetry($req, $options);
        };
    }

    private function doRetry(RequestInterface $request, array $options, ?ResponseInterface $response = null): PromiseInterface
    {
        $options['delay'] = ($this->delay)(++$options['retries'], $response, $request);

        return $this($request, $options);
    }
}
