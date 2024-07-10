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


return array_replace_recursive(require __DIR__.'/zh_HK.php', [
    'formats' => [
        'L' => 'YYYY年MM月DD日 dddd',
    ],
    'months' => ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
    'months_short' => ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
    'weekdays' => ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'],
    'weekdays_short' => ['日', '一', '二', '三', '四', '五', '六'],
    'weekdays_min' => ['日', '一', '二', '三', '四', '五', '六'],
    'first_day_of_week' => 0,
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['上午', '下午'],
]);
