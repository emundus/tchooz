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


return array_replace_recursive(require __DIR__.'/gsw.php', [
    'meridiem' => ['vorm.', 'nam.'],
    'months' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'Auguscht', 'Septämber', 'Oktoober', 'Novämber', 'Dezämber'],
    'first_day_of_week' => 1,
    'formats' => [
        'LLL' => 'Do MMMM YYYY HH:mm',
        'LLLL' => 'dddd, Do MMMM YYYY HH:mm',
    ],
]);
