<?php

declare(strict_types=1);

namespace GuzzleHttp\Psr7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Utils
{
    public static function caselessRemove(array $keys, array $data): array
    {
        $result = [];

        foreach ($keys as &$key) {
            $key = strtolower((string) $key);
        }

        foreach ($data as $k => $v) {
            if (!in_array(strtolower((string) $k), $keys)) {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    public static function copyToStream(StreamInterface $source, StreamInterface $dest, int $maxLen = -1): void
    {
        $bufferSize = 8192;

        if ($maxLen === -1) {
            while (!$source->eof()) {
                if (!$dest->write($source->read($bufferSize))) {
                    break;
                }
            }
        } else {
            $remaining = $maxLen;
            while ($remaining > 0 && !$source->eof()) {
                $buf = $source->read(min($bufferSize, $remaining));
                $len = strlen($buf);
                if (!$len) {
                    break;
                }
                $remaining -= $len;
                $dest->write($buf);
            }
        }
    }

    public static function copyToString(StreamInterface $stream, int $maxLen = -1): string
    {
        $buffer = '';

        if ($maxLen === -1) {
            while (!$stream->eof()) {
                $buf = $stream->read(1048576);
                if ($buf === '') {
                    break;
                }
                $buffer .= $buf;
            }

            return $buffer;
        }

        $len = 0;
        while (!$stream->eof() && $len < $maxLen) {
            $buf = $stream->read($maxLen - $len);
            if ($buf === '') {
                break;
            }
            $buffer .= $buf;
            $len = strlen($buffer);
        }

        return $buffer;
    }

    public static function hash(StreamInterface $stream, string $algo, bool $rawOutput = false): string
    {
        $pos = $stream->tell();

        if ($pos > 0) {
            $stream->rewind();
        }

        $ctx = hash_init($algo);
        while (!$stream->eof()) {
            hash_update($ctx, $stream->read(1048576));
        }

        $out = hash_final($ctx, $rawOutput);
        $stream->seek($pos);

        return $out;
    }

    public static function modifyRequest(RequestInterface $request, array $changes): RequestInterface
    {
        if (!$changes) {
            return $request;
        }

        $headers = $request->getHeaders();

        if (!isset($changes['uri'])) {
            $uri = $request->getUri();
        } else {
            if ($host = $changes['uri']->getHost()) {
                $changes['set_headers']['Host'] = $host;

                if ($port = $changes['uri']->getPort()) {
                    $standardPorts = ['http' => 80, 'https' => 443];
                    $scheme = $changes['uri']->getScheme();
                    if (isset($standardPorts[$scheme]) && $port != $standardPorts[$scheme]) {
                        $changes['set_headers']['Host'] .= ':'.$port;
                    }
                }
            }
            $uri = $changes['uri'];
        }

        if (!empty($changes['remove_headers'])) {
            $headers = self::caselessRemove($changes['remove_headers'], $headers);
        }

        if (!empty($changes['set_headers'])) {
            $headers = self::caselessRemove(array_keys($changes['set_headers']), $headers);
            $headers = $changes['set_headers'] + $headers;
        }

        if (isset($changes['query'])) {
            $uri = $uri->withQuery($changes['query']);
        }

        if ($request instanceof ServerRequestInterface) {
            $new = (new ServerRequest(
                $changes['method'] ?? $request->getMethod(),
                $uri,
                $headers,
                $changes['body'] ?? $request->getBody(),
                $changes['version'] ?? $request->getProtocolVersion(),
                $request->getServerParams()
            ))
            ->withParsedBody($request->getParsedBody())
            ->withQueryParams($request->getQueryParams())
            ->withCookieParams($request->getCookieParams())
            ->withUploadedFiles($request->getUploadedFiles());

            foreach ($request->getAttributes() as $key => $value) {
                $new = $new->withAttribute($key, $value);
            }

            return $new;
        }

        return new Request(
            $changes['method'] ?? $request->getMethod(),
            $uri,
            $headers,
            $changes['body'] ?? $request->getBody(),
            $changes['version'] ?? $request->getProtocolVersion()
        );
    }

    public static function readLine(StreamInterface $stream, ?int $maxLength = null): string
    {
        $buffer = '';
        $size = 0;

        while (!$stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n" || ++$size === $maxLength - 1) {
                break;
            }
        }

        return $buffer;
    }

    public static function redactUserInfo(UriInterface $uri): UriInterface
    {
        $userInfo = $uri->getUserInfo();

        if (false !== ($pos = \strpos($userInfo, ':'))) {
            return $uri->withUserInfo(\substr($userInfo, 0, $pos), '***');
        }

        return $uri;
    }

    public static function streamFor($resource = '', array $options = []): StreamInterface
    {
        if (is_scalar($resource)) {
            $stream = self::tryFopen('php://temp', 'r+');
            if ($resource !== '') {
                fwrite($stream, (string) $resource);
                fseek($stream, 0);
            }

            return new Stream($stream, $options);
        }

        switch (gettype($resource)) {
            case 'resource':


                if ((\stream_get_meta_data($resource)['uri'] ?? '') === 'php://input') {
                    $stream = self::tryFopen('php://temp', 'w+');
                    stream_copy_to_stream($resource, $stream);
                    fseek($stream, 0);
                    $resource = $stream;
                }

                return new Stream($resource, $options);
            case 'object':

                if ($resource instanceof StreamInterface) {
                    return $resource;
                } elseif ($resource instanceof \Iterator) {
                    return new PumpStream(function () use ($resource) {
                        if (!$resource->valid()) {
                            return false;
                        }
                        $result = $resource->current();
                        $resource->next();

                        return $result;
                    }, $options);
                } elseif (method_exists($resource, '__toString')) {
                    return self::streamFor((string) $resource, $options);
                }
                break;
            case 'NULL':
                return new Stream(self::tryFopen('php://temp', 'r+'), $options);
        }

        if (is_callable($resource)) {
            return new PumpStream($resource, $options);
        }

        throw new \InvalidArgumentException('Invalid resource type: '.gettype($resource));
    }

    public static function tryFopen(string $filename, string $mode)
    {
        $ex = null;
        set_error_handler(static function (int $errno, string $errstr) use ($filename, $mode, &$ex): bool {
            $ex = new \RuntimeException(sprintf(
                'Unable to open "%s" using mode "%s": %s',
                $filename,
                $mode,
                $errstr
            ));

            return true;
        });

        try {

            $handle = fopen($filename, $mode);
        } catch (\Throwable $e) {
            $ex = new \RuntimeException(sprintf(
                'Unable to open "%s" using mode "%s": %s',
                $filename,
                $mode,
                $e->getMessage()
            ), 0, $e);
        }

        restore_error_handler();

        if ($ex) {

            throw $ex;
        }

        return $handle;
    }

    public static function tryGetContents($stream): string
    {
        $ex = null;
        set_error_handler(static function (int $errno, string $errstr) use (&$ex): bool {
            $ex = new \RuntimeException(sprintf(
                'Unable to read stream contents: %s',
                $errstr
            ));

            return true;
        });

        try {

            $contents = stream_get_contents($stream);

            if ($contents === false) {
                $ex = new \RuntimeException('Unable to read stream contents');
            }
        } catch (\Throwable $e) {
            $ex = new \RuntimeException(sprintf(
                'Unable to read stream contents: %s',
                $e->getMessage()
            ), 0, $e);
        }

        restore_error_handler();

        if ($ex) {

            throw $ex;
        }

        return $contents;
    }

    public static function uriFor($uri): UriInterface
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }

        if (is_string($uri)) {
            return new Uri($uri);
        }

        throw new \InvalidArgumentException('URI must be a string or UriInterface');
    }
}
