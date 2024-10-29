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


return array_replace_recursive(require __DIR__.'/ru.php', [
    'weekdays' => ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'],
    'weekdays_short' => ['вск', 'пнд', 'вто', 'срд', 'чтв', 'птн', 'суб'],
    'weekdays_min' => ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'су'],
]);
