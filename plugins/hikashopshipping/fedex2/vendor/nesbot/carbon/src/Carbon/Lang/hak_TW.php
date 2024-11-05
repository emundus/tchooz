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
        'L' => 'YYYY年MM月DD日',
    ],
    'months' => ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
    'months_short' => [' 1月', ' 2月', ' 3月', ' 4月', ' 5月', ' 6月', ' 7月', ' 8月', ' 9月', '10月', '11月', '12月'],
    'weekdays' => ['禮拜日', '禮拜一', '禮拜二', '禮拜三', '禮拜四', '禮拜五', '禮拜六'],
    'weekdays_short' => ['日', '一', '二', '三', '四', '五', '六'],
    'weekdays_min' => ['日', '一', '二', '三', '四', '五', '六'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['上晝', '下晝'],

    'year' => ':count ngien11',
    'y' => ':count ngien11',
    'a_year' => ':count ngien11',

    'month' => ':count ngie̍t',
    'm' => ':count ngie̍t',
    'a_month' => ':count ngie̍t',

    'week' => ':count lî-pai',
    'w' => ':count lî-pai',
    'a_week' => ':count lî-pai',

    'day' => ':count ngit',
    'd' => ':count ngit',
    'a_day' => ':count ngit',

    'hour' => ':count sṳ̀',
    'h' => ':count sṳ̀',
    'a_hour' => ':count sṳ̀',

    'minute' => ':count fûn',
    'min' => ':count fûn',
    'a_minute' => ':count fûn',

    'second' => ':count miéu',
    's' => ':count miéu',
    'a_second' => ':count miéu',
]);
