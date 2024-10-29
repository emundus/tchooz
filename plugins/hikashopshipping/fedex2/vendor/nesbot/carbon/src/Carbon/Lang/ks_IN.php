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
        'L' => 'M/D/YY',
    ],
    'months' => ['جنؤری', 'فرؤری', 'مارٕچ', 'اپریل', 'میٔ', 'جوٗن', 'جوٗلایی', 'اگست', 'ستمبر', 'اکتوٗبر', 'نومبر', 'دسمبر'],
    'months_short' => ['جنؤری', 'فرؤری', 'مارٕچ', 'اپریل', 'میٔ', 'جوٗن', 'جوٗلایی', 'اگست', 'ستمبر', 'اکتوٗبر', 'نومبر', 'دسمبر'],
    'weekdays' => ['آتهوار', 'ژءندروار', 'بوءںوار', 'بودهوار', 'برىسوار', 'جمع', 'بٹوار'],
    'weekdays_short' => ['آتهوار', 'ژءنتروار', 'بوءںوار', 'بودهوار', 'برىسوار', 'جمع', 'بٹوار'],
    'weekdays_min' => ['آتهوار', 'ژءنتروار', 'بوءںوار', 'بودهوار', 'برىسوار', 'جمع', 'بٹوار'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['دوپھربرونھ', 'دوپھرپتھ'],

    'year' => ':count آب', // less reliable
    'y' => ':count آب', // less reliable
    'a_year' => ':count آب', // less reliable

    'month' => ':count रान्', // less reliable
    'm' => ':count रान्', // less reliable
    'a_month' => ':count रान्', // less reliable

    'week' => ':count آتھٕوار', // less reliable
    'w' => ':count آتھٕوار', // less reliable
    'a_week' => ':count آتھٕوار', // less reliable

    'hour' => ':count سۄن', // less reliable
    'h' => ':count سۄن', // less reliable
    'a_hour' => ':count سۄن', // less reliable

    'minute' => ':count فَن', // less reliable
    'min' => ':count فَن', // less reliable
    'a_minute' => ':count فَن', // less reliable

    'second' => ':count दोʼयुम', // less reliable
    's' => ':count दोʼयुम', // less reliable
    'a_second' => ':count दोʼयुम', // less reliable
]);
