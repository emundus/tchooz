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
    'months' => ['Pherekgong', 'Hlakola', 'Tlhakubele', 'Mmese', 'Motsheanong', 'Phupjane', 'Phupu', 'Phato', 'Leotse', 'Mphalane', 'Pudungwana', 'Tshitwe'],
    'months_short' => ['Phe', 'Hla', 'TlH', 'Mme', 'Mot', 'Jan', 'Upu', 'Pha', 'Leo', 'Mph', 'Pud', 'Tsh'],
    'weekdays' => ['Sontaha', 'Mantaha', 'Labobedi', 'Laboraro', 'Labone', 'Labohlano', 'Moqebelo'],
    'weekdays_short' => ['Son', 'Mma', 'Bed', 'Rar', 'Ne', 'Hla', 'Moq'],
    'weekdays_min' => ['Son', 'Mma', 'Bed', 'Rar', 'Ne', 'Hla', 'Moq'],
    'day_of_first_week_of_year' => 1,

    'week' => ':count Sontaha', // less reliable
    'w' => ':count Sontaha', // less reliable
    'a_week' => ':count Sontaha', // less reliable

    'day' => ':count letsatsi', // less reliable
    'd' => ':count letsatsi', // less reliable
    'a_day' => ':count letsatsi', // less reliable

    'hour' => ':count sešupanako', // less reliable
    'h' => ':count sešupanako', // less reliable
    'a_hour' => ':count sešupanako', // less reliable

    'minute' => ':count menyane', // less reliable
    'min' => ':count menyane', // less reliable
    'a_minute' => ':count menyane', // less reliable

    'second' => ':count thusa', // less reliable
    's' => ':count thusa', // less reliable
    'a_second' => ':count thusa', // less reliable

    'year' => ':count selemo',
    'y' => ':count selemo',
    'a_year' => ':count selemo',

    'month' => ':count kgwedi',
    'm' => ':count kgwedi',
    'a_month' => ':count kgwedi',
]);
