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
        'L' => 'D/M/YY',
    ],
    'months' => ['जनवरी', 'फरवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर"'],
    'months_short' => ['जनवरी', 'फरवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर"'],
    'weekdays' => ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'],
    'weekdays_short' => ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'],
    'weekdays_min' => ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['पूर्वाह्न', 'अपराह्न'],

    'hour' => ':count मौसम',
    'h' => ':count मौसम',
    'a_hour' => ':count मौसम',

    'minute' => ':count कला',
    'min' => ':count कला',
    'a_minute' => ':count कला',

    'second' => ':count सोमार',
    's' => ':count सोमार',
    'a_second' => ':count सोमार',

    'year' => ':count साल',
    'y' => ':count साल',
    'a_year' => ':count साल',

    'month' => ':count महिना',
    'm' => ':count महिना',
    'a_month' => ':count महिना',

    'week' => ':count सप्ताह',
    'w' => ':count सप्ताह',
    'a_week' => ':count सप्ताह',

    'day' => ':count दिन',
    'd' => ':count दिन',
    'a_day' => ':count दिन',
]);
