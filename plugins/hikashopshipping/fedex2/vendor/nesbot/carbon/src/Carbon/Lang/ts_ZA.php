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
    'months' => ['Sunguti', 'Nyenyenyani', 'Nyenyankulu', 'Dzivamisoko', 'Mudyaxihi', 'Khotavuxika', 'Mawuwani', 'Mhawuri', 'Ndzhati', 'Nhlangula', 'Hukuri', 'N\'wendzamhala'],
    'months_short' => ['Sun', 'Yan', 'Kul', 'Dzi', 'Mud', 'Kho', 'Maw', 'Mha', 'Ndz', 'Nhl', 'Huk', 'N\'w'],
    'weekdays' => ['Sonto', 'Musumbhunuku', 'Ravumbirhi', 'Ravunharhu', 'Ravumune', 'Ravuntlhanu', 'Mugqivela'],
    'weekdays_short' => ['Son', 'Mus', 'Bir', 'Har', 'Ne', 'Tlh', 'Mug'],
    'weekdays_min' => ['Son', 'Mus', 'Bir', 'Har', 'Ne', 'Tlh', 'Mug'],
    'day_of_first_week_of_year' => 1,

    'year' => 'malembe ya :count',
    'y' => 'malembe ya :count',
    'a_year' => 'malembe ya :count',

    'month' => 'tin’hweti ta :count',
    'm' => 'tin’hweti ta :count',
    'a_month' => 'tin’hweti ta :count',

    'week' => 'mavhiki ya :count',
    'w' => 'mavhiki ya :count',
    'a_week' => 'mavhiki ya :count',

    'day' => 'masiku :count',
    'd' => 'masiku :count',
    'a_day' => 'masiku :count',

    'hour' => 'tiawara ta :count',
    'h' => 'tiawara ta :count',
    'a_hour' => 'tiawara ta :count',

    'minute' => 'timinete ta :count',
    'min' => 'timinete ta :count',
    'a_minute' => 'timinete ta :count',

    'second' => 'tisekoni ta :count',
    's' => 'tisekoni ta :count',
    'a_second' => 'tisekoni ta :count',
]);
