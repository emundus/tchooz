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
    'weekdays' => ['Dimas', 'Teneŋ', 'Talata', 'Alarbay', 'Aramisay', 'Arjuma', 'Sibiti'],
    'weekdays_short' => ['Dim', 'Ten', 'Tal', 'Ala', 'Ara', 'Arj', 'Sib'],
    'weekdays_min' => ['Dim', 'Ten', 'Tal', 'Ala', 'Ara', 'Arj', 'Sib'],
    'months' => ['Sanvie', 'Fébirie', 'Mars', 'Aburil', 'Mee', 'Sueŋ', 'Súuyee', 'Ut', 'Settembar', 'Oktobar', 'Novembar', 'Disambar'],
    'months_short' => ['Sa', 'Fe', 'Ma', 'Ab', 'Me', 'Su', 'Sú', 'Ut', 'Se', 'Ok', 'No', 'De'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],
]);
