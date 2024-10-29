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
    'months' => ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रेल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर'],
    'months_short' => ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रेल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर'],
    'weekdays' => ['आथवार', 'चॅ़दुरवार', 'बोमवार', 'ब्वदवार', 'ब्रसवार', 'शोकुरवार', 'बटुवार'],
    'weekdays_short' => ['आथ ', 'चॅ़दुर', 'बोम', 'ब्वद', 'ब्रस', 'शोकुर', 'बटु'],
    'weekdays_min' => ['आथ ', 'चॅ़दुर', 'बोम', 'ब्वद', 'ब्रस', 'शोकुर', 'बटु'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['पूर्वाह्न', 'अपराह्न'],
]);
