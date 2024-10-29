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


return array_replace_recursive(require __DIR__.'/shi.php', [
    'meridiem' => ['tifawt', 'tadggʷat'],
    'weekdays' => ['asamas', 'aynas', 'asinas', 'akṛas', 'akwas', 'asimwas', 'asiḍyas'],
    'weekdays_short' => ['asa', 'ayn', 'asi', 'akṛ', 'akw', 'asim', 'asiḍ'],
    'weekdays_min' => ['asa', 'ayn', 'asi', 'akṛ', 'akw', 'asim', 'asiḍ'],
    'months' => ['innayr', 'bṛayṛ', 'maṛṣ', 'ibrir', 'mayyu', 'yunyu', 'yulyuz', 'ɣuct', 'cutanbir', 'ktubr', 'nuwanbir', 'dujanbir'],
    'months_short' => ['inn', 'bṛa', 'maṛ', 'ibr', 'may', 'yun', 'yul', 'ɣuc', 'cut', 'ktu', 'nuw', 'duj'],
    'first_day_of_week' => 6,
    'weekend' => [5, 6],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM, YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],

    'minute' => ':count agur', // less reliable
    'min' => ':count agur', // less reliable
    'a_minute' => ':count agur', // less reliable
]);
