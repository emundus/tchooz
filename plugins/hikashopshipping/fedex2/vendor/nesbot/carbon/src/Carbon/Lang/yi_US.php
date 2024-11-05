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
    'months' => ['יאַנואַר', 'פֿעברואַר', 'מערץ', 'אַפּריל', 'מיי', 'יוני', 'יולי', 'אויגוסט', 'סעפּטעמבער', 'אקטאבער', 'נאוועמבער', 'דעצעמבער'],
    'months_short' => ['יאַנ', 'פֿעב', 'מאַר', 'אַפּר', 'מײַ ', 'יונ', 'יול', 'אױג', 'סעפּ', 'אָקט', 'נאָװ', 'דעצ'],
    'weekdays' => ['זונטיק', 'מאָנטיק', 'דינסטיק', 'מיטװאָך', 'דאָנערשטיק', 'פֿרײַטיק', 'שבת'],
    'weekdays_short' => ['זונ\'', 'מאָנ\'', 'דינ\'', 'מיט\'', 'דאָנ\'', 'פֿרײַ\'', 'שבת'],
    'weekdays_min' => ['זונ\'', 'מאָנ\'', 'דינ\'', 'מיט\'', 'דאָנ\'', 'פֿרײַ\'', 'שבת'],
    'day_of_first_week_of_year' => 1,

    'year' => ':count יאר',
    'y' => ':count יאר',
    'a_year' => ':count יאר',

    'month' => ':count חודש',
    'm' => ':count חודש',
    'a_month' => ':count חודש',

    'week' => ':count וואָך',
    'w' => ':count וואָך',
    'a_week' => ':count וואָך',

    'day' => ':count טאָג',
    'd' => ':count טאָג',
    'a_day' => ':count טאָג',

    'hour' => ':count שעה',
    'h' => ':count שעה',
    'a_hour' => ':count שעה',

    'minute' => ':count מינוט',
    'min' => ':count מינוט',
    'a_minute' => ':count מינוט',

    'second' => ':count סעקונדע',
    's' => ':count סעקונדע',
    'a_second' => ':count סעקונדע',
]);
