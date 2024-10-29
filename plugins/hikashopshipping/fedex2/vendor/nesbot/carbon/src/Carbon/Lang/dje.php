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
    'meridiem' => ['Subbaahi', 'Zaarikay b'],
    'weekdays' => ['Alhadi', 'Atinni', 'Atalaata', 'Alarba', 'Alhamisi', 'Alzuma', 'Asibti'],
    'weekdays_short' => ['Alh', 'Ati', 'Ata', 'Ala', 'Alm', 'Alz', 'Asi'],
    'weekdays_min' => ['Alh', 'Ati', 'Ata', 'Ala', 'Alm', 'Alz', 'Asi'],
    'months' => ['Žanwiye', 'Feewiriye', 'Marsi', 'Awiril', 'Me', 'Žuweŋ', 'Žuyye', 'Ut', 'Sektanbur', 'Oktoobur', 'Noowanbur', 'Deesanbur'],
    'months_short' => ['Žan', 'Fee', 'Mar', 'Awi', 'Me', 'Žuw', 'Žuy', 'Ut', 'Sek', 'Okt', 'Noo', 'Dee'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM, YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],

    'year' => ':count hari', // less reliable
    'y' => ':count hari', // less reliable
    'a_year' => ':count hari', // less reliable

    'week' => ':count alzuma', // less reliable
    'w' => ':count alzuma', // less reliable
    'a_week' => ':count alzuma', // less reliable

    'second' => ':count atinni', // less reliable
    's' => ':count atinni', // less reliable
    'a_second' => ':count atinni', // less reliable
]);
