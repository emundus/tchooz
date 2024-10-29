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
        'L' => 'YYYY.DD.MM',
    ],
    'months' => ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
    'months_short' => ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
    'weekdays' => ['КӀиранан де', 'Оршотан де', 'Шинарин де', 'Кхаарин де', 'Еарин де', 'ПӀераскан де', 'Шот де'],
    'weekdays_short' => ['КӀ', 'Ор', 'Ши', 'Кх', 'Еа', 'ПӀ', 'Шо'],
    'weekdays_min' => ['КӀ', 'Ор', 'Ши', 'Кх', 'Еа', 'ПӀ', 'Шо'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,

    'year' => ':count шо',
    'y' => ':count шо',
    'a_year' => ':count шо',

    'month' => ':count бутт',
    'm' => ':count бутт',
    'a_month' => ':count бутт',

    'week' => ':count кӏира',
    'w' => ':count кӏира',
    'a_week' => ':count кӏира',

    'day' => ':count де',
    'd' => ':count де',
    'a_day' => ':count де',

    'hour' => ':count сахьт',
    'h' => ':count сахьт',
    'a_hour' => ':count сахьт',

    'minute' => ':count минот',
    'min' => ':count минот',
    'a_minute' => ':count минот',

    'second' => ':count секунд',
    's' => ':count секунд',
    'a_second' => ':count секунд',
]);
