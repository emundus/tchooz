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


return [
    'year' => ':count bliadhna',
    'a_year' => '{1}bliadhna|:count bliadhna',
    'y' => ':count b.',
    'month' => ':count mìosan',
    'a_month' => '{1}mìos|:count mìosan',
    'm' => ':count ms.',
    'week' => ':count seachdainean',
    'a_week' => '{1}seachdain|:count seachdainean',
    'w' => ':count s.',
    'day' => ':count latha',
    'a_day' => '{1}latha|:count latha',
    'd' => ':count l.',
    'hour' => ':count uairean',
    'a_hour' => '{1}uair|:count uairean',
    'h' => ':count u.',
    'minute' => ':count mionaidean',
    'a_minute' => '{1}mionaid|:count mionaidean',
    'min' => ':count md.',
    'second' => ':count diogan',
    'a_second' => '{1}beagan diogan|:count diogan',
    's' => ':count d.',
    'ago' => 'bho chionn :time',
    'from_now' => 'ann an :time',
    'diff_yesterday' => 'An-dè',
    'diff_yesterday_regexp' => 'An-dè(?:\\s+aig)?',
    'diff_today' => 'An-diugh',
    'diff_today_regexp' => 'An-diugh(?:\\s+aig)?',
    'diff_tomorrow' => 'A-màireach',
    'diff_tomorrow_regexp' => 'A-màireach(?:\\s+aig)?',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],
    'calendar' => [
        'sameDay' => '[An-diugh aig] LT',
        'nextDay' => '[A-màireach aig] LT',
        'nextWeek' => 'dddd [aig] LT',
        'lastDay' => '[An-dè aig] LT',
        'lastWeek' => 'dddd [seo chaidh] [aig] LT',
        'sameElse' => 'L',
    ],
    'ordinal' => function ($number) {
        return $number.($number === 1 ? 'd' : ($number % 10 === 2 ? 'na' : 'mh'));
    },
    'months' => ['Am Faoilleach', 'An Gearran', 'Am Màrt', 'An Giblean', 'An Cèitean', 'An t-Ògmhios', 'An t-Iuchar', 'An Lùnastal', 'An t-Sultain', 'An Dàmhair', 'An t-Samhain', 'An Dùbhlachd'],
    'months_short' => ['Faoi', 'Gear', 'Màrt', 'Gibl', 'Cèit', 'Ògmh', 'Iuch', 'Lùn', 'Sult', 'Dàmh', 'Samh', 'Dùbh'],
    'weekdays' => ['Didòmhnaich', 'Diluain', 'Dimàirt', 'Diciadain', 'Diardaoin', 'Dihaoine', 'Disathairne'],
    'weekdays_short' => ['Did', 'Dil', 'Dim', 'Dic', 'Dia', 'Dih', 'Dis'],
    'weekdays_min' => ['Dò', 'Lu', 'Mà', 'Ci', 'Ar', 'Ha', 'Sa'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
    'list' => [', ', ' agus '],
    'meridiem' => ['m', 'f'],
];
