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


return array_replace_recursive(require __DIR__.'/uz.php', [
    'formats' => [
        'L' => 'DD/MM/yy',
        'LL' => 'D MMM, YYYY',
        'LLL' => 'D MMMM, YYYY HH:mm',
        'LLLL' => 'dddd, DD MMMM, YYYY HH:mm',
    ],
    'meridiem' => ['ТО', 'ТК'],
]);
