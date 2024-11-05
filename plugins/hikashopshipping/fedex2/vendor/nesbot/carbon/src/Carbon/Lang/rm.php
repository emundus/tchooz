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
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD.MM.YYYY',
        'LL' => 'Do MMMM YYYY',
        'LLL' => 'Do MMMM, HH:mm [Uhr]',
        'LLLL' => 'dddd, Do MMMM YYYY, HH:mm [Uhr]',
    ],
    'year' => ':count onn|:count onns',
    'month' => ':count mais',
    'week' => ':count emna|:count emnas',
    'day' => ':count di|:count dis',
    'hour' => ':count oura|:count ouras',
    'minute' => ':count minuta|:count minutas',
    'second' => ':count secunda|:count secundas',
    'weekdays' => ['dumengia', 'glindesdi', 'mardi', 'mesemna', 'gievgia', 'venderdi', 'sonda'],
    'weekdays_short' => ['du', 'gli', 'ma', 'me', 'gie', 've', 'so'],
    'weekdays_min' => ['du', 'gli', 'ma', 'me', 'gie', 've', 'so'],
    'months' => ['schaner', 'favrer', 'mars', 'avrigl', 'matg', 'zercladur', 'fanadur', 'avust', 'settember', 'october', 'november', 'december'],
    'months_short' => ['schan', 'favr', 'mars', 'avr', 'matg', 'zercl', 'fan', 'avust', 'sett', 'oct', 'nov', 'dec'],
    'meridiem' => ['avantmezdi', 'suentermezdi'],
    'list' => [', ', ' e '],
    'first_day_of_week' => 1,
]);
