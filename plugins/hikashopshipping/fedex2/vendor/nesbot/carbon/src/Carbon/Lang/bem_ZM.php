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
        'L' => 'MM/DD/YYYY',
    ],
    'months' => ['Januari', 'Februari', 'Machi', 'Epreo', 'Mei', 'Juni', 'Julai', 'Ogasti', 'Septemba', 'Oktoba', 'Novemba', 'Disemba'],
    'months_short' => ['Jan', 'Feb', 'Mac', 'Epr', 'Mei', 'Jun', 'Jul', 'Oga', 'Sep', 'Okt', 'Nov', 'Dis'],
    'weekdays' => ['Pa Mulungu', 'Palichimo', 'Palichibuli', 'Palichitatu', 'Palichine', 'Palichisano', 'Pachibelushi'],
    'weekdays_short' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    'weekdays_min' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['uluchelo', 'akasuba'],

    'year' => 'myaka :count',
    'y' => 'myaka :count',
    'a_year' => 'myaka :count',

    'month' => 'myeshi :count',
    'm' => 'myeshi :count',
    'a_month' => 'myeshi :count',

    'week' => 'umulungu :count',
    'w' => 'umulungu :count',
    'a_week' => 'umulungu :count',

    'day' => 'inshiku :count',
    'd' => 'inshiku :count',
    'a_day' => 'inshiku :count',

    'hour' => 'awala :count',
    'h' => 'awala :count',
    'a_hour' => 'awala :count',

    'minute' => 'miniti :count',
    'min' => 'miniti :count',
    'a_minute' => 'miniti :count',

    'second' => 'sekondi :count',
    's' => 'sekondi :count',
    'a_second' => 'sekondi :count',
]);
