<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

interface TaskQueueInterface
{
    public function isEmpty(): bool;

    public function add(callable $task): void;

    public function run(): void;
}
