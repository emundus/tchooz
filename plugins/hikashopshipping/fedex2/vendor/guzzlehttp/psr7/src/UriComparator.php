<?php

declare(strict_types=1);

namespace GuzzleHttp\Psr7;

use Psr\Http\Message\UriInterface;

final class UriComparator
{
    public static function isCrossOrigin(UriInterface $original, UriInterface $modified): bool
    {
        if (\strcasecmp($original->getHost(), $modified->getHost()) !== 0) {
            return true;
        }

        if ($original->getScheme() !== $modified->getScheme()) {
            return true;
        }

        if (self::computePort($original) !== self::computePort($modified)) {
            return true;
        }

        return false;
    }

    private static function computePort(UriInterface $uri): int
    {
        $port = $uri->getPort();

        if (null !== $port) {
            return $port;
        }

        return 'https' === $uri->getScheme() ? 443 : 80;
    }

    private function __construct()
    {
    }
}
