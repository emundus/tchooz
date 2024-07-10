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
    'months' => ['जनवरी', 'फरवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'],
    'months_short' => ['जन', 'फर', 'मार्च', 'अप्रै', 'मई', 'जून', 'जुल', 'अग', 'सित', 'अक्टू', 'नव', 'दिस'],
    'weekdays' => ['रविवार', 'सोमवार', 'मंगल्लवार', 'बुधवार', 'बृहस्पतिवार', 'शुक्रवार', 'शनिवार'],
    'weekdays_short' => ['रवि', 'सोम', 'मंगल', 'बुध', 'बृहस्पति', 'शुक्र', 'शनि'],
    'weekdays_min' => ['रवि', 'सोम', 'मंगल', 'बुध', 'बृहस्पति', 'शुक्र', 'शनि'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['पूर्वाह्न', 'अपराह्न'],

    'year' => ':count आंहू', // less reliable
    'y' => ':count आंहू', // less reliable
    'a_year' => ':count आंहू', // less reliable

    'month' => ':count सूरज', // less reliable
    'm' => ':count सूरज', // less reliable
    'a_month' => ':count सूरज', // less reliable

    'week' => ':count निवाज', // less reliable
    'w' => ':count निवाज', // less reliable
    'a_week' => ':count निवाज', // less reliable

    'day' => ':count अेक', // less reliable
    'd' => ':count अेक', // less reliable
    'a_day' => ':count अेक', // less reliable

    'hour' => ':count दुनियांण', // less reliable
    'h' => ':count दुनियांण', // less reliable
    'a_hour' => ':count दुनियांण', // less reliable
]);
