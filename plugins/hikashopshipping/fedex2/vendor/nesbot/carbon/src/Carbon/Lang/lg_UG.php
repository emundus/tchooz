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
    'months' => ['Janwaliyo', 'Febwaliyo', 'Marisi', 'Apuli', 'Maayi', 'Juuni', 'Julaayi', 'Agusito', 'Sebuttemba', 'Okitobba', 'Novemba', 'Desemba'],
    'months_short' => ['Jan', 'Feb', 'Mar', 'Apu', 'Maa', 'Juu', 'Jul', 'Agu', 'Seb', 'Oki', 'Nov', 'Des'],
    'weekdays' => ['Sabiiti', 'Balaza', 'Lwakubiri', 'Lwakusatu', 'Lwakuna', 'Lwakutaano', 'Lwamukaaga'],
    'weekdays_short' => ['Sab', 'Bal', 'Lw2', 'Lw3', 'Lw4', 'Lw5', 'Lw6'],
    'weekdays_min' => ['Sab', 'Bal', 'Lw2', 'Lw3', 'Lw4', 'Lw5', 'Lw6'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,

    'month' => ':count njuba', // less reliable
    'm' => ':count njuba', // less reliable
    'a_month' => ':count njuba', // less reliable

    'year' => ':count mwaaka',
    'y' => ':count mwaaka',
    'a_year' => ':count mwaaka',

    'week' => ':count sabbiiti',
    'w' => ':count sabbiiti',
    'a_week' => ':count sabbiiti',

    'day' => ':count lunaku',
    'd' => ':count lunaku',
    'a_day' => ':count lunaku',

    'hour' => 'saawa :count',
    'h' => 'saawa :count',
    'a_hour' => 'saawa :count',

    'minute' => 'ddakiika :count',
    'min' => 'ddakiika :count',
    'a_minute' => 'ddakiika :count',

    'second' => ':count kyʼokubiri',
    's' => ':count kyʼokubiri',
    'a_second' => ':count kyʼokubiri',
]);
