<?php

namespace GuzzleHttp;

use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
use Psr\Http\Message\UriInterface;

final class Utils
{
    public static function describeType($input): string
    {
        switch (\gettype($input)) {
            case 'object':
                return 'object('.\get_class($input).')';
            case 'array':
                return 'array('.\count($input).')';
            default:
                \ob_start();
                \var_dump($input);

                $varDumpContent = \ob_get_clean();

                return \str_replace('double(', 'float(', \rtrim($varDumpContent));
        }
    }

    public static function headersFromLines(iterable $lines): array
    {
        $headers = [];

        foreach ($lines as $line) {
            $parts = \explode(':', $line, 2);
            $headers[\trim($parts[0])][] = isset($parts[1]) ? \trim($parts[1]) : null;
        }

        return $headers;
    }

    public static function debugResource($value = null)
    {
        if (\is_resource($value)) {
            return $value;
        }
        if (\defined('STDOUT')) {
            return \STDOUT;
        }

        return Psr7\Utils::tryFopen('php://output', 'w');
    }

    public static function chooseHandler(): callable
    {
        $handler = null;

        if (\defined('CURLOPT_CUSTOMREQUEST') && \function_exists('curl_version') && version_compare(curl_version()['version'], '7.21.2') >= 0) {
            if (\function_exists('curl_multi_exec') && \function_exists('curl_exec')) {
                $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
            } elseif (\function_exists('curl_exec')) {
                $handler = new CurlHandler();
            } elseif (\function_exists('curl_multi_exec')) {
                $handler = new CurlMultiHandler();
            }
        }

        if (\ini_get('allow_url_fopen')) {
            $handler = $handler
                ? Proxy::wrapStreaming($handler, new StreamHandler())
                : new StreamHandler();
        } elseif (!$handler) {
            throw new \RuntimeException('GuzzleHttp requires cURL, the allow_url_fopen ini setting, or a custom HTTP handler.');
        }

        return $handler;
    }

    public static function defaultUserAgent(): string
    {
        return sprintf('GuzzleHttp/%d', ClientInterface::MAJOR_VERSION);
    }

    public static function defaultCaBundle(): string
    {
        static $cached = null;
        static $cafiles = [
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/usr/local/share/certs/ca-root-nss.crt',
            '/var/lib/ca-certificates/ca-bundle.pem',
            '/usr/local/etc/openssl/cert.pem',
            '/etc/ca-certificates.crt',
            'C:\\windows\\system32\\curl-ca-bundle.crt',
            'C:\\windows\\curl-ca-bundle.crt',
        ];

        if ($cached) {
            return $cached;
        }

        if ($ca = \ini_get('openssl.cafile')) {
            return $cached = $ca;
        }

        if ($ca = \ini_get('curl.cainfo')) {
            return $cached = $ca;
        }

        foreach ($cafiles as $filename) {
            if (\file_exists($filename)) {
                return $cached = $filename;
            }
        }

        throw new \RuntimeException(
            <<< EOT
No system CA bundle could be found in any of the the common system locations.
PHP versions earlier than 5.6 are not properly configured to use the system's
CA bundle by default. In order to verify peer certificates, you will need to
supply the path on disk to a certificate bundle to the 'verify' request
option: https://docs.guzzlephp.org/en/latest/request-options.html#verify. If
you do not need a specific certificate bundle, then Mozilla provides a commonly
used CA bundle which can be downloaded here (provided by the maintainer of
cURL): https://curl.haxx.se/ca/cacert.pem. Once you have a CA bundle available
on disk, you can set the 'openssl.cafile' PHP ini setting to point to the path
to the file, allowing you to omit the 'verify' request option. See
https://curl.haxx.se/docs/sslcerts.html for more information.
EOT
        );
    }

    public static function normalizeHeaderKeys(array $headers): array
    {
        $result = [];
        foreach (\array_keys($headers) as $key) {
            $result[\strtolower($key)] = $key;
        }

        return $result;
    }

    public static function isHostInNoProxy(string $host, array $noProxyArray): bool
    {
        if (\strlen($host) === 0) {
            throw new InvalidArgumentException('Empty host provided');
        }

        [$host] = \explode(':', $host, 2);

        foreach ($noProxyArray as $area) {
            if ($area === '*') {
                return true;
            }

            if (empty($area)) {
                continue;
            }

            if ($area === $host) {
                return true;
            }
            $area = '.'.\ltrim($area, '.');
            if (\substr($host, -\strlen($area)) === $area) {
                return true;
            }
        }

        return false;
    }

    public static function jsonDecode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $data = \json_decode($json, $assoc, $depth, $options);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidArgumentException('json_decode error: '.\json_last_error_msg());
        }

        return $data;
    }

    public static function jsonEncode($value, int $options = 0, int $depth = 512): string
    {
        $json = \json_encode($value, $options, $depth);
        if (\JSON_ERROR_NONE !== \json_last_error()) {
            throw new InvalidArgumentException('json_encode error: '.\json_last_error_msg());
        }


        return $json;
    }

    public static function currentTime(): float
    {
        return (float) \function_exists('hrtime') ? \hrtime(true) / 1e9 : \microtime(true);
    }

    public static function idnUriConvert(UriInterface $uri, int $options = 0): UriInterface
    {
        if ($uri->getHost()) {
            $asciiHost = self::idnToAsci($uri->getHost(), $options, $info);
            if ($asciiHost === false) {
                $errorBitSet = $info['errors'] ?? 0;

                $errorConstants = array_filter(array_keys(get_defined_constants()), static function (string $name): bool {
                    return substr($name, 0, 11) === 'IDNA_ERROR_';
                });

                $errors = [];
                foreach ($errorConstants as $errorConstant) {
                    if ($errorBitSet & constant($errorConstant)) {
                        $errors[] = $errorConstant;
                    }
                }

                $errorMessage = 'IDN conversion failed';
                if ($errors) {
                    $errorMessage .= ' (errors: '.implode(', ', $errors).')';
                }

                throw new InvalidArgumentException($errorMessage);
            }
            if ($uri->getHost() !== $asciiHost) {
                $uri = $uri->withHost($asciiHost);
            }
        }

        return $uri;
    }

    public static function getenv(string $name): ?string
    {
        if (isset($_SERVER[$name])) {
            return (string) $_SERVER[$name];
        }

        if (\PHP_SAPI === 'cli' && ($value = \getenv($name)) !== false && $value !== null) {
            return (string) $value;
        }

        return null;
    }

    private static function idnToAsci(string $domain, int $options, ?array &$info = [])
    {
        if (\function_exists('idn_to_ascii') && \defined('INTL_IDNA_VARIANT_UTS46')) {
            return \idn_to_ascii($domain, $options, \INTL_IDNA_VARIANT_UTS46, $info);
        }

        throw new \Error('ext-idn or symfony/polyfill-intl-idn not loaded or too old');
    }
}
