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


return array_replace_recursive(require __DIR__.'/es.php', [
    'diff_before_yesterday' => 'anteayer',
    'formats' => [
        'LT' => 'h:mm A',
        'LTS' => 'h:mm:ss A',
        'L' => 'MM/DD/YYYY',
        'LL' => 'MMMM [de] D [de] YYYY',
        'LLL' => 'MMMM [de] D [de] YYYY h:mm A',
        'LLLL' => 'dddd, MMMM [de] D [de] YYYY h:mm A',
    ],
    'first_day_of_week' => 0,
    'day_of_first_week_of_year' => 1,
]);
