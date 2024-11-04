<?php

namespace GuzzleHttp;

function describe_type($input): string
{
    return Utils::describeType($input);
}

function headers_from_lines(iterable $lines): array
{
    return Utils::headersFromLines($lines);
}

function debug_resource($value = null)
{
    return Utils::debugResource($value);
}

function choose_handler(): callable
{
    return Utils::chooseHandler();
}

function default_user_agent(): string
{
    return Utils::defaultUserAgent();
}

function default_ca_bundle(): string
{
    return Utils::defaultCaBundle();
}

function normalize_header_keys(array $headers): array
{
    return Utils::normalizeHeaderKeys($headers);
}

function is_host_in_noproxy(string $host, array $noProxyArray): bool
{
    return Utils::isHostInNoProxy($host, $noProxyArray);
}

function json_decode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
{
    return Utils::jsonDecode($json, $assoc, $depth, $options);
}

function json_encode($value, int $options = 0, int $depth = 512): string
{
    return Utils::jsonEncode($value, $options, $depth);
}
