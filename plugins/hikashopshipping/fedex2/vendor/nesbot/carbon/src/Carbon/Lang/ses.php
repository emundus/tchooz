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
    'meridiem' => ['Adduha', 'Aluula'],
    'weekdays' => ['Alhadi', 'Atinni', 'Atalaata', 'Alarba', 'Alhamiisa', 'Alzuma', 'Asibti'],
    'weekdays_short' => ['Alh', 'Ati', 'Ata', 'Ala', 'Alm', 'Alz', 'Asi'],
    'weekdays_min' => ['Alh', 'Ati', 'Ata', 'Ala', 'Alm', 'Alz', 'Asi'],
    'months' => ['Žanwiye', 'Feewiriye', 'Marsi', 'Awiril', 'Me', 'Žuweŋ', 'Žuyye', 'Ut', 'Sektanbur', 'Oktoobur', 'Noowanbur', 'Deesanbur'],
    'months_short' => ['Žan', 'Fee', 'Mar', 'Awi', 'Me', 'Žuw', 'Žuy', 'Ut', 'Sek', 'Okt', 'Noo', 'Dee'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],

    'month' => ':count alaada', // less reliable
    'm' => ':count alaada', // less reliable
    'a_month' => ':count alaada', // less reliable

    'hour' => ':count ɲaajin', // less reliable
    'h' => ':count ɲaajin', // less reliable
    'a_hour' => ':count ɲaajin', // less reliable

    'minute' => ':count zarbu', // less reliable
    'min' => ':count zarbu', // less reliable
    'a_minute' => ':count zarbu', // less reliable

    'year' => ':count jiiri',
    'y' => ':count jiiri',
    'a_year' => ':count jiiri',

    'week' => ':count jirbiiyye',
    'w' => ':count jirbiiyye',
    'a_week' => ':count jirbiiyye',

    'day' => ':count zaari',
    'd' => ':count zaari',
    'a_day' => ':count zaari',

    'second' => ':count ihinkante',
    's' => ':count ihinkante',
    'a_second' => ':count ihinkante',
]);
