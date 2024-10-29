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
    'months' => ['mis Genver', 'mis Hwevrer', 'mis Meurth', 'mis Ebrel', 'mis Me', 'mis Metheven', 'mis Gortheren', 'mis Est', 'mis Gwynngala', 'mis Hedra', 'mis Du', 'mis Kevardhu'],
    'months_short' => ['Gen', 'Hwe', 'Meu', 'Ebr', 'Me', 'Met', 'Gor', 'Est', 'Gwn', 'Hed', 'Du', 'Kev'],
    'weekdays' => ['De Sul', 'De Lun', 'De Merth', 'De Merher', 'De Yow', 'De Gwener', 'De Sadorn'],
    'weekdays_short' => ['Sul', 'Lun', 'Mth', 'Mhr', 'Yow', 'Gwe', 'Sad'],
    'weekdays_min' => ['Sul', 'Lun', 'Mth', 'Mhr', 'Yow', 'Gwe', 'Sad'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,

    'year' => ':count bledhen',
    'y' => ':count bledhen',
    'a_year' => ':count bledhen',

    'month' => ':count mis',
    'm' => ':count mis',
    'a_month' => ':count mis',

    'week' => ':count seythen',
    'w' => ':count seythen',
    'a_week' => ':count seythen',

    'day' => ':count dydh',
    'd' => ':count dydh',
    'a_day' => ':count dydh',

    'hour' => ':count eur',
    'h' => ':count eur',
    'a_hour' => ':count eur',

    'minute' => ':count mynysen',
    'min' => ':count mynysen',
    'a_minute' => ':count mynysen',

    'second' => ':count pryjwyth',
    's' => ':count pryjwyth',
    'a_second' => ':count pryjwyth',
]);
