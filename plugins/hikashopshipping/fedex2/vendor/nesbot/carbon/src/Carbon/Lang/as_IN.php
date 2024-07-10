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
        'L' => 'D-MM-YYYY',
    ],
    'months' => ['জানুৱাৰী', 'ফেব্ৰুৱাৰী', 'মাৰ্চ', 'এপ্ৰিল', 'মে', 'জুন', 'জুলাই', 'আগষ্ট', 'ছেপ্তেম্বৰ', 'অক্টোবৰ', 'নৱেম্বৰ', 'ডিচেম্বৰ'],
    'months_short' => ['জানু', 'ফেব্ৰু', 'মাৰ্চ', 'এপ্ৰিল', 'মে', 'জুন', 'জুলাই', 'আগ', 'সেপ্ট', 'অক্টো', 'নভে', 'ডিসে'],
    'weekdays' => ['দেওবাৰ', 'সোমবাৰ', 'মঙ্গলবাৰ', 'বুধবাৰ', 'বৃহষ্পতিবাৰ', 'শুক্ৰবাৰ', 'শনিবাৰ'],
    'weekdays_short' => ['দেও', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহষ্পতি', 'শুক্ৰ', 'শনি'],
    'weekdays_min' => ['দেও', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহষ্পতি', 'শুক্ৰ', 'শনি'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['পূৰ্ব্বাহ্ন', 'অপৰাহ্ন'],

    'year' => ':count বছৰ',
    'y' => ':count বছৰ',
    'a_year' => ':count বছৰ',

    'month' => ':count মাহ',
    'm' => ':count মাহ',
    'a_month' => ':count মাহ',

    'week' => ':count সপ্তাহ',
    'w' => ':count সপ্তাহ',
    'a_week' => ':count সপ্তাহ',

    'day' => ':count বাৰ',
    'd' => ':count বাৰ',
    'a_day' => ':count বাৰ',

    'hour' => ':count ঘণ্টা',
    'h' => ':count ঘণ্টা',
    'a_hour' => ':count ঘণ্টা',

    'minute' => ':count মিনিট',
    'min' => ':count মিনিট',
    'a_minute' => ':count মিনিট',

    'second' => ':count দ্বিতীয়',
    's' => ':count দ্বিতীয়',
    'a_second' => ':count দ্বিতীয়',
]);
