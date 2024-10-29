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
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    'months_short' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    'weekdays' => ['Sambata', 'Sanyo', 'Maakisanyo', 'Roowe', 'Hamuse', 'Arbe', 'Qidaame'],
    'weekdays_short' => ['Sam', 'San', 'Mak', 'Row', 'Ham', 'Arb', 'Qid'],
    'weekdays_min' => ['Sam', 'San', 'Mak', 'Row', 'Ham', 'Arb', 'Qid'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['soodo', 'hawwaro'],
]);
