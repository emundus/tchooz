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
    'meridiem' => ['RŨ', 'ŨG'],
    'weekdays' => ['Kiumia', 'Muramuko', 'Wairi', 'Wethatu', 'Wena', 'Wetano', 'Jumamosi'],
    'weekdays_short' => ['KIU', 'MRA', 'WAI', 'WET', 'WEN', 'WTN', 'JUM'],
    'weekdays_min' => ['KIU', 'MRA', 'WAI', 'WET', 'WEN', 'WTN', 'JUM'],
    'months' => ['Januarĩ', 'Feburuarĩ', 'Machi', 'Ĩpurũ', 'Mĩĩ', 'Njuni', 'Njuraĩ', 'Agasti', 'Septemba', 'Oktũba', 'Novemba', 'Dicemba'],
    'months_short' => ['JAN', 'FEB', 'MAC', 'ĨPU', 'MĨĨ', 'NJU', 'NJR', 'AGA', 'SPT', 'OKT', 'NOV', 'DEC'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],

    'year' => ':count murume', // less reliable
    'y' => ':count murume', // less reliable
    'a_year' => ':count murume', // less reliable

    'month' => ':count muchaara', // less reliable
    'm' => ':count muchaara', // less reliable
    'a_month' => ':count muchaara', // less reliable

    'minute' => ':count monto', // less reliable
    'min' => ':count monto', // less reliable
    'a_minute' => ':count monto', // less reliable

    'second' => ':count gikeno', // less reliable
    's' => ':count gikeno', // less reliable
    'a_second' => ':count gikeno', // less reliable
]);
