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
    'meridiem' => ['KI', 'UT'],
    'weekdays' => ['Kiumia', 'Njumatatu', 'Njumaine', 'Njumatano', 'Aramithi', 'Njumaa', 'NJumamothii'],
    'weekdays_short' => ['Kma', 'Tat', 'Ine', 'Tan', 'Arm', 'Maa', 'NMM'],
    'weekdays_min' => ['Kma', 'Tat', 'Ine', 'Tan', 'Arm', 'Maa', 'NMM'],
    'months' => ['Mweri wa mbere', 'Mweri wa kaĩri', 'Mweri wa kathatũ', 'Mweri wa kana', 'Mweri wa gatano', 'Mweri wa gatantatũ', 'Mweri wa mũgwanja', 'Mweri wa kanana', 'Mweri wa kenda', 'Mweri wa ikũmi', 'Mweri wa ikũmi na ũmwe', 'Mweri wa ikũmi na Kaĩrĩ'],
    'months_short' => ['Mbe', 'Kai', 'Kat', 'Kan', 'Gat', 'Gan', 'Mug', 'Knn', 'Ken', 'Iku', 'Imw', 'Igi'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],
]);
