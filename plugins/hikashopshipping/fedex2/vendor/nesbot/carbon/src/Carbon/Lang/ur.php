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


$months = [
    'جنوری',
    'فروری',
    'مارچ',
    'اپریل',
    'مئی',
    'جون',
    'جولائی',
    'اگست',
    'ستمبر',
    'اکتوبر',
    'نومبر',
    'دسمبر',
];

$weekdays = [
    'اتوار',
    'پیر',
    'منگل',
    'بدھ',
    'جمعرات',
    'جمعہ',
    'ہفتہ',
];

return [
    'year' => 'ایک سال|:count سال',
    'month' => 'ایک ماہ|:count ماہ',
    'week' => ':count ہفتے',
    'day' => 'ایک دن|:count دن',
    'hour' => 'ایک گھنٹہ|:count گھنٹے',
    'minute' => 'ایک منٹ|:count منٹ',
    'second' => 'چند سیکنڈ|:count سیکنڈ',
    'ago' => ':time قبل',
    'from_now' => ':time بعد',
    'after' => ':time بعد',
    'before' => ':time پہلے',
    'diff_now' => 'اب',
    'diff_today' => 'آج',
    'diff_today_regexp' => 'آج(?:\\s+بوقت)?',
    'diff_yesterday' => 'گزشتہ کل',
    'diff_yesterday_regexp' => 'گذشتہ(?:\\s+روز)?(?:\\s+بوقت)?',
    'diff_tomorrow' => 'آئندہ کل',
    'diff_tomorrow_regexp' => 'کل(?:\\s+بوقت)?',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd، D MMMM YYYY HH:mm',
    ],
    'calendar' => [
        'sameDay' => '[آج بوقت] LT',
        'nextDay' => '[کل بوقت] LT',
        'nextWeek' => 'dddd [بوقت] LT',
        'lastDay' => '[گذشتہ روز بوقت] LT',
        'lastWeek' => '[گذشتہ] dddd [بوقت] LT',
        'sameElse' => 'L',
    ],
    'meridiem' => ['صبح', 'شام'],
    'months' => $months,
    'months_short' => $months,
    'weekdays' => $weekdays,
    'weekdays_short' => $weekdays,
    'weekdays_min' => $weekdays,
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
    'list' => ['، ', ' اور '],
];
