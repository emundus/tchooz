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


return array_replace_recursive(require __DIR__.'/ur.php', [
    'formats' => [
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['جنوری', 'فروری', 'مارچ', 'اپریل', 'مئی', 'جون', 'جولائی', 'اگست', 'ستمبر', 'اکتوبر', 'نومبر', 'دسمبر'],
    'months_short' => ['جنوری', 'فروری', 'مارچ', 'اپریل', 'مئی', 'جون', 'جولائی', 'اگست', 'ستمبر', 'اکتوبر', 'نومبر', 'دسمبر'],
    'weekdays' => ['اتوار', 'پير', 'منگل', 'بدھ', 'جمعرات', 'جمعه', 'هفته'],
    'weekdays_short' => ['اتوار', 'پير', 'منگل', 'بدھ', 'جمعرات', 'جمعه', 'هفته'],
    'weekdays_min' => ['اتوار', 'پير', 'منگل', 'بدھ', 'جمعرات', 'جمعه', 'هفته'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['ص', 'ش'],
]);
