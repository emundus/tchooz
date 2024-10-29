<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

namespace GuzzleHttp\Handler;

use GuzzleHttp\Utils;

final class HeaderProcessor
{
    public static function parseHeaders(array $headers): array
    {
        if ($headers === []) {
            throw new \RuntimeException('Expected a non-empty array of header data');
        }

        $parts = \explode(' ', \array_shift($headers), 3);
        $version = \explode('/', $parts[0])[1] ?? null;

        if ($version === null) {
            throw new \RuntimeException('HTTP version missing from header data');
        }

        $status = $parts[1] ?? null;

        if ($status === null) {
            throw new \RuntimeException('HTTP status code missing from header data');
        }

        return [$version, (int) $status, $parts[2] ?? null, Utils::headersFromLines($headers)];
    }
}
