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
        'L' => 'YYYY年MM月DD號',
    ],
    'months' => ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
    'months_short' => [' 1月', ' 2月', ' 3月', ' 4月', ' 5月', ' 6月', ' 7月', ' 8月', ' 9月', '10月', '11月', '12月'],
    'weekdays' => ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'],
    'weekdays_short' => ['日', '一', '二', '三', '四', '五', '六'],
    'weekdays_min' => ['日', '一', '二', '三', '四', '五', '六'],
    'meridiem' => ['上午', '下午'],

    'year' => ':count 年',
    'y' => ':count 年',
    'a_year' => ':count 年',

    'month' => ':count 月',
    'm' => ':count 月',
    'a_month' => ':count 月',

    'week' => ':count 周',
    'w' => ':count 周',
    'a_week' => ':count 周',

    'day' => ':count 白天',
    'd' => ':count 白天',
    'a_day' => ':count 白天',

    'hour' => ':count 小时',
    'h' => ':count 小时',
    'a_hour' => ':count 小时',

    'minute' => ':count 分钟',
    'min' => ':count 分钟',
    'a_minute' => ':count 分钟',

    'second' => ':count 秒',
    's' => ':count 秒',
    'a_second' => ':count 秒',
]);
