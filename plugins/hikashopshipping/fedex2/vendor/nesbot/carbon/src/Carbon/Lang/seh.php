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
    'weekdays' => ['Dimingu', 'Chiposi', 'Chipiri', 'Chitatu', 'Chinai', 'Chishanu', 'Sabudu'],
    'weekdays_short' => ['Dim', 'Pos', 'Pir', 'Tat', 'Nai', 'Sha', 'Sab'],
    'weekdays_min' => ['Dim', 'Pos', 'Pir', 'Tat', 'Nai', 'Sha', 'Sab'],
    'months' => ['Janeiro', 'Fevreiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Augusto', 'Setembro', 'Otubro', 'Novembro', 'Decembro'],
    'months_short' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Aug', 'Set', 'Otu', 'Nov', 'Dec'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'd [de] MMM [de] YYYY',
        'LLL' => 'd [de] MMMM [de] YYYY HH:mm',
        'LLLL' => 'dddd, d [de] MMMM [de] YYYY HH:mm',
    ],
]);
