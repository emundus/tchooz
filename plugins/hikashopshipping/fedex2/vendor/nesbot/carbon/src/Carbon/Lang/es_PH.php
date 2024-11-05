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
    'first_day_of_week' => 0,
    'formats' => [
        'LT' => 'h:mm a',
        'LTS' => 'h:mm:ss a',
        'L' => 'D/M/yy',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D [de] MMMM [de] YYYY h:mm a',
        'LLLL' => 'dddd, D [de] MMMM [de] YYYY h:mm a',
    ],
]);
