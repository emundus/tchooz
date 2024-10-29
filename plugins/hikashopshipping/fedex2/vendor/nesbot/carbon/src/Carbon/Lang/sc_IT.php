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
        'L' => 'DD. MM. YY',
    ],
    'months' => ['Ghennàrgiu', 'Freàrgiu', 'Martzu', 'Abrile', 'Maju', 'Làmpadas', 'Argiolas//Trìulas', 'Austu', 'Cabudanni', 'Santugaine//Ladàmine', 'Onniasantu//Santandria', 'Nadale//Idas'],
    'months_short' => ['Ghe', 'Fre', 'Mar', 'Abr', 'Maj', 'Làm', 'Arg', 'Aus', 'Cab', 'Lad', 'Onn', 'Nad'],
    'weekdays' => ['Domìnigu', 'Lunis', 'Martis', 'Mèrcuris', 'Giòbia', 'Chenàbura', 'Sàbadu'],
    'weekdays_short' => ['Dom', 'Lun', 'Mar', 'Mèr', 'Giò', 'Che', 'Sàb'],
    'weekdays_min' => ['Dom', 'Lun', 'Mar', 'Mèr', 'Giò', 'Che', 'Sàb'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,

    'minute' => ':count mementu', // less reliable
    'min' => ':count mementu', // less reliable
    'a_minute' => ':count mementu', // less reliable

    'year' => ':count annu',
    'y' => ':count annu',
    'a_year' => ':count annu',

    'month' => ':count mese',
    'm' => ':count mese',
    'a_month' => ':count mese',

    'week' => ':count chida',
    'w' => ':count chida',
    'a_week' => ':count chida',

    'day' => ':count dí',
    'd' => ':count dí',
    'a_day' => ':count dí',

    'hour' => ':count ora',
    'h' => ':count ora',
    'a_hour' => ':count ora',

    'second' => ':count secundu',
    's' => ':count secundu',
    'a_second' => ':count secundu',
]);
