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
    'months' => ['ינואר', 'פברואר', 'מרץ', 'אפריל', 'מאי', 'יוני', 'יולי', 'אוגוסט', 'ספטמבר', 'אוקטובר', 'נובמבר', 'דצמבר'],
    'months_short' => ['ינו׳', 'פבר׳', 'מרץ', 'אפר׳', 'מאי', 'יוני', 'יולי', 'אוג׳', 'ספט׳', 'אוק׳', 'נוב׳', 'דצמ׳'],
    'weekdays' => ['יום ראשון', 'יום שני', 'יום שלישי', 'יום רביעי', 'יום חמישי', 'יום שישי', 'יום שבת'],
    'weekdays_short' => ['יום א׳', 'יום ב׳', 'יום ג׳', 'יום ד׳', 'יום ה׳', 'יום ו׳', 'שבת'],
    'weekdays_min' => ['א׳', 'ב׳', 'ג׳', 'ד׳', 'ה׳', 'ו׳', 'ש׳'],
    'meridiem' => ['לפנה״צ', 'אחה״צ'],
    'formats' => [
        'LT' => 'H:mm',
        'LTS' => 'H:mm:ss',
        'L' => 'D.M.YYYY',
        'LL' => 'D בMMM YYYY',
        'LLL' => 'D בMMMM YYYY H:mm',
        'LLLL' => 'dddd, D בMMMM YYYY H:mm',
    ],

    'year' => ':count שנה',
    'y' => ':count שנה',
    'a_year' => ':count שנה',

    'month' => ':count חודש',
    'm' => ':count חודש',
    'a_month' => ':count חודש',

    'week' => ':count שבוע',
    'w' => ':count שבוע',
    'a_week' => ':count שבוע',

    'day' => ':count יום',
    'd' => ':count יום',
    'a_day' => ':count יום',

    'hour' => ':count שעה',
    'h' => ':count שעה',
    'a_hour' => ':count שעה',

    'minute' => ':count דקה',
    'min' => ':count דקה',
    'a_minute' => ':count דקה',

    'second' => ':count שניה',
    's' => ':count שניה',
    'a_second' => ':count שניה',

    'ago' => 'לפני :time',
    'from_now' => 'בעוד :time',
]);
