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
    'weekdays' => ['Sonto', 'Mvulo', 'Sibili', 'Sithathu', 'Sine', 'Sihlanu', 'Mgqibelo'],
    'weekdays_short' => ['Son', 'Mvu', 'Sib', 'Sit', 'Sin', 'Sih', 'Mgq'],
    'weekdays_min' => ['Son', 'Mvu', 'Sib', 'Sit', 'Sin', 'Sih', 'Mgq'],
    'months' => ['Zibandlela', 'Nhlolanja', 'Mbimbitho', 'Mabasa', 'Nkwenkwezi', 'Nhlangula', 'Ntulikazi', 'Ncwabakazi', 'Mpandula', 'Mfumfu', 'Lwezi', 'Mpalakazi'],
    'months_short' => ['Zib', 'Nhlo', 'Mbi', 'Mab', 'Nkw', 'Nhla', 'Ntu', 'Ncw', 'Mpan', 'Mfu', 'Lwe', 'Mpal'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],

    'year' => 'okweminyaka engu-:count', // less reliable
    'y' => 'okweminyaka engu-:count', // less reliable
    'a_year' => 'okweminyaka engu-:count', // less reliable

    'month' => 'inyanga ezingu-:count',
    'm' => 'inyanga ezingu-:count',
    'a_month' => 'inyanga ezingu-:count',

    'week' => 'amaviki angu-:count',
    'w' => 'amaviki angu-:count',
    'a_week' => 'amaviki angu-:count',

    'day' => 'kwamalanga angu-:count',
    'd' => 'kwamalanga angu-:count',
    'a_day' => 'kwamalanga angu-:count',

    'hour' => 'amahola angu-:count',
    'h' => 'amahola angu-:count',
    'a_hour' => 'amahola angu-:count',

    'minute' => 'imizuzu engu-:count',
    'min' => 'imizuzu engu-:count',
    'a_minute' => 'imizuzu engu-:count',

    'second' => 'imizuzwana engu-:count',
    's' => 'imizuzwana engu-:count',
    'a_second' => 'imizuzwana engu-:count',
]);
