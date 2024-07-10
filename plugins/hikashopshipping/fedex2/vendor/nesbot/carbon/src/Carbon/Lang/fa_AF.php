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


return array_replace_recursive(require __DIR__.'/fa.php', [
    'meridiem' => ['ق', 'ب'],
    'weekend' => [4, 5],
    'formats' => [
        'L' => 'OY/OM/OD',
        'LL' => 'OD MMM OY',
        'LLL' => 'OD MMMM OY،‏ H:mm',
        'LLLL' => 'dddd OD MMMM OY،‏ H:mm',
    ],
]);
