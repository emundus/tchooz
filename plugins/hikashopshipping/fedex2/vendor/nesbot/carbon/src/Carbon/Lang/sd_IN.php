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


return array_replace_recursive(require __DIR__.'/sd.php', [
    'formats' => [
        'L' => 'D/M/YY',
    ],
    'months' => ['جنوري', 'فبروري', 'مارچ', 'اپريل', 'مي', 'جون', 'جولاءِ', 'آگسٽ', 'سيپٽيمبر', 'آڪٽوبر', 'نومبر', 'ڊسمبر'],
    'months_short' => ['جنوري', 'فبروري', 'مارچ', 'اپريل', 'مي', 'جون', 'جولاءِ', 'آگسٽ', 'سيپٽيمبر', 'آڪٽوبر', 'نومبر', 'ڊسمبر'],
    'weekdays' => ['آرتوارُ', 'سومرُ', 'منگلُ', 'ٻُڌرُ', 'وسپت', 'جُمو', 'ڇنڇر'],
    'weekdays_short' => ['آرتوارُ', 'سومرُ', 'منگلُ', 'ٻُڌرُ', 'وسپت', 'جُمو', 'ڇنڇر'],
    'weekdays_min' => ['آرتوارُ', 'سومرُ', 'منگلُ', 'ٻُڌرُ', 'وسپت', 'جُمو', 'ڇنڇر'],
    'day_of_first_week_of_year' => 1,
]);
