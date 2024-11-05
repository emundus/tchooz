<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

final class Is
{
    public static function pending(PromiseInterface $promise): bool
    {
        return $promise->getState() === PromiseInterface::PENDING;
    }

    public static function settled(PromiseInterface $promise): bool
    {
        return $promise->getState() !== PromiseInterface::PENDING;
    }

    public static function fulfilled(PromiseInterface $promise): bool
    {
        return $promise->getState() === PromiseInterface::FULFILLED;
    }

    public static function rejected(PromiseInterface $promise): bool
    {
        return $promise->getState() === PromiseInterface::REJECTED;
    }
}
