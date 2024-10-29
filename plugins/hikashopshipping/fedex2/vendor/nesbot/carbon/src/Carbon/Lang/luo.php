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
    'meridiem' => ['OD', 'OT'],
    'weekdays' => ['Jumapil', 'Wuok Tich', 'Tich Ariyo', 'Tich Adek', 'Tich Ang’wen', 'Tich Abich', 'Ngeso'],
    'weekdays_short' => ['JMP', 'WUT', 'TAR', 'TAD', 'TAN', 'TAB', 'NGS'],
    'weekdays_min' => ['JMP', 'WUT', 'TAR', 'TAD', 'TAN', 'TAB', 'NGS'],
    'months' => ['Dwe mar Achiel', 'Dwe mar Ariyo', 'Dwe mar Adek', 'Dwe mar Ang’wen', 'Dwe mar Abich', 'Dwe mar Auchiel', 'Dwe mar Abiriyo', 'Dwe mar Aboro', 'Dwe mar Ochiko', 'Dwe mar Apar', 'Dwe mar gi achiel', 'Dwe mar Apar gi ariyo'],
    'months_short' => ['DAC', 'DAR', 'DAD', 'DAN', 'DAH', 'DAU', 'DAO', 'DAB', 'DOC', 'DAP', 'DGI', 'DAG'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],

    'year' => 'higni :count',
    'y' => 'higni :count',
    'a_year' => ':higni :count',

    'month' => 'dweche :count',
    'm' => 'dweche :count',
    'a_month' => 'dweche :count',

    'week' => 'jumbe :count',
    'w' => 'jumbe :count',
    'a_week' => 'jumbe :count',

    'day' => 'ndalo :count',
    'd' => 'ndalo :count',
    'a_day' => 'ndalo :count',

    'hour' => 'seche :count',
    'h' => 'seche :count',
    'a_hour' => 'seche :count',

    'minute' => 'dakika :count',
    'min' => 'dakika :count',
    'a_minute' => 'dakika :count',

    'second' => 'nus dakika :count',
    's' => 'nus dakika :count',
    'a_second' => 'nus dakika :count',
]);
