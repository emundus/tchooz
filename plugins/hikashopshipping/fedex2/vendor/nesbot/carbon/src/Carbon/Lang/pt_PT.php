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


return array_replace_recursive(require __DIR__.'/pt.php', [
    'formats' => [
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'],
    'months_short' => ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'],
    'weekdays' => ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'],
    'weekdays_short' => ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'],
    'weekdays_min' => ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
]);
