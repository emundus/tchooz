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
    'months' => ['जनवरी', 'फरवरी', 'मार्च', 'एप्रैल', 'मेई', 'जून', 'जूलै', 'अगस्त', 'सितंबर', 'अक्तूबर', 'नवंबर', 'दिसंबर'],
    'months_short' => ['जनवरी', 'फरवरी', 'मार्च', 'एप्रैल', 'मेई', 'जून', 'जूलै', 'अगस्त', 'सितंबर', 'अक्तूबर', 'नवंबर', 'दिसंबर'],
    'weekdays' => ['ऐतबार', 'सोमबार', 'मंगलबर', 'बुधबार', 'बीरबार', 'शुक्करबार', 'श्नीचरबार'],
    'weekdays_short' => ['ऐत', 'सोम', 'मंगल', 'बुध', 'बीर', 'शुक्कर', 'श्नीचर'],
    'weekdays_min' => ['ऐत', 'सोम', 'मंगल', 'बुध', 'बीर', 'शुक्कर', 'श्नीचर'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['सञं', 'सबेर'],

    'second' => ':count सङार', // less reliable
    's' => ':count सङार', // less reliable
    'a_second' => ':count सङार', // less reliable
]);
