<?php

namespace GuzzleHttp;

use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class Middleware
{
    public static function cookies(): callable
    {
        return static function (callable $handler): callable {
            return static function ($request, array $options) use ($handler) {
                if (empty($options['cookies'])) {
                    return $handler($request, $options);
                } elseif (!($options['cookies'] instanceof CookieJarInterface)) {
                    throw new \InvalidArgumentException('cookies must be an instance of GuzzleHttp\Cookie\CookieJarInterface');
                }
                $cookieJar = $options['cookies'];
                $request = $cookieJar->withCookieHeader($request);

                return $handler($request, $options)
                    ->then(
                        static function (ResponseInterface $response) use ($cookieJar, $request): ResponseInterface {
                            $cookieJar->extractCookies($request, $response);

                            return $response;
                        }
                    );
            };
        };
    }

    public static function httpErrors(?BodySummarizerInterface $bodySummarizer = null): callable
    {
        return static function (callable $handler) use ($bodySummarizer): callable {
            return static function ($request, array $options) use ($handler, $bodySummarizer) {
                if (empty($options['http_errors'])) {
                    return $handler($request, $options);
                }

                return $handler($request, $options)->then(
                    static function (ResponseInterface $response) use ($request, $bodySummarizer) {
                        $code = $response->getStatusCode();
                        if ($code < 400) {
                            return $response;
                        }
                        throw RequestException::create($request, $response, null, [], $bodySummarizer);
                    }
                );
            };
        };
    }

    public static function history(&$container): callable
    {
        if (!\is_array($container) && !$container instanceof \ArrayAccess) {
            throw new \InvalidArgumentException('history container must be an array or object implementing ArrayAccess');
        }

        return static function (callable $handler) use (&$container): callable {
            return static function (RequestInterface $request, array $options) use ($handler, &$container) {
                return $handler($request, $options)->then(
                    static function ($value) use ($request, &$container, $options) {
                        $container[] = [
                            'request' => $request,
                            'response' => $value,
                            'error' => null,
                            'options' => $options,
                        ];

                        return $value;
                    },
                    static function ($reason) use ($request, &$container, $options) {
                        $container[] = [
                            'request' => $request,
                            'response' => null,
                            'error' => $reason,
                            'options' => $options,
                        ];

                        return P\Create::rejectionFor($reason);
                    }
                );
            };
        };
    }

    public static function tap(?callable $before = null, ?callable $after = null): callable
    {
        return static function (callable $handler) use ($before, $after): callable {
            return static function (RequestInterface $request, array $options) use ($handler, $before, $after) {
                if ($before) {
                    $before($request, $options);
                }
                $response = $handler($request, $options);
                if ($after) {
                    $after($request, $options, $response);
                }

                return $response;
            };
        };
    }

    public static function redirect(): callable
    {
        return static function (callable $handler): RedirectMiddleware {
            return new RedirectMiddleware($handler);
        };
    }

    public static function retry(callable $decider, ?callable $delay = null): callable
    {
        return static function (callable $handler) use ($decider, $delay): RetryMiddleware {
            return new RetryMiddleware($decider, $handler, $delay);
        };
    }

    public static function log(LoggerInterface $logger, $formatter, string $logLevel = 'info'): callable
    {
        if (!$formatter instanceof MessageFormatter && !$formatter instanceof MessageFormatterInterface) {
            throw new \LogicException(sprintf('Argument 2 to %s::log() must be of type %s', self::class, MessageFormatterInterface::class));
        }

        return static function (callable $handler) use ($logger, $formatter, $logLevel): callable {
            return static function (RequestInterface $request, array $options = []) use ($handler, $logger, $formatter, $logLevel) {
                return $handler($request, $options)->then(
                    static function ($response) use ($logger, $request, $formatter, $logLevel): ResponseInterface {
                        $message = $formatter->format($request, $response);
                        $logger->log($logLevel, $message);

                        return $response;
                    },
                    static function ($reason) use ($logger, $request, $formatter): PromiseInterface {
                        $response = $reason instanceof RequestException ? $reason->getResponse() : null;
                        $message = $formatter->format($request, $response, P\Create::exceptionFor($reason));
                        $logger->error($message);

                        return P\Create::rejectionFor($reason);
                    }
                );
            };
        };
    }

    public static function prepareBody(): callable
    {
        return static function (callable $handler): PrepareBodyMiddleware {
            return new PrepareBodyMiddleware($handler);
        };
    }

    public static function mapRequest(callable $fn): callable
    {
        return static function (callable $handler) use ($fn): callable {
            return static function (RequestInterface $request, array $options) use ($handler, $fn) {
                return $handler($fn($request), $options);
            };
        };
    }

    public static function mapResponse(callable $fn): callable
    {
        return static function (callable $handler) use ($fn): callable {
            return static function (RequestInterface $request, array $options) use ($handler, $fn) {
                return $handler($request, $options)->then($fn);
            };
        };
    }
}
