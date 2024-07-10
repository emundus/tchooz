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


return array_replace_recursive(require __DIR__.'/ms.php', [
    'formats' => [
        'LT' => 'h:mm a',
        'LTS' => 'h:mm:ss a',
        'L' => 'D/MM/yy',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY, h:mm a',
        'LLLL' => 'dd MMMM YYYY, h:mm a',
    ],
    'meridiem' => ['a', 'p'],
]);
