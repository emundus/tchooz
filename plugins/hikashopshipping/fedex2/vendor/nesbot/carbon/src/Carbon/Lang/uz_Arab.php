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
    'weekdays' => ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'],
    'weekdays_short' => ['ی.', 'د.', 'س.', 'چ.', 'پ.', 'ج.', 'ش.'],
    'weekdays_min' => ['ی.', 'د.', 'س.', 'چ.', 'پ.', 'ج.', 'ش.'],
    'months' => ['جنوری', 'فبروری', 'مارچ', 'اپریل', 'می', 'جون', 'جولای', 'اگست', 'سپتمبر', 'اکتوبر', 'نومبر', 'دسمبر'],
    'months_short' => ['جنو', 'فبر', 'مار', 'اپر', 'می', 'جون', 'جول', 'اگس', 'سپت', 'اکت', 'نوم', 'دسم'],
    'first_day_of_week' => 6,
    'weekend' => [4, 5],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'YYYY-MM-dd',
        'LL' => 'YYYY MMM D',
        'LLL' => 'YYYY MMMM D HH:mm',
        'LLLL' => 'YYYY MMMM D, dddd HH:mm',
    ],
]);
