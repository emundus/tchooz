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


namespace Symfony\Component\Translation\Extractor;

trigger_deprecation('symfony/translation', '6.2', '"%s" is deprecated.', PhpStringTokenParser::class);


class PhpStringTokenParser
{
    protected static $replacements = [
        '\\' => '\\',
        '$' => '$',
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        'f' => "\f",
        'v' => "\v",
        'e' => "\x1B",
    ];

    public static function parse(string $str): string
    {
        $bLength = 0;
        if ('b' === $str[0]) {
            $bLength = 1;
        }

        if ('\'' === $str[$bLength]) {
            return str_replace(
                ['\\\\', '\\\''],
                ['\\', '\''],
                substr($str, $bLength + 1, -1)
            );
        } else {
            return self::parseEscapeSequences(substr($str, $bLength + 1, -1), '"');
        }
    }

    public static function parseEscapeSequences(string $str, ?string $quote = null): string
    {
        if (null !== $quote) {
            $str = str_replace('\\'.$quote, $quote, $str);
        }

        return preg_replace_callback(
            '~\\\\([\\\\$nrtfve]|[xX][0-9a-fA-F]{1,2}|[0-7]{1,3})~',
            [__CLASS__, 'parseCallback'],
            $str
        );
    }

    private static function parseCallback(array $matches): string
    {
        $str = $matches[1];

        if (isset(self::$replacements[$str])) {
            return self::$replacements[$str];
        } elseif ('x' === $str[0] || 'X' === $str[0]) {
            return \chr(hexdec($str));
        } else {
            return \chr(octdec($str));
        }
    }

    public static function parseDocString(string $startToken, string $str): string
    {
        $str = preg_replace('~(\r\n|\n|\r)$~', '', $str);

        if (str_contains($startToken, '\'')) {
            return $str;
        }

        return self::parseEscapeSequences($str, null);
    }
}
