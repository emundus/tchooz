<?php

namespace GuzzleHttp;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class RedirectMiddleware
{
    public const HISTORY_HEADER = 'X-Guzzle-Redirect-History';

    public const STATUS_HISTORY_HEADER = 'X-Guzzle-Redirect-Status-History';

    public static $defaultSettings = [
        'max' => 5,
        'protocols' => ['http', 'https'],
        'strict' => false,
        'referer' => false,
        'track_redirects' => false,
    ];

    private $nextHandler;

    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $fn = $this->nextHandler;

        if (empty($options['allow_redirects'])) {
            return $fn($request, $options);
        }

        if ($options['allow_redirects'] === true) {
            $options['allow_redirects'] = self::$defaultSettings;
        } elseif (!\is_array($options['allow_redirects'])) {
            throw new \InvalidArgumentException('allow_redirects must be true, false, or array');
        } else {
            $options['allow_redirects'] += self::$defaultSettings;
        }

        if (empty($options['allow_redirects']['max'])) {
            return $fn($request, $options);
        }

        return $fn($request, $options)
            ->then(function (ResponseInterface $response) use ($request, $options) {
                return $this->checkRedirect($request, $options, $response);
            });
    }

    public function checkRedirect(RequestInterface $request, array $options, ResponseInterface $response)
    {
        if (\strpos((string) $response->getStatusCode(), '3') !== 0
            || !$response->hasHeader('Location')
        ) {
            return $response;
        }

        $this->guardMax($request, $response, $options);
        $nextRequest = $this->modifyRequest($request, $options, $response);

        if (Psr7\UriComparator::isCrossOrigin($request->getUri(), $nextRequest->getUri()) && defined('\CURLOPT_HTTPAUTH')) {
            unset(
                $options['curl'][\CURLOPT_HTTPAUTH],
                $options['curl'][\CURLOPT_USERPWD]
            );
        }

        if (isset($options['allow_redirects']['on_redirect'])) {
            ($options['allow_redirects']['on_redirect'])(
                $request,
                $response,
                $nextRequest->getUri()
            );
        }

        $promise = $this($nextRequest, $options);

        if (!empty($options['allow_redirects']['track_redirects'])) {
            return $this->withTracking(
                $promise,
                (string) $nextRequest->getUri(),
                $response->getStatusCode()
            );
        }

        return $promise;
    }

    private function withTracking(PromiseInterface $promise, string $uri, int $statusCode): PromiseInterface
    {
        return $promise->then(
            static function (ResponseInterface $response) use ($uri, $statusCode) {
                $historyHeader = $response->getHeader(self::HISTORY_HEADER);
                $statusHeader = $response->getHeader(self::STATUS_HISTORY_HEADER);
                \array_unshift($historyHeader, $uri);
                \array_unshift($statusHeader, (string) $statusCode);

                return $response->withHeader(self::HISTORY_HEADER, $historyHeader)
                                ->withHeader(self::STATUS_HISTORY_HEADER, $statusHeader);
            }
        );
    }

    private function guardMax(RequestInterface $request, ResponseInterface $response, array &$options): void
    {
        $current = $options['__redirect_count']
            ?? 0;
        $options['__redirect_count'] = $current + 1;
        $max = $options['allow_redirects']['max'];

        if ($options['__redirect_count'] > $max) {
            throw new TooManyRedirectsException("Will not follow more than {$max} redirects", $request, $response);
        }
    }

    public function modifyRequest(RequestInterface $request, array $options, ResponseInterface $response): RequestInterface
    {
        $modify = [];
        $protocols = $options['allow_redirects']['protocols'];

        $statusCode = $response->getStatusCode();
        if ($statusCode == 303
            || ($statusCode <= 302 && !$options['allow_redirects']['strict'])
        ) {
            $safeMethods = ['GET', 'HEAD', 'OPTIONS'];
            $requestMethod = $request->getMethod();

            $modify['method'] = in_array($requestMethod, $safeMethods) ? $requestMethod : 'GET';
            $modify['body'] = '';
        }

        $uri = self::redirectUri($request, $response, $protocols);
        if (isset($options['idn_conversion']) && ($options['idn_conversion'] !== false)) {
            $idnOptions = ($options['idn_conversion'] === true) ? \IDNA_DEFAULT : $options['idn_conversion'];
            $uri = Utils::idnUriConvert($uri, $idnOptions);
        }

        $modify['uri'] = $uri;
        Psr7\Message::rewindBody($request);

        if ($options['allow_redirects']['referer']
            && $modify['uri']->getScheme() === $request->getUri()->getScheme()
        ) {
            $uri = $request->getUri()->withUserInfo('');
            $modify['set_headers']['Referer'] = (string) $uri;
        } else {
            $modify['remove_headers'][] = 'Referer';
        }

        if (Psr7\UriComparator::isCrossOrigin($request->getUri(), $modify['uri'])) {
            $modify['remove_headers'][] = 'Authorization';
            $modify['remove_headers'][] = 'Cookie';
        }

        return Psr7\Utils::modifyRequest($request, $modify);
    }

    private static function redirectUri(
        RequestInterface $request,
        ResponseInterface $response,
        array $protocols
    ): UriInterface {
        $location = Psr7\UriResolver::resolve(
            $request->getUri(),
            new Psr7\Uri($response->getHeaderLine('Location'))
        );

        if (!\in_array($location->getScheme(), $protocols)) {
            throw new BadResponseException(\sprintf('Redirect URI, %s, does not use one of the allowed redirect protocols: %s', $location, \implode(', ', $protocols)), $request, $response);
        }

        return $location;
    }
}
