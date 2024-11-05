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

use Exception;
use Generator;
use Throwable;

final class Coroutine implements PromiseInterface
{
    private $currentPromise;

    private $generator;

    private $result;

    public function __construct(callable $generatorFn)
    {
        $this->generator = $generatorFn();
        $this->result = new Promise(function () {
            while (isset($this->currentPromise)) {
                $this->currentPromise->wait();
            }
        });
        try {
            $this->nextCoroutine($this->generator->current());
        } catch (\Exception $exception) {
            $this->result->reject($exception);
        } catch (Throwable $throwable) {
            $this->result->reject($throwable);
        }
    }

    public static function of(callable $generatorFn)
    {
        return new self($generatorFn);
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null
    ) {
        return $this->result->then($onFulfilled, $onRejected);
    }

    public function otherwise(callable $onRejected)
    {
        return $this->result->otherwise($onRejected);
    }

    public function wait($unwrap = true)
    {
        return $this->result->wait($unwrap);
    }

    public function getState()
    {
        return $this->result->getState();
    }

    public function resolve($value)
    {
        $this->result->resolve($value);
    }

    public function reject($reason)
    {
        $this->result->reject($reason);
    }

    public function cancel()
    {
        $this->currentPromise->cancel();
        $this->result->cancel();
    }

    private function nextCoroutine($yielded)
    {
        $this->currentPromise = Create::promiseFor($yielded)
            ->then([$this, '_handleSuccess'], [$this, '_handleFailure']);
    }

    public function _handleSuccess($value)
    {
        unset($this->currentPromise);
        try {
            $next = $this->generator->send($value);
            if ($this->generator->valid()) {
                $this->nextCoroutine($next);
            } else {
                $this->result->resolve($value);
            }
        } catch (Exception $exception) {
            $this->result->reject($exception);
        } catch (Throwable $throwable) {
            $this->result->reject($throwable);
        }
    }

    public function _handleFailure($reason)
    {
        unset($this->currentPromise);
        try {
            $nextYield = $this->generator->throw(Create::exceptionFor($reason));
            $this->nextCoroutine($nextYield);
        } catch (Exception $exception) {
            $this->result->reject($exception);
        } catch (Throwable $throwable) {
            $this->result->reject($throwable);
        }
    }
}
