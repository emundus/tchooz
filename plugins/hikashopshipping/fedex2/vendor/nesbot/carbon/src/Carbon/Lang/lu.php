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
    'meridiem' => ['Dinda', 'Dilolo'],
    'weekdays' => ['Lumingu', 'Nkodya', 'Ndàayà', 'Ndangù', 'Njòwa', 'Ngòvya', 'Lubingu'],
    'weekdays_short' => ['Lum', 'Nko', 'Ndy', 'Ndg', 'Njw', 'Ngv', 'Lub'],
    'weekdays_min' => ['Lum', 'Nko', 'Ndy', 'Ndg', 'Njw', 'Ngv', 'Lub'],
    'months' => ['Ciongo', 'Lùishi', 'Lusòlo', 'Mùuyà', 'Lumùngùlù', 'Lufuimi', 'Kabàlàshìpù', 'Lùshìkà', 'Lutongolo', 'Lungùdi', 'Kaswèkèsè', 'Ciswà'],
    'months_short' => ['Cio', 'Lui', 'Lus', 'Muu', 'Lum', 'Luf', 'Kab', 'Lush', 'Lut', 'Lun', 'Kas', 'Cis'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],
]);
