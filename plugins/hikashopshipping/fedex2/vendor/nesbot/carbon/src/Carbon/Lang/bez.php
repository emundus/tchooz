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
    'meridiem' => ['pamilau', 'pamunyi'],
    'weekdays' => ['pa mulungu', 'pa shahuviluha', 'pa hivili', 'pa hidatu', 'pa hitayi', 'pa hihanu', 'pa shahulembela'],
    'weekdays_short' => ['Mul', 'Vil', 'Hiv', 'Hid', 'Hit', 'Hih', 'Lem'],
    'weekdays_min' => ['Mul', 'Vil', 'Hiv', 'Hid', 'Hit', 'Hih', 'Lem'],
    'months' => ['pa mwedzi gwa hutala', 'pa mwedzi gwa wuvili', 'pa mwedzi gwa wudatu', 'pa mwedzi gwa wutai', 'pa mwedzi gwa wuhanu', 'pa mwedzi gwa sita', 'pa mwedzi gwa saba', 'pa mwedzi gwa nane', 'pa mwedzi gwa tisa', 'pa mwedzi gwa kumi', 'pa mwedzi gwa kumi na moja', 'pa mwedzi gwa kumi na mbili'],
    'months_short' => ['Hut', 'Vil', 'Dat', 'Tai', 'Han', 'Sit', 'Sab', 'Nan', 'Tis', 'Kum', 'Kmj', 'Kmb'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],
]);
