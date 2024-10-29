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
    'meridiem' => ['sárúwá', 'cɛɛ́nko'],
    'weekdays' => ['sɔ́ndǝ', 'lǝndí', 'maadí', 'mɛkrɛdí', 'jǝǝdí', 'júmbá', 'samdí'],
    'weekdays_short' => ['sɔ́n', 'lǝn', 'maa', 'mɛk', 'jǝǝ', 'júm', 'sam'],
    'weekdays_min' => ['sɔ́n', 'lǝn', 'maa', 'mɛk', 'jǝǝ', 'júm', 'sam'],
    'months' => ['ŋwíí a ntɔ́ntɔ', 'ŋwíí akǝ bɛ́ɛ', 'ŋwíí akǝ ráá', 'ŋwíí akǝ nin', 'ŋwíí akǝ táan', 'ŋwíí akǝ táafɔk', 'ŋwíí akǝ táabɛɛ', 'ŋwíí akǝ táaraa', 'ŋwíí akǝ táanin', 'ŋwíí akǝ ntɛk', 'ŋwíí akǝ ntɛk di bɔ́k', 'ŋwíí akǝ ntɛk di bɛ́ɛ'],
    'months_short' => ['ŋ1', 'ŋ2', 'ŋ3', 'ŋ4', 'ŋ5', 'ŋ6', 'ŋ7', 'ŋ8', 'ŋ9', 'ŋ10', 'ŋ11', 'ŋ12'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],
]);
