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
    'year' => ':count aasta|:count aastat',
    'y' => ':count a',
    'month' => ':count kuu|:count kuud',
    'm' => ':count k',
    'week' => ':count nädal|:count nädalat',
    'w' => ':count näd',
    'day' => ':count päev|:count päeva',
    'd' => ':count p',
    'hour' => ':count tund|:count tundi',
    'h' => ':count t',
    'minute' => ':count minut|:count minutit',
    'min' => ':count min',
    'second' => ':count sekund|:count sekundit',
    's' => ':count s',
    'ago' => ':time tagasi',
    'from_now' => ':time pärast',
    'after' => ':time pärast',
    'before' => ':time enne',
    'year_from_now' => ':count aasta',
    'month_from_now' => ':count kuu',
    'week_from_now' => ':count nädala',
    'day_from_now' => ':count päeva',
    'hour_from_now' => ':count tunni',
    'minute_from_now' => ':count minuti',
    'second_from_now' => ':count sekundi',
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
    'diff_now' => 'nüüd',
    'diff_today' => 'täna',
    'diff_yesterday' => 'eile',
    'diff_tomorrow' => 'homme',
    'diff_before_yesterday' => 'üleeile',
    'diff_after_tomorrow' => 'ülehomme',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD.MM.YYYY',
        'LL' => 'D. MMMM YYYY',
        'LLL' => 'D. MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D. MMMM YYYY HH:mm',
    ],
    'calendar' => [
        'sameDay' => '[täna] LT',
        'nextDay' => '[homme] LT',
        'lastDay' => '[eile] LT',
        'nextWeek' => 'dddd LT',
        'lastWeek' => '[eelmine] dddd LT',
        'sameElse' => 'L',
    ],
    'months' => ['jaanuar', 'veebruar', 'märts', 'aprill', 'mai', 'juuni', 'juuli', 'august', 'september', 'oktoober', 'november', 'detsember'],
    'months_short' => ['jaan', 'veebr', 'märts', 'apr', 'mai', 'juuni', 'juuli', 'aug', 'sept', 'okt', 'nov', 'dets'],
    'weekdays' => ['pühapäev', 'esmaspäev', 'teisipäev', 'kolmapäev', 'neljapäev', 'reede', 'laupäev'],
    'weekdays_short' => ['P', 'E', 'T', 'K', 'N', 'R', 'L'],
    'weekdays_min' => ['P', 'E', 'T', 'K', 'N', 'R', 'L'],
    'list' => [', ', ' ja '],
    'meridiem' => ['enne lõunat', 'pärast lõunat'],
];
