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
    'formats' => [
        'L' => 'DD/MM/YY',
    ],
    'months' => ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
    'months_short' => ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'],
    'weekdays' => ['teoilhuitl', 'ceilhuitl', 'omeilhuitl', 'yeilhuitl', 'nahuilhuitl', 'macuililhuitl', 'chicuaceilhuitl'],
    'weekdays_short' => ['teo', 'cei', 'ome', 'yei', 'nau', 'mac', 'chi'],
    'weekdays_min' => ['teo', 'cei', 'ome', 'yei', 'nau', 'mac', 'chi'],
    'day_of_first_week_of_year' => 1,

    'month' => ':count metztli', // less reliable
    'm' => ':count metztli', // less reliable
    'a_month' => ':count metztli', // less reliable

    'week' => ':count tonalli', // less reliable
    'w' => ':count tonalli', // less reliable
    'a_week' => ':count tonalli', // less reliable

    'day' => ':count tonatih', // less reliable
    'd' => ':count tonatih', // less reliable
    'a_day' => ':count tonatih', // less reliable

    'minute' => ':count toltecayotl', // less reliable
    'min' => ':count toltecayotl', // less reliable
    'a_minute' => ':count toltecayotl', // less reliable

    'second' => ':count ome', // less reliable
    's' => ':count ome', // less reliable
    'a_second' => ':count ome', // less reliable

    'year' => ':count xihuitl',
    'y' => ':count xihuitl',
    'a_year' => ':count xihuitl',
]);
