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
    'months' => ['जानुवारी', 'फेब्रुवारी', 'मार्स', 'एफ्रिल', 'मे', 'जुन', 'जुलाइ', 'आगस्थ', 'सेबथेज्ब़र', 'अखथबर', 'नबेज्ब़र', 'दिसेज्ब़र'],
    'months_short' => ['जानुवारी', 'फेब्रुवारी', 'मार्स', 'एप्रिल', 'मे', 'जुन', 'जुलाइ', 'आगस्थ', 'सेबथेज्ब़र', 'अखथबर', 'नबेज्ब़र', 'दिसेज्ब़र'],
    'weekdays' => ['रबिबार', 'सोबार', 'मंगलबार', 'बुदबार', 'बिसथिबार', 'सुखुरबार', 'सुनिबार'],
    'weekdays_short' => ['रबि', 'सम', 'मंगल', 'बुद', 'बिसथि', 'सुखुर', 'सुनि'],
    'weekdays_min' => ['रबि', 'सम', 'मंगल', 'बुद', 'बिसथि', 'सुखुर', 'सुनि'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['फुं.', 'बेलासे.'],
]);
