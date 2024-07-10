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
    'year' => '{1}blwyddyn|]1,Inf[:count flynedd',
    'y' => ':countbl',
    'month' => '{1}mis|]1,Inf[:count mis',
    'm' => ':countmi',
    'week' => ':count wythnos',
    'w' => ':countw',
    'day' => '{1}diwrnod|]1,Inf[:count diwrnod',
    'd' => ':countd',
    'hour' => '{1}awr|]1,Inf[:count awr',
    'h' => ':counth',
    'minute' => '{1}munud|]1,Inf[:count munud',
    'min' => ':countm',
    'second' => '{1}ychydig eiliadau|]1,Inf[:count eiliad',
    's' => ':counts',
    'ago' => ':time yn ôl',
    'from_now' => 'mewn :time',
    'after' => ':time ar ôl',
    'before' => ':time o\'r blaen',
    'diff_now' => 'nawr',
    'diff_today' => 'Heddiw',
    'diff_today_regexp' => 'Heddiw(?:\\s+am)?',
    'diff_yesterday' => 'ddoe',
    'diff_yesterday_regexp' => 'Ddoe(?:\\s+am)?',
    'diff_tomorrow' => 'yfory',
    'diff_tomorrow_regexp' => 'Yfory(?:\\s+am)?',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],
    'calendar' => [
        'sameDay' => '[Heddiw am] LT',
        'nextDay' => '[Yfory am] LT',
        'nextWeek' => 'dddd [am] LT',
        'lastDay' => '[Ddoe am] LT',
        'lastWeek' => 'dddd [diwethaf am] LT',
        'sameElse' => 'L',
    ],
    'ordinal' => function ($number) {
        return $number.(
            $number > 20
                ? (\in_array((int) $number, [40, 50, 60, 80, 100], true) ? 'fed' : 'ain')
                : ([
                    '', 'af', 'il', 'ydd', 'ydd', 'ed', 'ed', 'ed', 'fed', 'fed', 'fed', // 1af to 10fed
                    'eg', 'fed', 'eg', 'eg', 'fed', 'eg', 'eg', 'fed', 'eg', 'fed', // 11eg to 20fed
                ])[$number] ?? ''
        );
    },
    'months' => ['Ionawr', 'Chwefror', 'Mawrth', 'Ebrill', 'Mai', 'Mehefin', 'Gorffennaf', 'Awst', 'Medi', 'Hydref', 'Tachwedd', 'Rhagfyr'],
    'months_short' => ['Ion', 'Chwe', 'Maw', 'Ebr', 'Mai', 'Meh', 'Gor', 'Aws', 'Med', 'Hyd', 'Tach', 'Rhag'],
    'weekdays' => ['Dydd Sul', 'Dydd Llun', 'Dydd Mawrth', 'Dydd Mercher', 'Dydd Iau', 'Dydd Gwener', 'Dydd Sadwrn'],
    'weekdays_short' => ['Sul', 'Llun', 'Maw', 'Mer', 'Iau', 'Gwe', 'Sad'],
    'weekdays_min' => ['Su', 'Ll', 'Ma', 'Me', 'Ia', 'Gw', 'Sa'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
    'list' => [', ', ' a '],
    'meridiem' => ['yb', 'yh'],
];
