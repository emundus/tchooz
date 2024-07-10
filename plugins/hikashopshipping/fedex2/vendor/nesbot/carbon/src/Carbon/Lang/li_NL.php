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
    'months' => ['jannewarie', 'fibberwarie', 'miert', 'eprèl', 'meij', 'junie', 'julie', 'augustus', 'september', 'oktober', 'november', 'desember'],
    'months_short' => ['jan', 'fib', 'mie', 'epr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
    'weekdays' => ['zóndig', 'maondig', 'daensdig', 'goonsdig', 'dónderdig', 'vriedig', 'zaoterdig'],
    'weekdays_short' => ['zón', 'mao', 'dae', 'goo', 'dón', 'vri', 'zao'],
    'weekdays_min' => ['zón', 'mao', 'dae', 'goo', 'dón', 'vri', 'zao'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,

    'minute' => ':count momênt', // less reliable
    'min' => ':count momênt', // less reliable
    'a_minute' => ':count momênt', // less reliable

    'year' => ':count jaor',
    'y' => ':count jaor',
    'a_year' => ':count jaor',

    'month' => ':count maond',
    'm' => ':count maond',
    'a_month' => ':count maond',

    'week' => ':count waek',
    'w' => ':count waek',
    'a_week' => ':count waek',

    'day' => ':count daag',
    'd' => ':count daag',
    'a_day' => ':count daag',

    'hour' => ':count oer',
    'h' => ':count oer',
    'a_hour' => ':count oer',

    'second' => ':count Secónd',
    's' => ':count Secónd',
    'a_second' => ':count Secónd',
]);
