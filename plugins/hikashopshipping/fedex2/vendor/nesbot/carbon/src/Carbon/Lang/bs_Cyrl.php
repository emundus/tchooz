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


return array_replace_recursive(require __DIR__.'/bs.php', [
    'meridiem' => ['пре подне', 'поподне'],
    'weekdays' => ['недјеља', 'понедјељак', 'уторак', 'сриједа', 'четвртак', 'петак', 'субота'],
    'weekdays_short' => ['нед', 'пон', 'уто', 'сри', 'чет', 'пет', 'суб'],
    'weekdays_min' => ['нед', 'пон', 'уто', 'сри', 'чет', 'пет', 'суб'],
    'months' => ['јануар', 'фебруар', 'март', 'април', 'мај', 'јуни', 'јули', 'аугуст', 'септембар', 'октобар', 'новембар', 'децембар'],
    'months_short' => ['јан', 'феб', 'мар', 'апр', 'мај', 'јун', 'јул', 'ауг', 'сеп', 'окт', 'нов', 'дец'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D.M.YYYY.',
        'LL' => 'DD.MM.YYYY.',
        'LLL' => 'DD. MMMM YYYY. HH:mm',
        'LLLL' => 'dddd, DD. MMMM YYYY. HH:mm',
    ],
]);
