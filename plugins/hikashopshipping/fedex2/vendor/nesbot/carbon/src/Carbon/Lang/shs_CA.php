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
    'months' => ['Pellkwet̓min', 'Pelctsipwen̓ten', 'Pellsqépts', 'Peslléwten', 'Pell7ell7é7llqten', 'Pelltspéntsk', 'Pelltqwelq̓wél̓t', 'Pellct̓éxel̓cten', 'Pesqelqlélten', 'Pesllwélsten', 'Pellc7ell7é7llcwten̓', 'Pelltetétq̓em'],
    'months_short' => ['Kwe', 'Tsi', 'Sqe', 'Éwt', 'Ell', 'Tsp', 'Tqw', 'Ct̓é', 'Qel', 'Wél', 'U7l', 'Tet'],
    'weekdays' => ['Sxetspesq̓t', 'Spetkesq̓t', 'Selesq̓t', 'Skellesq̓t', 'Smesesq̓t', 'Stselkstesq̓t', 'Stqmekstesq̓t'],
    'weekdays_short' => ['Sxe', 'Spe', 'Sel', 'Ske', 'Sme', 'Sts', 'Stq'],
    'weekdays_min' => ['Sxe', 'Spe', 'Sel', 'Ske', 'Sme', 'Sts', 'Stq'],
    'day_of_first_week_of_year' => 1,

    'year' => ':count sqlélten', // less reliable
    'y' => ':count sqlélten', // less reliable
    'a_year' => ':count sqlélten', // less reliable

    'month' => ':count swewll', // less reliable
    'm' => ':count swewll', // less reliable
    'a_month' => ':count swewll', // less reliable

    'hour' => ':count seqwlút', // less reliable
    'h' => ':count seqwlút', // less reliable
    'a_hour' => ':count seqwlút', // less reliable
]);
