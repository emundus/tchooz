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
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['Ferikgong', 'Tlhakole', 'Mopitlwe', 'Moranang', 'Motsheganong', 'Seetebosigo', 'Phukwi', 'Phatwe', 'Lwetse', 'Diphalane', 'Ngwanatsele', 'Sedimonthole'],
    'months_short' => ['Fer', 'Tlh', 'Mop', 'Mor', 'Mot', 'See', 'Phu', 'Pha', 'Lwe', 'Dip', 'Ngw', 'Sed'],
    'weekdays' => ['laTshipi', 'Mosupologo', 'Labobedi', 'Laboraro', 'Labone', 'Labotlhano', 'Lamatlhatso'],
    'weekdays_short' => ['Tsh', 'Mos', 'Bed', 'Rar', 'Ne', 'Tlh', 'Mat'],
    'weekdays_min' => ['Tsh', 'Mos', 'Bed', 'Rar', 'Ne', 'Tlh', 'Mat'],
    'day_of_first_week_of_year' => 1,

    'year' => 'dingwaga di le :count',
    'y' => 'dingwaga di le :count',
    'a_year' => 'dingwaga di le :count',

    'month' => 'dikgwedi di le :count',
    'm' => 'dikgwedi di le :count',
    'a_month' => 'dikgwedi di le :count',

    'week' => 'dibeke di le :count',
    'w' => 'dibeke di le :count',
    'a_week' => 'dibeke di le :count',

    'day' => 'malatsi :count',
    'd' => 'malatsi :count',
    'a_day' => 'malatsi :count',

    'hour' => 'diura di le :count',
    'h' => 'diura di le :count',
    'a_hour' => 'diura di le :count',

    'minute' => 'metsotso e le :count',
    'min' => 'metsotso e le :count',
    'a_minute' => 'metsotso e le :count',

    'second' => 'metsotswana e le :count',
    's' => 'metsotswana e le :count',
    'a_second' => 'metsotswana e le :count',
]);
