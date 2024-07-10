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
        'L' => 'MM/DD/YY',
    ],
    'months' => ['Enero', 'Pebrero', 'Marso', 'Abril', 'Mayo', 'Hunyo', 'Hulyo', 'Agosto', 'Setyembre', 'Oktubre', 'Nobyembre', 'Disyembre'],
    'months_short' => ['Ene', 'Peb', 'Mar', 'Abr', 'May', 'Hun', 'Hul', 'Ago', 'Set', 'Okt', 'Nob', 'Dis'],
    'weekdays' => ['Linggo', 'Lunes', 'Martes', 'Miyerkoles', 'Huwebes', 'Biyernes', 'Sabado'],
    'weekdays_short' => ['Lin', 'Lun', 'Mar', 'Miy', 'Huw', 'Biy', 'Sab'],
    'weekdays_min' => ['Lin', 'Lun', 'Mar', 'Miy', 'Huw', 'Biy', 'Sab'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['N.U.', 'N.H.'],

    'before' => ':time bago',
    'after' => ':time pagkatapos',

    'year' => ':count taon',
    'y' => ':count taon',
    'a_year' => ':count taon',

    'month' => ':count buwan',
    'm' => ':count buwan',
    'a_month' => ':count buwan',

    'week' => ':count linggo',
    'w' => ':count linggo',
    'a_week' => ':count linggo',

    'day' => ':count araw',
    'd' => ':count araw',
    'a_day' => ':count araw',

    'hour' => ':count oras',
    'h' => ':count oras',
    'a_hour' => ':count oras',

    'minute' => ':count minuto',
    'min' => ':count minuto',
    'a_minute' => ':count minuto',

    'second' => ':count segundo',
    's' => ':count segundo',
    'a_second' => ':count segundo',

    'ago' => ':time ang nakalipas',
    'from_now' => 'sa :time',
]);
