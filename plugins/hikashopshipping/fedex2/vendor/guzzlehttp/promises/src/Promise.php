<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

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
        callable $waitFn = null,
        callable $cancelFn = null
    ) {
        $this->waitFn = $waitFn;
        $this->cancelFn = $cancelFn;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
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

    public function otherwise(callable $onRejected)
    {
        return $this->then(null, $onRejected);
    }

    public function wait($unwrap = true)
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

    public function getState()
    {
        return $this->state;
    }

    public function cancel()
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
            } catch (\Exception $e) {
                $this->reject($e);
            }
        }


        if ($this->state === self::PENDING) {
            $this->reject(new CancellationException('Promise has been cancelled'));
        }
    }

    public function resolve($value)
    {
        $this->settle(self::FULFILLED, $value);
    }

    public function reject($reason)
    {
        $this->settle(self::REJECTED, $reason);
    }

    private function settle($state, $value)
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
            Utils::queue()->add(static function () use ($id, $value, $handlers) {
                foreach ($handlers as $handler) {
                    self::callHandler($id, $value, $handler);
                }
            });
        } elseif ($value instanceof Promise && Is::pending($value)) {
            $value->handlers = array_merge($value->handlers, $handlers);
        } else {
            $value->then(
                static function ($value) use ($handlers) {
                    foreach ($handlers as $handler) {
                        self::callHandler(1, $value, $handler);
                    }
                },
                static function ($reason) use ($handlers) {
                    foreach ($handlers as $handler) {
                        self::callHandler(2, $reason, $handler);
                    }
                }
            );
        }
    }

    private static function callHandler($index, $value, array $handler)
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
        } catch (\Exception $reason) {
            $promise->reject($reason);
        }
    }

    private function waitIfPending()
    {
        if ($this->state !== self::PENDING) {
            return;
        } elseif ($this->waitFn) {
            $this->invokeWaitFn();
        } elseif ($this->waitList) {
            $this->invokeWaitList();
        } else {
            $this->reject('Cannot wait on a promise that has '
                . 'no internal wait function. You must provide a wait '
                . 'function when constructing the promise to be able to '
                . 'wait on a promise.');
        }

        Utils::queue()->run();


        if ($this->state === self::PENDING) {
            $this->reject('Invoking the wait callback did not resolve the promise');
        }
    }

    private function invokeWaitFn()
    {
        try {
            $wfn = $this->waitFn;
            $this->waitFn = null;
            $wfn(true);
        } catch (\Exception $reason) {
            if ($this->state === self::PENDING) {
                $this->reject($reason);
            } else {
                throw $reason;
            }
        }
    }

    private function invokeWaitList()
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
