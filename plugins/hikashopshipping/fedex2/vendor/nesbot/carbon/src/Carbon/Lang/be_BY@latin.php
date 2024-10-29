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
    'months' => ['studzienia', 'lutaha', 'sakavika', 'krasavika', 'maja', 'červienia', 'lipienia', 'žniŭnia', 'vieraśnia', 'kastryčnika', 'listapada', 'śniežnia'],
    'months_short' => ['Stu', 'Lut', 'Sak', 'Kra', 'Maj', 'Čer', 'Lip', 'Žni', 'Vie', 'Kas', 'Lis', 'Śni'],
    'weekdays' => ['Niadziela', 'Paniadziełak', 'Aŭtorak', 'Sierada', 'Čaćvier', 'Piatnica', 'Subota'],
    'weekdays_short' => ['Nia', 'Pan', 'Aŭt', 'Sie', 'Čać', 'Pia', 'Sub'],
    'weekdays_min' => ['Nia', 'Pan', 'Aŭt', 'Sie', 'Čać', 'Pia', 'Sub'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
]);
