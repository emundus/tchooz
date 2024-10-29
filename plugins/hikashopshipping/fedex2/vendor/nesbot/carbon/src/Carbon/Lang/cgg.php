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
    'weekdays' => ['Sande', 'Orwokubanza', 'Orwakabiri', 'Orwakashatu', 'Orwakana', 'Orwakataano', 'Orwamukaaga'],
    'weekdays_short' => ['SAN', 'ORK', 'OKB', 'OKS', 'OKN', 'OKT', 'OMK'],
    'weekdays_min' => ['SAN', 'ORK', 'OKB', 'OKS', 'OKN', 'OKT', 'OMK'],
    'months' => ['Okwokubanza', 'Okwakabiri', 'Okwakashatu', 'Okwakana', 'Okwakataana', 'Okwamukaaga', 'Okwamushanju', 'Okwamunaana', 'Okwamwenda', 'Okwaikumi', 'Okwaikumi na kumwe', 'Okwaikumi na ibiri'],
    'months_short' => ['KBZ', 'KBR', 'KST', 'KKN', 'KTN', 'KMK', 'KMS', 'KMN', 'KMW', 'KKM', 'KNK', 'KNB'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],

    'day' => ':count ruhanga', // less reliable
    'd' => ':count ruhanga', // less reliable
    'a_day' => ':count ruhanga', // less reliable
]);
