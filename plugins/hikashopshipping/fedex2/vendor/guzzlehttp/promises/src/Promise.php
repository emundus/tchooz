<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

class Promise implements PromiseInterface
{
    private $state = self::PENDING;
    private $result;
    private $cancelFn;
    private $waitFn;
    private $waitList;
    private $handlers = [];

    public function __construct(
        ?callable $waitFn = null,
        ?callable $cancelFn = null
    ) {
        $this->waitFn = $waitFn;
        $this->cancelFn = $cancelFn;
    }

    public function then(
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ): PromiseInterface {
        if ($this->state === self::PENDING) {
            $p = new Promise(null, [$this, 'cancel']);
            $this->handlers[] = [$p, $onFulfilled, $onRejected];
            $p->waitList = $this->waitList;
            $p->waitList[] = $this;

            return $p;
        }

        if ($this->state === self::FULFILLED) {
            $promise = Create::promiseFor($this->result);

            return $onFulfilled ? $promise->then($onFulfilled) : $promise;
        }

        $rejection = Create::rejectionFor($this->result);

        return $onRejected ? $rejection->then(null, $onRejected) : $rejection;
    }

    public function otherwise(callable $onRejected): PromiseInterface
    {
        return $this->then(null, $onRejected);
    }

    public function wait(bool $unwrap = true)
    {
        $this->waitIfPending();

        if ($this->result instanceof PromiseInterface) {
            return $this->result->wait($unwrap);
        }
        if ($unwrap) {
            if ($this->state === self::FULFILLED) {
                return $this->result;
            }
            throw Create::exceptionFor($this->result);
        }
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function cancel(): void
    {
        if ($this->state !== self::PENDING) {
            return;
        }

        $this->waitFn = $this->waitList = null;

        if ($this->cancelFn) {
            $fn = $this->cancelFn;
            $this->cancelFn = null;
            try {
                $fn();
            } catch (\Throwable $e) {
                $this->reject($e);
            }
        }


        if ($this->state === self::PENDING) {
            $this->reject(new CancellationException('Promise has been cancelled'));
        }
    }

    public function resolve($value): void
    {
        $this->settle(self::FULFILLED, $value);
    }

    public function reject($reason): void
    {
        $this->settle(self::REJECTED, $reason);
    }

    private function settle(string $state, $value): void
    {
        if ($this->state !== self::PENDING) {
            if ($state === $this->state && $value === $this->result) {
                return;
            }
            throw $this->state === $state
                ? new \LogicException("The promise is already {$state}.")
                : new \LogicException("Cannot change a {$this->state} promise to {$state}");
        }

        if ($value === $this) {
            throw new \LogicException('Cannot fulfill or reject a promise with itself');
        }

        $this->state = $state;
        $this->result = $value;
        $handlers = $this->handlers;
        $this->handlers = null;
        $this->waitList = $this->waitFn = null;
        $this->cancelFn = null;

        if (!$handlers) {
            return;
        }

        if (!is_object($value) || !method_exists($value, 'then')) {
            $id = $state === self::FULFILLED ? 1 : 2;
            Utils::queue()->add(static function () use ($id, $value, $handlers): void {
                foreach ($handlers as $handler) {
                    self::callHandler($id, $value, $handler);
                }
            });
        } elseif ($value instanceof Promise && Is::pending($value)) {
            $value->handlers = array_merge($value->handlers, $handlers);
        } else {
            $value->then(
                static function ($value) use ($handlers): void {
                    foreach ($handlers as $handler) {
                        self::callHandler(1, $value, $handler);
                    }
                },
                static function ($reason) use ($handlers): void {
                    foreach ($handlers as $handler) {
                        self::callHandler(2, $reason, $handler);
                    }
                }
            );
        }
    }

    private static function callHandler(int $index, $value, array $handler): void
    {

        $promise = $handler[0];

        if (Is::settled($promise)) {
            return;
        }

        try {
            if (isset($handler[$index])) {
                $f = $handler[$index];
                unset($handler);
                $promise->resolve($f($value));
            } elseif ($index === 1) {
                $promise->resolve($value);
            } else {
                $promise->reject($value);
            }
        } catch (\Throwable $reason) {
            $promise->reject($reason);
        }
    }

    private function waitIfPending(): void
    {
        if ($this->state !== self::PENDING) {
            return;
        } elseif ($this->waitFn) {
            $this->invokeWaitFn();
        } elseif ($this->waitList) {
            $this->invokeWaitList();
        } else {
            $this->reject('Cannot wait on a promise that has '
                .'no internal wait function. You must provide a wait '
                .'function when constructing the promise to be able to '
                .'wait on a promise.');
        }

        Utils::queue()->run();


        if ($this->state === self::PENDING) {
            $this->reject('Invoking the wait callback did not resolve the promise');
        }
    }

    private function invokeWaitFn(): void
    {
        try {
            $wfn = $this->waitFn;
            $this->waitFn = null;
            $wfn(true);
        } catch (\Throwable $reason) {
            if ($this->state === self::PENDING) {
                $this->reject($reason);
            } else {
                throw $reason;
            }
        }
    }

    private function invokeWaitList(): void
    {
        $waitList = $this->waitList;
        $this->waitList = null;

        foreach ($waitList as $result) {
            do {
                $result->waitIfPending();
                $result = $result->result;
            } while ($result instanceof Promise);

            if ($result instanceof PromiseInterface) {
                $result->wait(false);
            }
        }
    }
}
