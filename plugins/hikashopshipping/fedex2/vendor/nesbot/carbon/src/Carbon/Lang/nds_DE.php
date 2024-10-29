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
    'months' => ['Jannuaar', 'Feberwaar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
    'months_short' => ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
    'weekdays' => ['Sünndag', 'Maandag', 'Dingsdag', 'Middeweek', 'Dunnersdag', 'Freedag', 'Sünnavend'],
    'weekdays_short' => ['Sdag', 'Maan', 'Ding', 'Midd', 'Dunn', 'Free', 'Svd.'],
    'weekdays_min' => ['Sd', 'Ma', 'Di', 'Mi', 'Du', 'Fr', 'Sa'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,

    'year' => ':count Johr',
    'y' => ':countJ',
    'a_year' => '{1}een Johr|:count Johr',

    'month' => ':count Maand',
    'm' => ':countM',
    'a_month' => '{1}een Maand|:count Maand',

    'week' => ':count Week|:count Weken',
    'w' => ':countW',
    'a_week' => '{1}een Week|:count Week|:count Weken',

    'day' => ':count Dag|:count Daag',
    'd' => ':countD',
    'a_day' => '{1}een Dag|:count Dag|:count Daag',

    'hour' => ':count Stünn|:count Stünnen',
    'h' => ':countSt',
    'a_hour' => '{1}een Stünn|:count Stünn|:count Stünnen',

    'minute' => ':count Minuut|:count Minuten',
    'min' => ':countm',
    'a_minute' => '{1}een Minuut|:count Minuut|:count Minuten',

    'second' => ':count Sekunn|:count Sekunnen',
    's' => ':counts',
    'a_second' => 'en poor Sekunnen|:count Sekunn|:count Sekunnen',

    'ago' => 'vör :time',
    'from_now' => 'in :time',
    'before' => ':time vörher',
    'after' => ':time later',
]);
