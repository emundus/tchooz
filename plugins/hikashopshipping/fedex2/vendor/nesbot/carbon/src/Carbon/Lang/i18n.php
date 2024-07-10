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
        'L' => 'YYYY-MM-DD',
    ],
    'months' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
    'months_short' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
    'weekdays' => ['1', '2', '3', '4', '5', '6', '7'],
    'weekdays_short' => ['1', '2', '3', '4', '5', '6', '7'],
    'weekdays_min' => ['1', '2', '3', '4', '5', '6', '7'],
    'first_day_of_week' => 0,
    'day_of_first_week_of_year' => 4,
]);
