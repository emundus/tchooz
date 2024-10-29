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
    'meridiem' => ['ꎸꄑ', 'ꁯꋒ'],
    'weekdays' => ['ꑭꆏꑍ', 'ꆏꊂꋍ', 'ꆏꊂꑍ', 'ꆏꊂꌕ', 'ꆏꊂꇖ', 'ꆏꊂꉬ', 'ꆏꊂꃘ'],
    'weekdays_short' => ['ꑭꆏ', 'ꆏꋍ', 'ꆏꑍ', 'ꆏꌕ', 'ꆏꇖ', 'ꆏꉬ', 'ꆏꃘ'],
    'weekdays_min' => ['ꑭꆏ', 'ꆏꋍ', 'ꆏꑍ', 'ꆏꌕ', 'ꆏꇖ', 'ꆏꉬ', 'ꆏꃘ'],
    'months' => null,
    'months_short' => ['ꋍꆪ', 'ꑍꆪ', 'ꌕꆪ', 'ꇖꆪ', 'ꉬꆪ', 'ꃘꆪ', 'ꏃꆪ', 'ꉆꆪ', 'ꈬꆪ', 'ꊰꆪ', 'ꊰꊪꆪ', 'ꊰꑋꆪ'],
    'formats' => [
        'LT' => 'h:mm a',
        'LTS' => 'h:mm:ss a',
        'L' => 'YYYY-MM-dd',
        'LL' => 'YYYY MMM D',
        'LLL' => 'YYYY MMMM D h:mm a',
        'LLLL' => 'YYYY MMMM D, dddd h:mm a',
    ],

    'year' => ':count ꒉ', // less reliable
    'y' => ':count ꒉ', // less reliable
    'a_year' => ':count ꒉ', // less reliable

    'month' => ':count ꆪ',
    'm' => ':count ꆪ',
    'a_month' => ':count ꆪ',

    'week' => ':count ꏃ', // less reliable
    'w' => ':count ꏃ', // less reliable
    'a_week' => ':count ꏃ', // less reliable

    'day' => ':count ꏜ', // less reliable
    'd' => ':count ꏜ', // less reliable
    'a_day' => ':count ꏜ', // less reliable

    'hour' => ':count ꄮꈉ',
    'h' => ':count ꄮꈉ',
    'a_hour' => ':count ꄮꈉ',

    'minute' => ':count ꀄꊭ', // less reliable
    'min' => ':count ꀄꊭ', // less reliable
    'a_minute' => ':count ꀄꊭ', // less reliable

    'second' => ':count ꇅ', // less reliable
    's' => ':count ꇅ', // less reliable
    'a_second' => ':count ꇅ', // less reliable
]);
