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


return [
    'year' => ':count ປີ',
    'y' => ':count ປີ',
    'month' => ':count ເດືອນ',
    'm' => ':count ດ. ',
    'week' => ':count ອາທິດ',
    'w' => ':count ອທ. ',
    'day' => ':count ມື້',
    'd' => ':count ມື້',
    'hour' => ':count ຊົ່ວໂມງ',
    'h' => ':count ຊມ. ',
    'minute' => ':count ນາທີ',
    'min' => ':count ນທ. ',
    'second' => '{1}ບໍ່ເທົ່າໃດວິນາທີ|]1,Inf[:count ວິນາທີ',
    's' => ':count ວິ. ',
    'ago' => ':timeຜ່ານມາ',
    'from_now' => 'ອີກ :time',
    'diff_now' => 'ຕອນນີ້',
    'diff_today' => 'ມື້ນີ້ເວລາ',
    'diff_yesterday' => 'ມື້ວານນີ້ເວລາ',
    'diff_tomorrow' => 'ມື້ອື່ນເວລາ',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'ວັນdddd D MMMM YYYY HH:mm',
    ],
    'calendar' => [
        'sameDay' => '[ມື້ນີ້ເວລາ] LT',
        'nextDay' => '[ມື້ອື່ນເວລາ] LT',
        'nextWeek' => '[ວັນ]dddd[ໜ້າເວລາ] LT',
        'lastDay' => '[ມື້ວານນີ້ເວລາ] LT',
        'lastWeek' => '[ວັນ]dddd[ແລ້ວນີ້ເວລາ] LT',
        'sameElse' => 'L',
    ],
    'ordinal' => 'ທີ່:number',
    'meridiem' => ['ຕອນເຊົ້າ', 'ຕອນແລງ'],
    'months' => ['ມັງກອນ', 'ກຸມພາ', 'ມີນາ', 'ເມສາ', 'ພຶດສະພາ', 'ມິຖຸນາ', 'ກໍລະກົດ', 'ສິງຫາ', 'ກັນຍາ', 'ຕຸລາ', 'ພະຈິກ', 'ທັນວາ'],
    'months_short' => ['ມັງກອນ', 'ກຸມພາ', 'ມີນາ', 'ເມສາ', 'ພຶດສະພາ', 'ມິຖຸນາ', 'ກໍລະກົດ', 'ສິງຫາ', 'ກັນຍາ', 'ຕຸລາ', 'ພະຈິກ', 'ທັນວາ'],
    'weekdays' => ['ອາທິດ', 'ຈັນ', 'ອັງຄານ', 'ພຸດ', 'ພະຫັດ', 'ສຸກ', 'ເສົາ'],
    'weekdays_short' => ['ທິດ', 'ຈັນ', 'ອັງຄານ', 'ພຸດ', 'ພະຫັດ', 'ສຸກ', 'ເສົາ'],
    'weekdays_min' => ['ທ', 'ຈ', 'ອຄ', 'ພ', 'ພຫ', 'ສກ', 'ສ'],
    'list' => [', ', 'ແລະ '],
];
