<?php

namespace GuzzleHttp\Handler;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class MockHandler implements \Countable
{
    private $queue = [];

    private $lastRequest;

    private $lastOptions = [];

    private $onFulfilled;

    private $onRejected;

    public static function createWithMiddleware(?array $queue = null, ?callable $onFulfilled = null, ?callable $onRejected = null): HandlerStack
    {
        return HandlerStack::create(new self($queue, $onFulfilled, $onRejected));
    }

    public function __construct(?array $queue = null, ?callable $onFulfilled = null, ?callable $onRejected = null)
    {
        $this->onFulfilled = $onFulfilled;
        $this->onRejected = $onRejected;

        if ($queue) {
            $this->append(...array_values($queue));
        }
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        if (!$this->queue) {
            throw new \OutOfBoundsException('Mock queue is empty');
        }

        if (isset($options['delay']) && \is_numeric($options['delay'])) {
            \usleep((int) $options['delay'] * 1000);
        }

        $this->lastRequest = $request;
        $this->lastOptions = $options;
        $response = \array_shift($this->queue);

        if (isset($options['on_headers'])) {
            if (!\is_callable($options['on_headers'])) {
                throw new \InvalidArgumentException('on_headers must be callable');
            }
            try {
                $options['on_headers']($response);
            } catch (\Exception $e) {
                $msg = 'An error was encountered during the on_headers event';
                $response = new RequestException($msg, $request, $response, $e);
            }
        }

        if (\is_callable($response)) {
            $response = $response($request, $options);
        }

        $response = $response instanceof \Throwable
            ? P\Create::rejectionFor($response)
            : P\Create::promiseFor($response);

        return $response->then(
            function (?ResponseInterface $value) use ($request, $options) {
                $this->invokeStats($request, $options, $value);
                if ($this->onFulfilled) {
                    ($this->onFulfilled)($value);
                }

                if ($value !== null && isset($options['sink'])) {
                    $contents = (string) $value->getBody();
                    $sink = $options['sink'];

                    if (\is_resource($sink)) {
                        \fwrite($sink, $contents);
                    } elseif (\is_string($sink)) {
                        \file_put_contents($sink, $contents);
                    } elseif ($sink instanceof StreamInterface) {
                        $sink->write($contents);
                    }
                }

                return $value;
            },
            function ($reason) use ($request, $options) {
                $this->invokeStats($request, $options, null, $reason);
                if ($this->onRejected) {
                    ($this->onRejected)($reason);
                }

                return P\Create::rejectionFor($reason);
            }
        );
    }

    public function append(...$values): void
    {
        foreach ($values as $value) {
            if ($value instanceof ResponseInterface
                || $value instanceof \Throwable
                || $value instanceof PromiseInterface
                || \is_callable($value)
            ) {
                $this->queue[] = $value;
            } else {
                throw new \TypeError('Expected a Response, Promise, Throwable or callable. Found '.Utils::describeType($value));
            }
        }
    }

    public function getLastRequest(): ?RequestInterface
    {
        return $this->lastRequest;
    }

    public function getLastOptions(): array
    {
        return $this->lastOptions;
    }

    public function count(): int
    {
        return \count($this->queue);
    }

    public function reset(): void
    {
        $this->queue = [];
    }

    private function invokeStats(
        RequestInterface $request,
        array $options,
        ?ResponseInterface $response = null,
        $reason = null
    ): void {
        if (isset($options['on_stats'])) {
            $transferTime = $options['transfer_time'] ?? 0;
            $stats = new TransferStats($request, $response, $transferTime, $reason);
            ($options['on_stats'])($stats);
        }
    }
}
