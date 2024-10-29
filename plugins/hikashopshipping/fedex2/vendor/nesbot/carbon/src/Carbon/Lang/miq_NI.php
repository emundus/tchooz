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
    'months' => ['siakwa kati', 'kuswa kati', 'kakamuk kati', 'lî wainhka kati', 'lih mairin kati', 'lî kati', 'pastara kati', 'sikla kati', 'wîs kati', 'waupasa kati', 'yahbra kati', 'trisu kati'],
    'months_short' => ['siakwa kati', 'kuswa kati', 'kakamuk kati', 'lî wainhka kati', 'lih mairin kati', 'lî kati', 'pastara kati', 'sikla kati', 'wîs kati', 'waupasa kati', 'yahbra kati', 'trisu kati'],
    'weekdays' => ['sandi', 'mundi', 'tiusdi', 'wensde', 'tausde', 'praidi', 'satadi'],
    'weekdays_short' => ['san', 'mun', 'tius', 'wens', 'taus', 'prai', 'sat'],
    'weekdays_min' => ['san', 'mun', 'tius', 'wens', 'taus', 'prai', 'sat'],
    'first_day_of_week' => 0,
    'day_of_first_week_of_year' => 7,
    'meridiem' => ['VM', 'NM'],

    'month' => ':count kati', // less reliable
    'm' => ':count kati', // less reliable
    'a_month' => ':count kati', // less reliable
]);
