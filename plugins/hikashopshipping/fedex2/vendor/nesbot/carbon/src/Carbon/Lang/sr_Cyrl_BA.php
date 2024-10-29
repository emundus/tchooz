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


use Symfony\Component\Translation\PluralizationRules;

if (class_exists(PluralizationRules::class)) {
    PluralizationRules::set(static function ($number) {
        return PluralizationRules::get($number, 'sr');
    }, 'sr_Cyrl_BA');
}

return array_replace_recursive(require __DIR__.'/sr_Cyrl.php', [
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D.M.yy.',
        'LL' => 'DD.MM.YYYY.',
        'LLL' => 'DD. MMMM YYYY. HH:mm',
        'LLLL' => 'dddd, DD. MMMM YYYY. HH:mm',
    ],
    'weekdays' => ['недјеља', 'понедељак', 'уторак', 'сриједа', 'четвртак', 'петак', 'субота'],
    'weekdays_short' => ['нед.', 'пон.', 'ут.', 'ср.', 'чет.', 'пет.', 'суб.'],
]);
