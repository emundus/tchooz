<?php

declare(strict_types=1);

namespace GuzzleHttp\Promise;

final class Utils
{
    public static function queue(?TaskQueueInterface $assign = null): TaskQueueInterface
    {
        static $queue;

        if ($assign) {
            $queue = $assign;
        } elseif (!$queue) {
            $queue = new TaskQueue();
        }

        return $queue;
    }

    public static function task(callable $task): PromiseInterface
    {
        $queue = self::queue();
        $promise = new Promise([$queue, 'run']);
        $queue->add(function () use ($task, $promise): void {
            try {
                if (Is::pending($promise)) {
                    $promise->resolve($task());
                }
            } catch (\Throwable $e) {
                $promise->reject($e);
            }
        });

        return $promise;
    }

    public static function inspect(PromiseInterface $promise): array
    {
        try {
            return [
                'state' => PromiseInterface::FULFILLED,
                'value' => $promise->wait(),
            ];
        } catch (RejectionException $e) {
            return ['state' => PromiseInterface::REJECTED, 'reason' => $e->getReason()];
        } catch (\Throwable $e) {
            return ['state' => PromiseInterface::REJECTED, 'reason' => $e];
        }
    }

    public static function inspectAll($promises): array
    {
        $results = [];
        foreach ($promises as $key => $promise) {
            $results[$key] = self::inspect($promise);
        }

        return $results;
    }

    public static function unwrap($promises): array
    {
        $results = [];
        foreach ($promises as $key => $promise) {
            $results[$key] = $promise->wait();
        }

        return $results;
    }

    public static function all($promises, bool $recursive = false): PromiseInterface
    {
        $results = [];
        $promise = Each::of(
            $promises,
            function ($value, $idx) use (&$results): void {
                $results[$idx] = $value;
            },
            function ($reason, $idx, Promise $aggregate): void {
                $aggregate->reject($reason);
            }
        )->then(function () use (&$results) {
            ksort($results);

            return $results;
        });

        if (true === $recursive) {
            $promise = $promise->then(function ($results) use ($recursive, &$promises) {
                foreach ($promises as $promise) {
                    if (Is::pending($promise)) {
                        return self::all($promises, $recursive);
                    }
                }

                return $results;
            });
        }

        return $promise;
    }

    public static function some(int $count, $promises): PromiseInterface
    {
        $results = [];
        $rejections = [];

        return Each::of(
            $promises,
            function ($value, $idx, PromiseInterface $p) use (&$results, $count): void {
                if (Is::settled($p)) {
                    return;
                }
                $results[$idx] = $value;
                if (count($results) >= $count) {
                    $p->resolve(null);
                }
            },
            function ($reason) use (&$rejections): void {
                $rejections[] = $reason;
            }
        )->then(
            function () use (&$results, &$rejections, $count) {
                if (count($results) !== $count) {
                    throw new AggregateException(
                        'Not enough promises to fulfill count',
                        $rejections
                    );
                }
                ksort($results);

                return array_values($results);
            }
        );
    }

    public static function any($promises): PromiseInterface
    {
        return self::some(1, $promises)->then(function ($values) {
            return $values[0];
        });
    }

    public static function settle($promises): PromiseInterface
    {
        $results = [];

        return Each::of(
            $promises,
            function ($value, $idx) use (&$results): void {
                $results[$idx] = ['state' => PromiseInterface::FULFILLED, 'value' => $value];
            },
            function ($reason, $idx) use (&$results): void {
                $results[$idx] = ['state' => PromiseInterface::REJECTED, 'reason' => $reason];
            }
        )->then(function () use (&$results) {
            ksort($results);

            return $results;
        });
    }
}
