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
    'months' => ['Phando', 'Luhuhi', 'Ṱhafamuhwe', 'Lambamai', 'Shundunthule', 'Fulwi', 'Fulwana', 'Ṱhangule', 'Khubvumedzi', 'Tshimedzi', 'Ḽara', 'Nyendavhusiku'],
    'months_short' => ['Pha', 'Luh', 'Fam', 'Lam', 'Shu', 'Lwi', 'Lwa', 'Ngu', 'Khu', 'Tsh', 'Ḽar', 'Nye'],
    'weekdays' => ['Swondaha', 'Musumbuluwo', 'Ḽavhuvhili', 'Ḽavhuraru', 'Ḽavhuṋa', 'Ḽavhuṱanu', 'Mugivhela'],
    'weekdays_short' => ['Swo', 'Mus', 'Vhi', 'Rar', 'ṋa', 'Ṱan', 'Mug'],
    'weekdays_min' => ['Swo', 'Mus', 'Vhi', 'Rar', 'ṋa', 'Ṱan', 'Mug'],
    'day_of_first_week_of_year' => 1,

]);
