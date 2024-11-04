<?php

declare(strict_types=1);

namespace GuzzleHttp\Psr7;

use GuzzleHttp\Psr7\Exception\MalformedUriException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface, \JsonSerializable
{
    private const HTTP_DEFAULT_HOST = 'localhost';

    private const DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';
    private const QUERY_SEPARATORS_REPLACEMENT = ['=' => '%3D', '&' => '%26'];


    private $scheme = '';


    private $userInfo = '';


    private $host = '';


    private $port;


    private $path = '';


    private $query = '';


    private $fragment = '';


    private $composedComponents;

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = self::parse($uri);
            if ($parts === false) {
                throw new MalformedUriException("Unable to parse URI: $uri");
            }
            $this->applyParts($parts);
        }
    }

    private static function parse(string $url)
    {
        $prefix = '';
        if (preg_match('%^(.*://\[[0-9:a-f]+\])(.*?)$%', $url, $matches)) {

            $prefix = $matches[1];
            $url = $matches[2];
        }


        $encodedUrl = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            static function ($matches) {
                return urlencode($matches[0]);
            },
            $url
        );

        $result = parse_url($prefix.$encodedUrl);

        if ($result === false) {
            return false;
        }

        return array_map('urldecode', $result);
    }

    public function __toString(): string
    {
        if ($this->composedComponents === null) {
            $this->composedComponents = self::composeComponents(
                $this->scheme,
                $this->getAuthority(),
                $this->path,
                $this->query,
                $this->fragment
            );
        }

        return $this->composedComponents;
    }

    public static function composeComponents(?string $scheme, ?string $authority, string $path, ?string $query, ?string $fragment): string
    {
        $uri = '';

        if ($scheme != '') {
            $uri .= $scheme.':';
        }

        if ($authority != '' || $scheme === 'file') {
            $uri .= '//'.$authority;
        }

        if ($authority != '' && $path != '' && $path[0] != '/') {
            $path = '/'.$path;
        }

        $uri .= $path;

        if ($query != '') {
            $uri .= '?'.$query;
        }

        if ($fragment != '') {
            $uri .= '#'.$fragment;
        }

        return $uri;
    }

    public static function isDefaultPort(UriInterface $uri): bool
    {
        return $uri->getPort() === null
            || (isset(self::DEFAULT_PORTS[$uri->getScheme()]) && $uri->getPort() === self::DEFAULT_PORTS[$uri->getScheme()]);
    }

    public static function isAbsolute(UriInterface $uri): bool
    {
        return $uri->getScheme() !== '';
    }

    public static function isNetworkPathReference(UriInterface $uri): bool
    {
        return $uri->getScheme() === '' && $uri->getAuthority() !== '';
    }

    public static function isAbsolutePathReference(UriInterface $uri): bool
    {
        return $uri->getScheme() === ''
            && $uri->getAuthority() === ''
            && isset($uri->getPath()[0])
            && $uri->getPath()[0] === '/';
    }

    public static function isRelativePathReference(UriInterface $uri): bool
    {
        return $uri->getScheme() === ''
            && $uri->getAuthority() === ''
            && (!isset($uri->getPath()[0]) || $uri->getPath()[0] !== '/');
    }

    public static function isSameDocumentReference(UriInterface $uri, ?UriInterface $base = null): bool
    {
        if ($base !== null) {
            $uri = UriResolver::resolve($base, $uri);

            return ($uri->getScheme() === $base->getScheme())
                && ($uri->getAuthority() === $base->getAuthority())
                && ($uri->getPath() === $base->getPath())
                && ($uri->getQuery() === $base->getQuery());
        }

        return $uri->getScheme() === '' && $uri->getAuthority() === '' && $uri->getPath() === '' && $uri->getQuery() === '';
    }

    public static function withoutQueryValue(UriInterface $uri, string $key): UriInterface
    {
        $result = self::getFilteredQueryString($uri, [$key]);

        return $uri->withQuery(implode('&', $result));
    }

    public static function withQueryValue(UriInterface $uri, string $key, ?string $value): UriInterface
    {
        $result = self::getFilteredQueryString($uri, [$key]);

        $result[] = self::generateQueryString($key, $value);

        return $uri->withQuery(implode('&', $result));
    }

    public static function withQueryValues(UriInterface $uri, array $keyValueArray): UriInterface
    {
        $result = self::getFilteredQueryString($uri, array_keys($keyValueArray));

        foreach ($keyValueArray as $key => $value) {
            $result[] = self::generateQueryString((string) $key, $value !== null ? (string) $value : null);
        }

        return $uri->withQuery(implode('&', $result));
    }

    public static function fromParts(array $parts): UriInterface
    {
        $uri = new self();
        $uri->applyParts($parts);
        $uri->validateState();

        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo.'@'.$authority;
        }

        if ($this->port !== null) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme($scheme): UriInterface
    {
        $scheme = $this->filterScheme($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->composedComponents = null;
        $new->removeDefaultPort();
        $new->validateState();

        return $new;
    }

    public function withUserInfo($user, $password = null): UriInterface
    {
        $info = $this->filterUserInfoComponent($user);
        if ($password !== null) {
            $info .= ':'.$this->filterUserInfoComponent($password);
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;
        $new->composedComponents = null;
        $new->validateState();

        return $new;
    }

    public function withHost($host): UriInterface
    {
        $host = $this->filterHost($host);

        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        $new->composedComponents = null;
        $new->validateState();

        return $new;
    }

    public function withPort($port): UriInterface
    {
        $port = $this->filterPort($port);

        if ($this->port === $port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        $new->composedComponents = null;
        $new->removeDefaultPort();
        $new->validateState();

        return $new;
    }

    public function withPath($path): UriInterface
    {
        $path = $this->filterPath($path);

        if ($this->path === $path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        $new->composedComponents = null;
        $new->validateState();

        return $new;
    }

    public function withQuery($query): UriInterface
    {
        $query = $this->filterQueryAndFragment($query);

        if ($this->query === $query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        $new->composedComponents = null;

        return $new;
    }

    public function withFragment($fragment): UriInterface
    {
        $fragment = $this->filterQueryAndFragment($fragment);

        if ($this->fragment === $fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        $new->composedComponents = null;

        return $new;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    private function applyParts(array $parts): void
    {
        $this->scheme = isset($parts['scheme'])
            ? $this->filterScheme($parts['scheme'])
            : '';
        $this->userInfo = isset($parts['user'])
            ? $this->filterUserInfoComponent($parts['user'])
            : '';
        $this->host = isset($parts['host'])
            ? $this->filterHost($parts['host'])
            : '';
        $this->port = isset($parts['port'])
            ? $this->filterPort($parts['port'])
            : null;
        $this->path = isset($parts['path'])
            ? $this->filterPath($parts['path'])
            : '';
        $this->query = isset($parts['query'])
            ? $this->filterQueryAndFragment($parts['query'])
            : '';
        $this->fragment = isset($parts['fragment'])
            ? $this->filterQueryAndFragment($parts['fragment'])
            : '';
        if (isset($parts['pass'])) {
            $this->userInfo .= ':'.$this->filterUserInfoComponent($parts['pass']);
        }

        $this->removeDefaultPort();
    }

    private function filterScheme($scheme): string
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }

        return \strtr($scheme, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    private function filterUserInfoComponent($component): string
    {
        if (!is_string($component)) {
            throw new \InvalidArgumentException('User info must be a string');
        }

        return preg_replace_callback(
            '/(?:[^%'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.']+|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $component
        );
    }

    private function filterHost($host): string
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }

        return \strtr($host, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    private function filterPort($port): ?int
    {
        if ($port === null) {
            return null;
        }

        $port = (int) $port;
        if (0 > $port || 0xFFFF < $port) {
            throw new \InvalidArgumentException(
                sprintf('Invalid port: %d. Must be between 0 and 65535', $port)
            );
        }

        return $port;
    }

    private static function getFilteredQueryString(UriInterface $uri, array $keys): array
    {
        $current = $uri->getQuery();

        if ($current === '') {
            return [];
        }

        $decodedKeys = array_map(function ($k): string {
            return rawurldecode((string) $k);
        }, $keys);

        return array_filter(explode('&', $current), function ($part) use ($decodedKeys) {
            return !in_array(rawurldecode(explode('=', $part)[0]), $decodedKeys, true);
        });
    }

    private static function generateQueryString(string $key, ?string $value): string
    {
        $queryString = strtr($key, self::QUERY_SEPARATORS_REPLACEMENT);

        if ($value !== null) {
            $queryString .= '='.strtr($value, self::QUERY_SEPARATORS_REPLACEMENT);
        }

        return $queryString;
    }

    private function removeDefaultPort(): void
    {
        if ($this->port !== null && self::isDefaultPort($this)) {
            $this->port = null;
        }
    }

    private function filterPath($path): string
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string');
        }

        return preg_replace_callback(
            '/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $path
        );
    }

    private function filterQueryAndFragment($str): string
    {
        if (!is_string($str)) {
            throw new \InvalidArgumentException('Query and fragment must be a string');
        }

        return preg_replace_callback(
            '/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/',
            [$this, 'rawurlencodeMatchZero'],
            $str
        );
    }

    private function rawurlencodeMatchZero(array $match): string
    {
        return rawurlencode($match[0]);
    }

    private function validateState(): void
    {
        if ($this->host === '' && ($this->scheme === 'http' || $this->scheme === 'https')) {
            $this->host = self::HTTP_DEFAULT_HOST;
        }

        if ($this->getAuthority() === '') {
            if (0 === strpos($this->path, '//')) {
                throw new MalformedUriException('The path of a URI without an authority must not start with two slashes "//"');
            }
            if ($this->scheme === '' && false !== strpos(explode('/', $this->path, 2)[0], ':')) {
                throw new MalformedUriException('A relative URI must not have a path beginning with a segment containing a colon');
            }
        }
    }
}
