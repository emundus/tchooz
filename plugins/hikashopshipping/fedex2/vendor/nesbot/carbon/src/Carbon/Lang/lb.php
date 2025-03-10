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



use Carbon\CarbonInterface;

return [
    'year' => ':count Joer',
    'y' => ':countJ',
    'month' => ':count Mount|:count Méint',
    'm' => ':countMo',
    'week' => ':count Woch|:count Wochen',
    'w' => ':countWo|:countWo',
    'day' => ':count Dag|:count Deeg',
    'd' => ':countD',
    'hour' => ':count Stonn|:count Stonnen',
    'h' => ':countSto',
    'minute' => ':count Minutt|:count Minutten',
    'min' => ':countM',
    'second' => ':count Sekonn|:count Sekonnen',
    's' => ':countSek',

    'ago' => 'virun :time',
    'from_now' => 'an :time',
    'before' => ':time virdrun',
    'after' => ':time duerno',

    'diff_today' => 'Haut',
    'diff_yesterday' => 'Gëschter',
    'diff_yesterday_regexp' => 'Gëschter(?:\\s+um)?',
    'diff_tomorrow' => 'Muer',
    'diff_tomorrow_regexp' => 'Muer(?:\\s+um)?',
    'diff_today_regexp' => 'Haut(?:\\s+um)?',
    'formats' => [
        'LT' => 'H:mm [Auer]',
        'LTS' => 'H:mm:ss [Auer]',
        'L' => 'DD.MM.YYYY',
        'LL' => 'D. MMMM YYYY',
        'LLL' => 'D. MMMM YYYY H:mm [Auer]',
        'LLLL' => 'dddd, D. MMMM YYYY H:mm [Auer]',
    ],

    'calendar' => [
        'sameDay' => '[Haut um] LT',
        'nextDay' => '[Muer um] LT',
        'nextWeek' => 'dddd [um] LT',
        'lastDay' => '[Gëschter um] LT',
        'lastWeek' => function (CarbonInterface $date) {
            switch ($date->dayOfWeek) {
                case 2:
                case 4:
                    return '[Leschten] dddd [um] LT';
                default:
                    return '[Leschte] dddd [um] LT';
            }
        },
        'sameElse' => 'L',
    ],

    'months' => ['Januar', 'Februar', 'Mäerz', 'Abrëll', 'Mee', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
    'months_short' => ['Jan.', 'Febr.', 'Mrz.', 'Abr.', 'Mee', 'Jun.', 'Jul.', 'Aug.', 'Sept.', 'Okt.', 'Nov.', 'Dez.'],
    'weekdays' => ['Sonndeg', 'Méindeg', 'Dënschdeg', 'Mëttwoch', 'Donneschdeg', 'Freideg', 'Samschdeg'],
    'weekdays_short' => ['So.', 'Mé.', 'Dë.', 'Më.', 'Do.', 'Fr.', 'Sa.'],
    'weekdays_min' => ['So', 'Mé', 'Dë', 'Më', 'Do', 'Fr', 'Sa'],
    'ordinal' => ':number.',
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
    'list' => [', ', ' an '],
    'meridiem' => ['moies', 'mëttes'],
    'weekdays_short_standalone' => ['Son', 'Méi', 'Dën', 'Mët', 'Don', 'Fre', 'Sam'],
    'months_short_standalone' => ['Jan', 'Feb', 'Mäe', 'Abr', 'Mee', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
];
