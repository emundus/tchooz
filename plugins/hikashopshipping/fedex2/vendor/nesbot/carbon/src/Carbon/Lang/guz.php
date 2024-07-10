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
    'meridiem' => ['Ma', 'Mo'],
    'weekdays' => ['Chumapiri', 'Chumatato', 'Chumaine', 'Chumatano', 'Aramisi', 'Ichuma', 'Esabato'],
    'weekdays_short' => ['Cpr', 'Ctt', 'Cmn', 'Cmt', 'Ars', 'Icm', 'Est'],
    'weekdays_min' => ['Cpr', 'Ctt', 'Cmn', 'Cmt', 'Ars', 'Icm', 'Est'],
    'months' => ['Chanuari', 'Feburari', 'Machi', 'Apiriri', 'Mei', 'Juni', 'Chulai', 'Agosti', 'Septemba', 'Okitoba', 'Nobemba', 'Disemba'],
    'months_short' => ['Can', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Cul', 'Agt', 'Sep', 'Okt', 'Nob', 'Dis'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],

    'month' => ':count omotunyi', // less reliable
    'm' => ':count omotunyi', // less reliable
    'a_month' => ':count omotunyi', // less reliable

    'week' => ':count isano naibere', // less reliable
    'w' => ':count isano naibere', // less reliable
    'a_week' => ':count isano naibere', // less reliable

    'second' => ':count ibere', // less reliable
    's' => ':count ibere', // less reliable
    'a_second' => ':count ibere', // less reliable

    'year' => ':count omwaka',
    'y' => ':count omwaka',
    'a_year' => ':count omwaka',

    'day' => ':count rituko',
    'd' => ':count rituko',
    'a_day' => ':count rituko',
]);
