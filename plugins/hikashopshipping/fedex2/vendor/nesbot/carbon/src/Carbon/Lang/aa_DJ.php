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
        'L' => 'DD.MM.YYYY',
    ],
    'months' => ['Qunxa Garablu', 'Kudo', 'Ciggilta Kudo', 'Agda Baxisso', 'Caxah Alsa', 'Qasa Dirri', 'Qado Dirri', 'Liiqen', 'Waysu', 'Diteli', 'Ximoli', 'Kaxxa Garablu'],
    'months_short' => ['qun', 'nah', 'cig', 'agd', 'cax', 'qas', 'qad', 'leq', 'way', 'dit', 'xim', 'kax'],
    'weekdays' => ['Acaada', 'Etleeni', 'Talaata', 'Arbaqa', 'Kamiisi', 'Gumqata', 'Sabti'],
    'weekdays_short' => ['aca', 'etl', 'tal', 'arb', 'kam', 'gum', 'sab'],
    'weekdays_min' => ['aca', 'etl', 'tal', 'arb', 'kam', 'gum', 'sab'],
    'first_day_of_week' => 6,
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['saaku', 'carra'],

    'year' => ':count gaqambo', // less reliable
    'y' => ':count gaqambo', // less reliable
    'a_year' => ':count gaqambo', // less reliable

    'month' => ':count àlsa',
    'm' => ':count àlsa',
    'a_month' => ':count àlsa',

    'day' => ':count saaku', // less reliable
    'd' => ':count saaku', // less reliable
    'a_day' => ':count saaku', // less reliable

    'hour' => ':count ayti', // less reliable
    'h' => ':count ayti', // less reliable
    'a_hour' => ':count ayti', // less reliable
]);
