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


return array_replace_recursive(require __DIR__.'/sw.php', [
    'formats' => [
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['Januari', 'Februari', 'Machi', 'Aprili', 'Mei', 'Juni', 'Julai', 'Agosti', 'Septemba', 'Oktoba', 'Novemba', 'Desemba'],
    'months_short' => ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des'],
    'weekdays' => ['Jumapili', 'Jumatatu', 'Jumanne', 'Jumatano', 'Alhamisi', 'Ijumaa', 'Jumamosi'],
    'weekdays_short' => ['J2', 'J3', 'J4', 'J5', 'Alh', 'Ij', 'J1'],
    'weekdays_min' => ['J2', 'J3', 'J4', 'J5', 'Alh', 'Ij', 'J1'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['asubuhi', 'alasiri'],
]);
