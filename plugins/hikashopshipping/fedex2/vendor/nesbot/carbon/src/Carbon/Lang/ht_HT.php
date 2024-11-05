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
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['janvye', 'fevriye', 'mas', 'avril', 'me', 'jen', 'jiyè', 'out', 'septanm', 'oktòb', 'novanm', 'desanm'],
    'months_short' => ['jan', 'fev', 'mas', 'avr', 'me', 'jen', 'jiy', 'out', 'sep', 'okt', 'nov', 'des'],
    'weekdays' => ['dimanch', 'lendi', 'madi', 'mèkredi', 'jedi', 'vandredi', 'samdi'],
    'weekdays_short' => ['dim', 'len', 'mad', 'mèk', 'jed', 'van', 'sam'],
    'weekdays_min' => ['dim', 'len', 'mad', 'mèk', 'jed', 'van', 'sam'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,

    'year' => ':count lane',
    'y' => ':count lane',
    'a_year' => ':count lane',

    'month' => 'mwa :count',
    'm' => 'mwa :count',
    'a_month' => 'mwa :count',

    'week' => 'semèn :count',
    'w' => 'semèn :count',
    'a_week' => 'semèn :count',

    'day' => ':count jou',
    'd' => ':count jou',
    'a_day' => ':count jou',

    'hour' => ':count lè',
    'h' => ':count lè',
    'a_hour' => ':count lè',

    'minute' => ':count minit',
    'min' => ':count minit',
    'a_minute' => ':count minit',

    'second' => ':count segonn',
    's' => ':count segonn',
    'a_second' => ':count segonn',
]);
