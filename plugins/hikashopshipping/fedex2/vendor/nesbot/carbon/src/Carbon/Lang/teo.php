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


return array_replace_recursive(require __DIR__.'/ta.php', [
    'meridiem' => ['Taparachu', 'Ebongi'],
    'weekdays' => ['Nakaejuma', 'Nakaebarasa', 'Nakaare', 'Nakauni', 'Nakaung’on', 'Nakakany', 'Nakasabiti'],
    'weekdays_short' => ['Jum', 'Bar', 'Aar', 'Uni', 'Ung', 'Kan', 'Sab'],
    'weekdays_min' => ['Jum', 'Bar', 'Aar', 'Uni', 'Ung', 'Kan', 'Sab'],
    'months' => ['Orara', 'Omuk', 'Okwamg’', 'Odung’el', 'Omaruk', 'Omodok’king’ol', 'Ojola', 'Opedel', 'Osokosokoma', 'Otibar', 'Olabor', 'Opoo'],
    'months_short' => ['Rar', 'Muk', 'Kwa', 'Dun', 'Mar', 'Mod', 'Jol', 'Ped', 'Sok', 'Tib', 'Lab', 'Poo'],
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
