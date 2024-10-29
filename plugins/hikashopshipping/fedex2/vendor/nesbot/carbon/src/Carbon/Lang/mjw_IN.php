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
        'L' => 'D/M/YY',
    ],
    'months' => ['Arkoi', 'Thangthang', 'There', 'Jangmi', 'Aru', 'Vosik', 'Jakhong', 'Paipai', 'Chiti', 'Phere', 'Phaikuni', 'Matijong'],
    'months_short' => ['Ark', 'Thang', 'The', 'Jang', 'Aru', 'Vos', 'Jak', 'Pai', 'Chi', 'Phe', 'Phai', 'Mati'],
    'weekdays' => ['Bhomkuru', 'Urmi', 'Durmi', 'Thelang', 'Theman', 'Bhomta', 'Bhomti'],
    'weekdays_short' => ['Bhom', 'Ur', 'Dur', 'Tkel', 'Tkem', 'Bhta', 'Bhti'],
    'weekdays_min' => ['Bhom', 'Ur', 'Dur', 'Tkel', 'Tkem', 'Bhta', 'Bhti'],
    'first_day_of_week' => 0,
    'day_of_first_week_of_year' => 1,
]);
