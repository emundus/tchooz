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
    'meridiem' => ['mbaʼámbaʼ', 'ncwònzém'],
    'weekdays' => null,
    'weekdays_short' => ['lyɛʼɛ́ sẅíŋtè', 'mvfò lyɛ̌ʼ', 'mbɔ́ɔntè mvfò lyɛ̌ʼ', 'tsètsɛ̀ɛ lyɛ̌ʼ', 'mbɔ́ɔntè tsetsɛ̀ɛ lyɛ̌ʼ', 'mvfò màga lyɛ̌ʼ', 'màga lyɛ̌ʼ'],
    'weekdays_min' => null,
    'months' => null,
    'months_short' => ['saŋ tsetsɛ̀ɛ lùm', 'saŋ kàg ngwóŋ', 'saŋ lepyè shúm', 'saŋ cÿó', 'saŋ tsɛ̀ɛ cÿó', 'saŋ njÿoláʼ', 'saŋ tyɛ̀b tyɛ̀b mbʉ̀ŋ', 'saŋ mbʉ̀ŋ', 'saŋ ngwɔ̀ʼ mbÿɛ', 'saŋ tàŋa tsetsáʼ', 'saŋ mejwoŋó', 'saŋ lùm'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/yy',
        'LL' => 'D MMM, YYYY',
        'LLL' => '[lyɛ]̌ʼ d [na] MMMM, YYYY HH:mm',
        'LLLL' => 'dddd , [lyɛ]̌ʼ d [na] MMMM, YYYY HH:mm',
    ],
]);
