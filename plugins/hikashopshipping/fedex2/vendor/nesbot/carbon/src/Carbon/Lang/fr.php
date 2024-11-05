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
    'year' => ':count an|:count ans',
    'a_year' => 'un an|:count ans',
    'y' => ':count an|:count ans',
    'month' => ':count mois|:count mois',
    'a_month' => 'un mois|:count mois',
    'm' => ':count mois',
    'week' => ':count semaine|:count semaines',
    'a_week' => 'une semaine|:count semaines',
    'w' => ':count sem.',
    'day' => ':count jour|:count jours',
    'a_day' => 'un jour|:count jours',
    'd' => ':count j',
    'hour' => ':count heure|:count heures',
    'a_hour' => 'une heure|:count heures',
    'h' => ':count h',
    'minute' => ':count minute|:count minutes',
    'a_minute' => 'une minute|:count minutes',
    'min' => ':count min',
    'second' => ':count seconde|:count secondes',
    'a_second' => 'quelques secondes|:count secondes',
    's' => ':count s',
    'millisecond' => ':count milliseconde|:count millisecondes',
    'a_millisecond' => 'une milliseconde|:count millisecondes',
    'ms' => ':countms',
    'microsecond' => ':count microseconde|:count microsecondes',
    'a_microsecond' => 'une microseconde|:count microsecondes',
    'µs' => ':countµs',
    'ago' => 'il y a :time',
    'from_now' => 'dans :time',
    'after' => ':time après',
    'before' => ':time avant',
    'diff_now' => "à l'instant",
    'diff_today' => "aujourd'hui",
    'diff_today_regexp' => "aujourd'hui(?:\s+à)?",
    'diff_yesterday' => 'hier',
    'diff_yesterday_regexp' => 'hier(?:\s+à)?',
    'diff_tomorrow' => 'demain',
    'diff_tomorrow_regexp' => 'demain(?:\s+à)?',
    'diff_before_yesterday' => 'avant-hier',
    'diff_after_tomorrow' => 'après-demain',
    'period_recurrences' => ':count fois',
    'period_interval' => 'tous les :interval',
    'period_start_date' => 'de :date',
    'period_end_date' => 'à :date',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],
    'calendar' => [
        'sameDay' => '[Aujourd’hui à] LT',
        'nextDay' => '[Demain à] LT',
        'nextWeek' => 'dddd [à] LT',
        'lastDay' => '[Hier à] LT',
        'lastWeek' => 'dddd [dernier à] LT',
        'sameElse' => 'L',
    ],
    'months' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
    'months_short' => ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
    'weekdays' => ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
    'weekdays_short' => ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'],
    'weekdays_min' => ['di', 'lu', 'ma', 'me', 'je', 've', 'sa'],
    'ordinal' => function ($number, $period) {
        switch ($period) {
            case 'D':
                return $number.($number === 1 ? 'er' : '');

            default:
            case 'M':
            case 'Q':
            case 'DDD':
            case 'd':
                return $number.($number === 1 ? 'er' : 'e');

            case 'w':
            case 'W':
                return $number.($number === 1 ? 're' : 'e');
        }
    },
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
    'list' => [', ', ' et '],
    'ordinal_words' => [
        'of' => 'de',
        'first' => 'premier',
        'second' => 'deuxième',
        'third' => 'troisième',
        'fourth' => 'quatrième',
        'fifth' => 'cinquième',
        'last' => 'dernier',
    ],
];
