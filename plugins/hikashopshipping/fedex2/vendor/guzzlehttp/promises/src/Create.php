<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

final class Create
{
    public static function promiseFor($value): PromiseInterface
    {
        if ($value instanceof PromiseInterface) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'then')) {
            $wfn = method_exists($value, 'wait') ? [$value, 'wait'] : null;
            $cfn = method_exists($value, 'cancel') ? [$value, 'cancel'] : null;
            $promise = new Promise($wfn, $cfn);
            $value->then([$promise, 'resolve'], [$promise, 'reject']);

            return $promise;
        }

        return new FulfilledPromise($value);
    }

    public static function rejectionFor($reason): PromiseInterface
    {
        if ($reason instanceof PromiseInterface) {
            return $reason;
        }

        return new RejectedPromise($reason);
    }

    public static function exceptionFor($reason): \Throwable
    {
        if ($reason instanceof \Throwable) {
            return $reason;
        }

        return new RejectionException($reason);
    }

    public static function iterFor($value): \Iterator
    {
        if ($value instanceof \Iterator) {
            return $value;
        }

        if (is_array($value)) {
            return new \ArrayIterator($value);
        }

        return new \ArrayIterator([$value]);
    }
}
