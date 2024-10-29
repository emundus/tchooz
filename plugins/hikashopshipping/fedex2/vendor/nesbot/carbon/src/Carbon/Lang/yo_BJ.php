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


return array_replace_recursive(require __DIR__.'/yo.php', [
    'meridiem' => ['Àárɔ̀', 'Ɔ̀sán'],
    'weekdays' => ['Ɔjɔ́ Àìkú', 'Ɔjɔ́ Ajé', 'Ɔjɔ́ Ìsɛ́gun', 'Ɔjɔ́rú', 'Ɔjɔ́bɔ', 'Ɔjɔ́ Ɛtì', 'Ɔjɔ́ Àbámɛ́ta'],
    'weekdays_short' => ['Àìkú', 'Ajé', 'Ìsɛ́gun', 'Ɔjɔ́rú', 'Ɔjɔ́bɔ', 'Ɛtì', 'Àbámɛ́ta'],
    'weekdays_min' => ['Àìkú', 'Ajé', 'Ìsɛ́gun', 'Ɔjɔ́rú', 'Ɔjɔ́bɔ', 'Ɛtì', 'Àbámɛ́ta'],
    'months' => ['Oshù Shɛ́rɛ́', 'Oshù Èrèlè', 'Oshù Ɛrɛ̀nà', 'Oshù Ìgbé', 'Oshù Ɛ̀bibi', 'Oshù Òkúdu', 'Oshù Agɛmɔ', 'Oshù Ògún', 'Oshù Owewe', 'Oshù Ɔ̀wàrà', 'Oshù Bélú', 'Oshù Ɔ̀pɛ̀'],
    'months_short' => ['Shɛ́rɛ́', 'Èrèlè', 'Ɛrɛ̀nà', 'Ìgbé', 'Ɛ̀bibi', 'Òkúdu', 'Agɛmɔ', 'Ògún', 'Owewe', 'Ɔ̀wàrà', 'Bélú', 'Ɔ̀pɛ̀'],
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
