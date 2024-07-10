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
        'L' => 'DD/MM/YY',
    ],
    'months' => ['iniru', 'phiwriru', 'marsu', 'awril', 'mayu', 'huniyu', 'huliyu', 'agustu', 'siptiyimri', 'uktuwri', 'nuwiyimri', 'tisiyimri'],
    'months_short' => ['ini', 'phi', 'mar', 'awr', 'may', 'hun', 'hul', 'agu', 'sip', 'ukt', 'nuw', 'tis'],
    'weekdays' => ['tuminku', 'lunis', 'martis', 'miyirkulis', 'juywis', 'wiyirnis', 'sawatu'],
    'weekdays_short' => ['tum', 'lun', 'mar', 'miy', 'juy', 'wiy', 'saw'],
    'weekdays_min' => ['tum', 'lun', 'mar', 'miy', 'juy', 'wiy', 'saw'],
    'day_of_first_week_of_year' => 1,

    'minute' => ':count uchuy', // less reliable
    'min' => ':count uchuy', // less reliable
    'a_minute' => ':count uchuy', // less reliable

    'year' => ':count wata',
    'y' => ':count wata',
    'a_year' => ':count wata',

    'month' => ':count killa',
    'm' => ':count killa',
    'a_month' => ':count killa',

    'week' => ':count simana',
    'w' => ':count simana',
    'a_week' => ':count simana',

    'day' => ':count pʼunchaw',
    'd' => ':count pʼunchaw',
    'a_day' => ':count pʼunchaw',

    'hour' => ':count ura',
    'h' => ':count ura',
    'a_hour' => ':count ura',

    'second' => ':count iskay ñiqin',
    's' => ':count iskay ñiqin',
    'a_second' => ':count iskay ñiqin',
]);
