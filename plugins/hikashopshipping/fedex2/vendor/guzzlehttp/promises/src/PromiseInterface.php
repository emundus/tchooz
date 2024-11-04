<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

interface PromiseInterface
{
    public const PENDING = 'pending';
    public const FULFILLED = 'fulfilled';
    public const REJECTED = 'rejected';

    public function then(
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ): PromiseInterface;

    public function otherwise(callable $onRejected): PromiseInterface;

    public function getState(): string;

    public function resolve($value): void;

    public function reject($reason): void;

    public function cancel(): void;

    public function wait(bool $unwrap = true);
}
