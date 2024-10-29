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
    'weekdays' => ['ꕞꕌꔵ', 'ꗳꗡꘉ', 'ꕚꕞꕚ', 'ꕉꕞꕒ', 'ꕉꔤꕆꕢ', 'ꕉꔤꕀꕮ', 'ꔻꔬꔳ'],
    'weekdays_short' => ['ꕞꕌꔵ', 'ꗳꗡꘉ', 'ꕚꕞꕚ', 'ꕉꕞꕒ', 'ꕉꔤꕆꕢ', 'ꕉꔤꕀꕮ', 'ꔻꔬꔳ'],
    'weekdays_min' => ['ꕞꕌꔵ', 'ꗳꗡꘉ', 'ꕚꕞꕚ', 'ꕉꕞꕒ', 'ꕉꔤꕆꕢ', 'ꕉꔤꕀꕮ', 'ꔻꔬꔳ'],
    'months' => ['ꖨꖕ ꕪꕴ ꔞꔀꕮꕊ', 'ꕒꕡꖝꖕ', 'ꕾꖺ', 'ꖢꖕ', 'ꖑꕱ', 'ꖱꘋ', 'ꖱꕞꔤ', 'ꗛꔕ', 'ꕢꕌ', 'ꕭꖃ', 'ꔞꘋꕔꕿ ꕸꖃꗏ', 'ꖨꖕ ꕪꕴ ꗏꖺꕮꕊ'],
    'months_short' => ['ꖨꖕꔞ', 'ꕒꕡ', 'ꕾꖺ', 'ꖢꖕ', 'ꖑꕱ', 'ꖱꘋ', 'ꖱꕞ', 'ꗛꔕ', 'ꕢꕌ', 'ꕭꖃ', 'ꔞꘋ', 'ꖨꖕꗏ'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'h:mm a',
        'LTS' => 'h:mm:ss a',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY h:mm a',
        'LLLL' => 'dddd, D MMMM YYYY h:mm a',
    ],

    'year' => ':count ꕀ', // less reliable
    'y' => ':count ꕀ', // less reliable
    'a_year' => ':count ꕀ', // less reliable

    'second' => ':count ꗱꕞꕯꕊ', // less reliable
    's' => ':count ꗱꕞꕯꕊ', // less reliable
    'a_second' => ':count ꗱꕞꕯꕊ', // less reliable
]);
