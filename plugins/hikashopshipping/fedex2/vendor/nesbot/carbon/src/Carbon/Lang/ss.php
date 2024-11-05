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
    'year' => '{1}umnyaka|:count iminyaka',
    'month' => '{1}inyanga|:count tinyanga',
    'week' => '{1}:count liviki|:count emaviki',
    'day' => '{1}lilanga|:count emalanga',
    'hour' => '{1}lihora|:count emahora',
    'minute' => '{1}umzuzu|:count emizuzu',
    'second' => '{1}emizuzwana lomcane|:count mzuzwana',
    'ago' => 'wenteka nga :time',
    'from_now' => 'nga :time',
    'diff_yesterday' => 'Itolo',
    'diff_yesterday_regexp' => 'Itolo(?:\\s+nga)?',
    'diff_today' => 'Namuhla',
    'diff_today_regexp' => 'Namuhla(?:\\s+nga)?',
    'diff_tomorrow' => 'Kusasa',
    'diff_tomorrow_regexp' => 'Kusasa(?:\\s+nga)?',
    'formats' => [
        'LT' => 'h:mm A',
        'LTS' => 'h:mm:ss A',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY h:mm A',
        'LLLL' => 'dddd, D MMMM YYYY h:mm A',
    ],
    'calendar' => [
        'sameDay' => '[Namuhla nga] LT',
        'nextDay' => '[Kusasa nga] LT',
        'nextWeek' => 'dddd [nga] LT',
        'lastDay' => '[Itolo nga] LT',
        'lastWeek' => 'dddd [leliphelile] [nga] LT',
        'sameElse' => 'L',
    ],
    'ordinal' => function ($number) {
        $lastDigit = $number % 10;

        return $number.(
            ((int) ($number % 100 / 10) === 1) ? 'e' : (
                ($lastDigit === 1 || $lastDigit === 2) ? 'a' : 'e'
            )
        );
    },
    'meridiem' => function ($hour) {
        if ($hour < 11) {
            return 'ekuseni';
        }
        if ($hour < 15) {
            return 'emini';
        }
        if ($hour < 19) {
            return 'entsambama';
        }

        return 'ebusuku';
    },
    'months' => ['Bhimbidvwane', 'Indlovana', 'Indlov\'lenkhulu', 'Mabasa', 'Inkhwekhweti', 'Inhlaba', 'Kholwane', 'Ingci', 'Inyoni', 'Imphala', 'Lweti', 'Ingongoni'],
    'months_short' => ['Bhi', 'Ina', 'Inu', 'Mab', 'Ink', 'Inh', 'Kho', 'Igc', 'Iny', 'Imp', 'Lwe', 'Igo'],
    'weekdays' => ['Lisontfo', 'Umsombuluko', 'Lesibili', 'Lesitsatfu', 'Lesine', 'Lesihlanu', 'Umgcibelo'],
    'weekdays_short' => ['Lis', 'Umb', 'Lsb', 'Les', 'Lsi', 'Lsh', 'Umg'],
    'weekdays_min' => ['Li', 'Us', 'Lb', 'Lt', 'Ls', 'Lh', 'Ug'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
];
