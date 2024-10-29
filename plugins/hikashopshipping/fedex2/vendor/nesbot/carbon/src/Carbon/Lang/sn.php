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
    'meridiem' => ['a', 'p'],
    'weekdays' => ['Svondo', 'Muvhuro', 'Chipiri', 'Chitatu', 'China', 'Chishanu', 'Mugovera'],
    'weekdays_short' => ['Svo', 'Muv', 'Chp', 'Cht', 'Chn', 'Chs', 'Mug'],
    'weekdays_min' => ['Sv', 'Mu', 'Cp', 'Ct', 'Cn', 'Cs', 'Mg'],
    'months' => ['Ndira', 'Kukadzi', 'Kurume', 'Kubvumbi', 'Chivabvu', 'Chikumi', 'Chikunguru', 'Nyamavhuvhu', 'Gunyana', 'Gumiguru', 'Mbudzi', 'Zvita'],
    'months_short' => ['Ndi', 'Kuk', 'Kur', 'Kub', 'Chv', 'Chk', 'Chg', 'Nya', 'Gun', 'Gum', 'Mbu', 'Zvi'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'YYYY-MM-dd',
        'LL' => 'YYYY MMM D',
        'LLL' => 'YYYY MMMM D HH:mm',
        'LLLL' => 'YYYY MMMM D, dddd HH:mm',
    ],

    'year' => 'makore :count',
    'y' => 'makore :count',
    'a_year' => 'makore :count',

    'month' => 'mwedzi :count',
    'm' => 'mwedzi :count',
    'a_month' => 'mwedzi :count',

    'week' => 'vhiki :count',
    'w' => 'vhiki :count',
    'a_week' => 'vhiki :count',

    'day' => 'mazuva :count',
    'd' => 'mazuva :count',
    'a_day' => 'mazuva :count',

    'hour' => 'maawa :count',
    'h' => 'maawa :count',
    'a_hour' => 'maawa :count',

    'minute' => 'minitsi :count',
    'min' => 'minitsi :count',
    'a_minute' => 'minitsi :count',

    'second' => 'sekonzi :count',
    's' => 'sekonzi :count',
    'a_second' => 'sekonzi :count',
]);
