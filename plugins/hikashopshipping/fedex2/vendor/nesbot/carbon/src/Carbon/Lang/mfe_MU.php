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


return array_replace_recursive(require __DIR__.'/en.php', [
    'formats' => [
        'L' => 'DD/MM/YY',
    ],
    'months' => ['zanvie', 'fevriye', 'mars', 'avril', 'me', 'zin', 'zilye', 'out', 'septam', 'oktob', 'novam', 'desam'],
    'months_short' => ['zan', 'fev', 'mar', 'avr', 'me', 'zin', 'zil', 'out', 'sep', 'okt', 'nov', 'des'],
    'weekdays' => ['dimans', 'lindi', 'mardi', 'merkredi', 'zedi', 'vandredi', 'samdi'],
    'weekdays_short' => ['dim', 'lin', 'mar', 'mer', 'ze', 'van', 'sam'],
    'weekdays_min' => ['dim', 'lin', 'mar', 'mer', 'ze', 'van', 'sam'],

    'year' => ':count banané',
    'y' => ':count banané',
    'a_year' => ':count banané',

    'month' => ':count mwa',
    'm' => ':count mwa',
    'a_month' => ':count mwa',

    'week' => ':count sémenn',
    'w' => ':count sémenn',
    'a_week' => ':count sémenn',

    'day' => ':count zour',
    'd' => ':count zour',
    'a_day' => ':count zour',

    'hour' => ':count -er-tan',
    'h' => ':count -er-tan',
    'a_hour' => ':count -er-tan',

    'minute' => ':count minitt',
    'min' => ':count minitt',
    'a_minute' => ':count minitt',

    'second' => ':count déziém',
    's' => ':count déziém',
    'a_second' => ':count déziém',
]);
