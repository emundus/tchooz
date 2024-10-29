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
        'L' => 'DD/MM/YY',
    ],
    'months' => ['enikwsi', 'chkwali', 'xamokhwite', 'kwetayoxe', 'tainipen', 'kichinipen', 'lainipen', 'winaminke', 'kichitahkok', 'puksit', 'wini', 'muxkotae'],
    'months_short' => ['eni', 'chk', 'xam', 'kwe', 'tai', 'nip', 'lai', 'win', 'tah', 'puk', 'kun', 'mux'],
    'weekdays' => ['kentuwei', 'manteke', 'tusteke', 'lelai', 'tasteke', 'pelaiteke', 'sateteke'],
    'weekdays_short' => ['ken', 'man', 'tus', 'lel', 'tas', 'pel', 'sat'],
    'weekdays_min' => ['ken', 'man', 'tus', 'lel', 'tas', 'pel', 'sat'],
    'day_of_first_week_of_year' => 1,

]);
