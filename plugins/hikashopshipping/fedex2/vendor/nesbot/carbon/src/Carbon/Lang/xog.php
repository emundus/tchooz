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
    'meridiem' => ['Munkyo', 'Eigulo'],
    'weekdays' => ['Sabiiti', 'Balaza', 'Owokubili', 'Owokusatu', 'Olokuna', 'Olokutaanu', 'Olomukaaga'],
    'weekdays_short' => ['Sabi', 'Bala', 'Kubi', 'Kusa', 'Kuna', 'Kuta', 'Muka'],
    'weekdays_min' => ['Sabi', 'Bala', 'Kubi', 'Kusa', 'Kuna', 'Kuta', 'Muka'],
    'months' => ['Janwaliyo', 'Febwaliyo', 'Marisi', 'Apuli', 'Maayi', 'Juuni', 'Julaayi', 'Agusito', 'Sebuttemba', 'Okitobba', 'Novemba', 'Desemba'],
    'months_short' => ['Jan', 'Feb', 'Mar', 'Apu', 'Maa', 'Juu', 'Jul', 'Agu', 'Seb', 'Oki', 'Nov', 'Des'],
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
