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
        'L' => 'DD.MM.YYYY',
    ],
    'months' => ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'Mayıs', 'İyun', 'İyul', 'Avgust', 'Sentâbr', 'Oktâbr', 'Noyabr', 'Dekabr'],
    'months_short' => ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'İyn', 'İyl', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek'],
    'weekdays' => ['Bazar', 'Bazarertesi', 'Salı', 'Çarşembe', 'Cumaaqşamı', 'Cuma', 'Cumaertesi'],
    'weekdays_short' => ['Baz', 'Ber', 'Sal', 'Çar', 'Caq', 'Cum', 'Cer'],
    'weekdays_min' => ['Baz', 'Ber', 'Sal', 'Çar', 'Caq', 'Cum', 'Cer'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['ÜE', 'ÜS'],

    'year' => ':count yıl',
    'y' => ':count yıl',
    'a_year' => ':count yıl',

    'month' => ':count ay',
    'm' => ':count ay',
    'a_month' => ':count ay',

    'week' => ':count afta',
    'w' => ':count afta',
    'a_week' => ':count afta',

    'day' => ':count kün',
    'd' => ':count kün',
    'a_day' => ':count kün',

    'hour' => ':count saat',
    'h' => ':count saat',
    'a_hour' => ':count saat',

    'minute' => ':count daqqa',
    'min' => ':count daqqa',
    'a_minute' => ':count daqqa',

    'second' => ':count ekinci',
    's' => ':count ekinci',
    'a_second' => ':count ekinci',
]);
