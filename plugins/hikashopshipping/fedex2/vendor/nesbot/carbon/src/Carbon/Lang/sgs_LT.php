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
        'L' => 'YYYY.MM.DD',
    ],
    'months' => ['sausė', 'vasarė', 'kuova', 'balondė', 'gegožės', 'bėrželė', 'lëpas', 'rogpjūtė', 'siejės', 'spalė', 'lapkrėstė', 'grůdė'],
    'months_short' => ['Sau', 'Vas', 'Kuo', 'Bal', 'Geg', 'Bėr', 'Lëp', 'Rgp', 'Sie', 'Spa', 'Lap', 'Grd'],
    'weekdays' => ['nedielės dëna', 'panedielis', 'oterninks', 'sereda', 'četvergs', 'petnīčė', 'sobata'],
    'weekdays_short' => ['Nd', 'Pn', 'Ot', 'Sr', 'Čt', 'Pt', 'Sb'],
    'weekdays_min' => ['Nd', 'Pn', 'Ot', 'Sr', 'Čt', 'Pt', 'Sb'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,

    'minute' => ':count mažos', // less reliable
    'min' => ':count mažos', // less reliable
    'a_minute' => ':count mažos', // less reliable

    'year' => ':count metā',
    'y' => ':count metā',
    'a_year' => ':count metā',

    'month' => ':count mienou',
    'm' => ':count mienou',
    'a_month' => ':count mienou',

    'week' => ':count nedielė',
    'w' => ':count nedielė',
    'a_week' => ':count nedielė',

    'day' => ':count dīna',
    'd' => ':count dīna',
    'a_day' => ':count dīna',

    'hour' => ':count adīna',
    'h' => ':count adīna',
    'a_hour' => ':count adīna',

    'second' => ':count Sekondė',
    's' => ':count Sekondė',
    'a_second' => ':count Sekondė',
]);
