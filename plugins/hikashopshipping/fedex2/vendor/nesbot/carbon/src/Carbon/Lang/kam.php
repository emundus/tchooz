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
    'meridiem' => ['Ĩyakwakya', 'Ĩyawĩoo'],
    'weekdays' => ['Wa kyumwa', 'Wa kwambĩlĩlya', 'Wa kelĩ', 'Wa katatũ', 'Wa kana', 'Wa katano', 'Wa thanthatũ'],
    'weekdays_short' => ['Wky', 'Wkw', 'Wkl', 'Wtũ', 'Wkn', 'Wtn', 'Wth'],
    'weekdays_min' => ['Wky', 'Wkw', 'Wkl', 'Wtũ', 'Wkn', 'Wtn', 'Wth'],
    'months' => ['Mwai wa mbee', 'Mwai wa kelĩ', 'Mwai wa katatũ', 'Mwai wa kana', 'Mwai wa katano', 'Mwai wa thanthatũ', 'Mwai wa muonza', 'Mwai wa nyaanya', 'Mwai wa kenda', 'Mwai wa ĩkumi', 'Mwai wa ĩkumi na ĩmwe', 'Mwai wa ĩkumi na ilĩ'],
    'months_short' => ['Mbe', 'Kel', 'Ktũ', 'Kan', 'Ktn', 'Tha', 'Moo', 'Nya', 'Knd', 'Ĩku', 'Ĩkm', 'Ĩkl'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],

]);
