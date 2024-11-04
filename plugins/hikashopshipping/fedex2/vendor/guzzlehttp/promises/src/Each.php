<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

final class Each
{
    public static function of(
        $iterable,
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ): PromiseInterface {
        return (new EachPromise($iterable, [
            'fulfilled' => $onFulfilled,
            'rejected' => $onRejected,
        ]))->promise();
    }

    public static function ofLimit(
        $iterable,
        $concurrency,
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ): PromiseInterface {
        return (new EachPromise($iterable, [
            'fulfilled' => $onFulfilled,
            'rejected' => $onRejected,
            'concurrency' => $concurrency,
        ]))->promise();
    }

    public static function ofLimitAll(
        $iterable,
        $concurrency,
        ?callable $onFulfilled = null
    ): PromiseInterface {
        return self::ofLimit(
            $iterable,
            $concurrency,
            $onFulfilled,
            function ($reason, $idx, PromiseInterface $aggregate): void {
                $aggregate->reject($reason);
            }
        );
    }
}
