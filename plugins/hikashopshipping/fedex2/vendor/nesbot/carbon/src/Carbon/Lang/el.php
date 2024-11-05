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
    'year' => ':count χρόνος|:count χρόνια',
    'a_year' => 'ένας χρόνος|:count χρόνια',
    'y' => ':count χρ.',
    'month' => ':count μήνας|:count μήνες',
    'a_month' => 'ένας μήνας|:count μήνες',
    'm' => ':count μήν.',
    'week' => ':count εβδομάδα|:count εβδομάδες',
    'a_week' => 'μια εβδομάδα|:count εβδομάδες',
    'w' => ':count εβδ.',
    'day' => ':count μέρα|:count μέρες',
    'a_day' => 'μία μέρα|:count μέρες',
    'd' => ':count μέρ.',
    'hour' => ':count ώρα|:count ώρες',
    'a_hour' => 'μία ώρα|:count ώρες',
    'h' => ':count ώρα|:count ώρες',
    'minute' => ':count λεπτό|:count λεπτά',
    'a_minute' => 'ένα λεπτό|:count λεπτά',
    'min' => ':count λεπ.',
    'second' => ':count δευτερόλεπτο|:count δευτερόλεπτα',
    'a_second' => 'λίγα δευτερόλεπτα|:count δευτερόλεπτα',
    's' => ':count δευ.',
    'ago' => 'πριν :time',
    'from_now' => 'σε :time',
    'after' => ':time μετά',
    'before' => ':time πριν',
    'diff_now' => 'τώρα',
    'diff_today' => 'Σήμερα',
    'diff_today_regexp' => 'Σήμερα(?:\\s+{})?',
    'diff_yesterday' => 'χθες',
    'diff_yesterday_regexp' => 'Χθες(?:\\s+{})?',
    'diff_tomorrow' => 'αύριο',
    'diff_tomorrow_regexp' => 'Αύριο(?:\\s+{})?',
    'formats' => [
        'LT' => 'h:mm A',
        'LTS' => 'h:mm:ss A',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY h:mm A',
        'LLLL' => 'dddd, D MMMM YYYY h:mm A',
    ],
    'calendar' => [
        'sameDay' => '[Σήμερα {}] LT',
        'nextDay' => '[Αύριο {}] LT',
        'nextWeek' => 'dddd [{}] LT',
        'lastDay' => '[Χθες {}] LT',
        'lastWeek' => function (CarbonInterface $current) {
            switch ($current->dayOfWeek) {
                case 6:
                    return '[το προηγούμενο] dddd [{}] LT';
                default:
                    return '[την προηγούμενη] dddd [{}] LT';
            }
        },
        'sameElse' => 'L',
    ],
    'ordinal' => ':numberη',
    'meridiem' => ['ΠΜ', 'ΜΜ', 'πμ', 'μμ'],
    'months' => ['Ιανουαρίου', 'Φεβρουαρίου', 'Μαρτίου', 'Απριλίου', 'Μαΐου', 'Ιουνίου', 'Ιουλίου', 'Αυγούστου', 'Σεπτεμβρίου', 'Οκτωβρίου', 'Νοεμβρίου', 'Δεκεμβρίου'],
    'months_standalone' => ['Ιανουάριος', 'Φεβρουάριος', 'Μάρτιος', 'Απρίλιος', 'Μάιος', 'Ιούνιος', 'Ιούλιος', 'Αύγουστος', 'Σεπτέμβριος', 'Οκτώβριος', 'Νοέμβριος', 'Δεκέμβριος'],
    'months_regexp' => '/(D[oD]?[\s,]+MMMM|L{2,4}|l{2,4})/',
    'months_short' => ['Ιαν', 'Φεβ', 'Μαρ', 'Απρ', 'Μαϊ', 'Ιουν', 'Ιουλ', 'Αυγ', 'Σεπ', 'Οκτ', 'Νοε', 'Δεκ'],
    'weekdays' => ['Κυριακή', 'Δευτέρα', 'Τρίτη', 'Τετάρτη', 'Πέμπτη', 'Παρασκευή', 'Σάββατο'],
    'weekdays_short' => ['Κυρ', 'Δευ', 'Τρι', 'Τετ', 'Πεμ', 'Παρ', 'Σαβ'],
    'weekdays_min' => ['Κυ', 'Δε', 'Τρ', 'Τε', 'Πε', 'Πα', 'Σα'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
    'list' => [', ', ' και '],
];
