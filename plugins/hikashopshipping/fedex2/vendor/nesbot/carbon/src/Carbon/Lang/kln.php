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
    'meridiem' => ['krn', 'koosk'],
    'weekdays' => ['Kotisap', 'Kotaai', 'Koaeng’', 'Kosomok', 'Koang’wan', 'Komuut', 'Kolo'],
    'weekdays_short' => ['Kts', 'Kot', 'Koo', 'Kos', 'Koa', 'Kom', 'Kol'],
    'weekdays_min' => ['Kts', 'Kot', 'Koo', 'Kos', 'Koa', 'Kom', 'Kol'],
    'months' => ['Mulgul', 'Ng’atyaato', 'Kiptaamo', 'Iwootkuut', 'Mamuut', 'Paagi', 'Ng’eiyeet', 'Rooptui', 'Bureet', 'Epeeso', 'Kipsuunde ne taai', 'Kipsuunde nebo aeng’'],
    'months_short' => ['Mul', 'Ngat', 'Taa', 'Iwo', 'Mam', 'Paa', 'Nge', 'Roo', 'Bur', 'Epe', 'Kpt', 'Kpa'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],

    'year' => ':count maghatiat', // less reliable
    'y' => ':count maghatiat', // less reliable
    'a_year' => ':count maghatiat', // less reliable
]);
