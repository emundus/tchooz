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
    'meridiem' => ['Lwamilawu', 'Pashamihe'],
    'weekdays' => ['Mulungu', 'Jumatatu', 'Jumanne', 'Jumatano', 'Alahamisi', 'Ijumaa', 'Jumamosi'],
    'weekdays_short' => ['Mul', 'Jtt', 'Jnn', 'Jtn', 'Alh', 'Iju', 'Jmo'],
    'weekdays_min' => ['Mul', 'Jtt', 'Jnn', 'Jtn', 'Alh', 'Iju', 'Jmo'],
    'months' => ['Mupalangulwa', 'Mwitope', 'Mushende', 'Munyi', 'Mushende Magali', 'Mujimbi', 'Mushipepo', 'Mupuguto', 'Munyense', 'Mokhu', 'Musongandembwe', 'Muhaano'],
    'months_short' => ['Mup', 'Mwi', 'Msh', 'Mun', 'Mag', 'Muj', 'Msp', 'Mpg', 'Mye', 'Mok', 'Mus', 'Muh'],
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
