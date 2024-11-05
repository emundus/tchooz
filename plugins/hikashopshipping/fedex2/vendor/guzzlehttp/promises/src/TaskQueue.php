<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

class TaskQueue implements TaskQueueInterface
{
    private $enableShutdown = true;
    private $queue = [];

    public function __construct(bool $withShutdown = true)
    {
        if ($withShutdown) {
            register_shutdown_function(function (): void {
                if ($this->enableShutdown) {
                    $err = error_get_last();
                    if (!$err || ($err['type'] ^ E_ERROR)) {
                        $this->run();
                    }
                }
            });
        }
    }

    public function isEmpty(): bool
    {
        return !$this->queue;
    }

    public function add(callable $task): void
    {
        $this->queue[] = $task;
    }

    public function run(): void
    {
        while ($task = array_shift($this->queue)) {

            $task();
        }
    }

    public function disableShutdown(): void
    {
        $this->enableShutdown = false;
    }
}
