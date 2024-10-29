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
        'L' => 'DD.MM.YYYY',
    ],
    'months' => ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avqust', 'sentyabr', 'oktyabr', 'noyabr', 'dekabr'],
    'months_short' => ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'İyn', 'İyl', 'Avq', 'Sen', 'Okt', 'Noy', 'Dek'],
    'weekdays' => ['bazar günü', 'birinci gün', 'ikinci gün', 'üçüncü gün', 'dördüncü gün', 'beşinci gün', 'altıncı gün'],
    'weekdays_short' => ['baz', 'bir', 'iki', 'üçü', 'dör', 'beş', 'alt'],
    'weekdays_min' => ['baz', 'bir', 'iki', 'üçü', 'dör', 'beş', 'alt'],
    'first_day_of_week' => 6,
    'day_of_first_week_of_year' => 1,
]);
