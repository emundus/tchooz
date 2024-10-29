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


return array_replace_recursive(require __DIR__.'/fy.php', [
    'formats' => [
        'L' => 'DD-MM-YY',
    ],
    'months' => ['Jannewaris', 'Febrewaris', 'Maart', 'April', 'Maaie', 'Juny', 'July', 'Augustus', 'Septimber', 'Oktober', 'Novimber', 'Desimber'],
    'months_short' => ['Jan', 'Feb', 'Mrt', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'],
    'weekdays' => ['Snein', 'Moandei', 'Tiisdei', 'Woansdei', 'Tongersdei', 'Freed', 'Sneon'],
    'weekdays_short' => ['Sn', 'Mo', 'Ti', 'Wo', 'To', 'Fr', 'Sn'],
    'weekdays_min' => ['Sn', 'Mo', 'Ti', 'Wo', 'To', 'Fr', 'Sn'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
]);
