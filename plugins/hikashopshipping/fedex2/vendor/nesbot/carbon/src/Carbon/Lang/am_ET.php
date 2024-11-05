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
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['ጃንዩወሪ', 'ፌብሩወሪ', 'ማርች', 'ኤፕሪል', 'ሜይ', 'ጁን', 'ጁላይ', 'ኦገስት', 'ሴፕቴምበር', 'ኦክቶበር', 'ኖቬምበር', 'ዲሴምበር'],
    'months_short' => ['ጃንዩ', 'ፌብሩ', 'ማርች', 'ኤፕረ', 'ሜይ ', 'ጁን ', 'ጁላይ', 'ኦገስ', 'ሴፕቴ', 'ኦክተ', 'ኖቬም', 'ዲሴም'],
    'weekdays' => ['እሑድ', 'ሰኞ', 'ማክሰኞ', 'ረቡዕ', 'ሐሙስ', 'ዓርብ', 'ቅዳሜ'],
    'weekdays_short' => ['እሑድ', 'ሰኞ ', 'ማክሰ', 'ረቡዕ', 'ሐሙስ', 'ዓርብ', 'ቅዳሜ'],
    'weekdays_min' => ['እሑድ', 'ሰኞ ', 'ማክሰ', 'ረቡዕ', 'ሐሙስ', 'ዓርብ', 'ቅዳሜ'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['ጡዋት', 'ከሰዓት'],

    'year' => ':count አመት',
    'y' => ':count አመት',
    'a_year' => ':count አመት',

    'month' => ':count ወር',
    'm' => ':count ወር',
    'a_month' => ':count ወር',

    'week' => ':count ሳምንት',
    'w' => ':count ሳምንት',
    'a_week' => ':count ሳምንት',

    'day' => ':count ቀን',
    'd' => ':count ቀን',
    'a_day' => ':count ቀን',

    'hour' => ':count ሰዓት',
    'h' => ':count ሰዓት',
    'a_hour' => ':count ሰዓት',

    'minute' => ':count ደቂቃ',
    'min' => ':count ደቂቃ',
    'a_minute' => ':count ደቂቃ',

    'second' => ':count ሴኮንድ',
    's' => ':count ሴኮንድ',
    'a_second' => ':count ሴኮንድ',

    'ago' => 'ከ:time በፊት',
    'from_now' => 'በ:time ውስጥ',
]);
