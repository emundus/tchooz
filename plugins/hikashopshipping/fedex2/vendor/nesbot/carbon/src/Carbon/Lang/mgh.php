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
    'meridiem' => ['wichishu', 'mchochil’l'],
    'weekdays' => ['Sabato', 'Jumatatu', 'Jumanne', 'Jumatano', 'Arahamisi', 'Ijumaa', 'Jumamosi'],
    'weekdays_short' => ['Sab', 'Jtt', 'Jnn', 'Jtn', 'Ara', 'Iju', 'Jmo'],
    'weekdays_min' => ['Sab', 'Jtt', 'Jnn', 'Jtn', 'Ara', 'Iju', 'Jmo'],
    'months' => ['Mweri wo kwanza', 'Mweri wo unayeli', 'Mweri wo uneraru', 'Mweri wo unecheshe', 'Mweri wo unethanu', 'Mweri wo thanu na mocha', 'Mweri wo saba', 'Mweri wo nane', 'Mweri wo tisa', 'Mweri wo kumi', 'Mweri wo kumi na moja', 'Mweri wo kumi na yel’li'],
    'months_short' => ['Kwa', 'Una', 'Rar', 'Che', 'Tha', 'Moc', 'Sab', 'Nan', 'Tis', 'Kum', 'Moj', 'Yel'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],
]);
