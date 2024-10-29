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
        'L' => 'D-M-YY',
    ],
    'months' => ['जानेवारी', 'फेब्रुवारी', 'मार्च', 'एप्रिल', 'मे', 'जून', 'जुलै', 'ओगस्ट', 'सेप्टेंबर', 'ओक्टोबर', 'नोव्हेंबर', 'डिसेंबर'],
    'months_short' => ['जानेवारी', 'फेब्रुवारी', 'मार्च', 'एप्रिल', 'मे', 'जून', 'जुलै', 'ओगस्ट', 'सेप्टेंबर', 'ओक्टोबर', 'नोव्हेंबर', 'डिसेंबर'],
    'weekdays' => ['आयतार', 'सोमार', 'मंगळवार', 'बुधवार', 'बेरेसतार', 'शुकरार', 'शेनवार'],
    'weekdays_short' => ['आयतार', 'सोमार', 'मंगळवार', 'बुधवार', 'बेरेसतार', 'शुकरार', 'शेनवार'],
    'weekdays_min' => ['आयतार', 'सोमार', 'मंगळवार', 'बुधवार', 'बेरेसतार', 'शुकरार', 'शेनवार'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['म.पू.', 'म.नं.'],

    'year' => ':count वैशाकु', // less reliable
    'y' => ':count वैशाकु', // less reliable
    'a_year' => ':count वैशाकु', // less reliable

    'week' => ':count आदित्यवार', // less reliable
    'w' => ':count आदित्यवार', // less reliable
    'a_week' => ':count आदित्यवार', // less reliable

    'minute' => ':count नोंद', // less reliable
    'min' => ':count नोंद', // less reliable
    'a_minute' => ':count नोंद', // less reliable

    'second' => ':count तेंको', // less reliable
    's' => ':count तेंको', // less reliable
    'a_second' => ':count तेंको', // less reliable

    'month' => ':count मैनो',
    'm' => ':count मैनो',
    'a_month' => ':count मैनो',

    'day' => ':count दिवसु',
    'd' => ':count दिवसु',
    'a_day' => ':count दिवसु',

    'hour' => ':count घंते',
    'h' => ':count घंते',
    'a_hour' => ':count घंते',
]);
