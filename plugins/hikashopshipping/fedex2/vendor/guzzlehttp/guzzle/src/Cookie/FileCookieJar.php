<?php

namespace GuzzleHttp\Cookie;

use GuzzleHttp\Utils;

class FileCookieJar extends CookieJar
{
    private $filename;

    private $storeSessionCookies;

    public function __construct(string $cookieFile, bool $storeSessionCookies = false)
    {
        parent::__construct();
        $this->filename = $cookieFile;
        $this->storeSessionCookies = $storeSessionCookies;

        if (\file_exists($cookieFile)) {
            $this->load($cookieFile);
        }
    }

    public function __destruct()
    {
        $this->save($this->filename);
    }

    public function save(string $filename): void
    {
        $json = [];

        foreach ($this as $cookie) {
            if (CookieJar::shouldPersist($cookie, $this->storeSessionCookies)) {
                $json[] = $cookie->toArray();
            }
        }

        $jsonStr = Utils::jsonEncode($json);
        if (false === \file_put_contents($filename, $jsonStr, \LOCK_EX)) {
            throw new \RuntimeException("Unable to save file {$filename}");
        }
    }

    public function load(string $filename): void
    {
        $json = \file_get_contents($filename);
        if (false === $json) {
            throw new \RuntimeException("Unable to load file {$filename}");
        }
        if ($json === '') {
            return;
        }

        $data = Utils::jsonDecode($json, true);
        if (\is_array($data)) {
            foreach ($data as $cookie) {
                $this->setCookie(new SetCookie($cookie));
            }
        } elseif (\is_scalar($data) && !empty($data)) {
            throw new \RuntimeException("Invalid cookie file: {$filename}");
        }
    }
}
