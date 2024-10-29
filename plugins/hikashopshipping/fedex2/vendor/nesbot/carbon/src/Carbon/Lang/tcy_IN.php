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
    'months' => ['ಜನವರಿ', 'ಫೆಬ್ರುವರಿ', 'ಮಾರ್ಚ್', 'ಏಪ್ರಿಲ್‌‌', 'ಮೇ', 'ಜೂನ್', 'ಜುಲೈ', 'ಆಗಸ್ಟ್', 'ಸೆಪ್ಟೆಂಬರ್‌', 'ಅಕ್ಟೋಬರ್', 'ನವೆಂಬರ್', 'ಡಿಸೆಂಬರ್'],
    'months_short' => ['ಜ', 'ಫೆ', 'ಮಾ', 'ಏ', 'ಮೇ', 'ಜೂ', 'ಜು', 'ಆ', 'ಸೆ', 'ಅ', 'ನ', 'ಡಿ'],
    'weekdays' => ['ಐಥಾರ', 'ಸೋಮಾರ', 'ಅಂಗರೆ', 'ಬುಧಾರ', 'ಗುರುವಾರ', 'ಶುಕ್ರರ', 'ಶನಿವಾರ'],
    'weekdays_short' => ['ಐ', 'ಸೋ', 'ಅಂ', 'ಬು', 'ಗು', 'ಶು', 'ಶ'],
    'weekdays_min' => ['ಐ', 'ಸೋ', 'ಅಂ', 'ಬು', 'ಗು', 'ಶು', 'ಶ'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['ಕಾಂಡೆ', 'ಬಯ್ಯ'],

    'year' => ':count ನೀರ್', // less reliable
    'y' => ':count ನೀರ್', // less reliable
    'a_year' => ':count ನೀರ್', // less reliable

    'month' => ':count ಮೀನ್', // less reliable
    'm' => ':count ಮೀನ್', // less reliable
    'a_month' => ':count ಮೀನ್', // less reliable

    'day' => ':count ಸುಗ್ಗಿ', // less reliable
    'd' => ':count ಸುಗ್ಗಿ', // less reliable
    'a_day' => ':count ಸುಗ್ಗಿ', // less reliable
]);
