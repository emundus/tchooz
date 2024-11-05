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


return array_replace_recursive(require __DIR__.'/uz_Latn.php', [
    'formats' => [
        'L' => 'DD/MM/YY',
    ],
    'months' => ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'],
    'months_short' => ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'Iyn', 'Iyl', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek'],
    'weekdays' => ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'],
    'weekdays_short' => ['Yak', 'Du', 'Se', 'Cho', 'Pay', 'Ju', 'Sha'],
    'weekdays_min' => ['Yak', 'Du', 'Se', 'Cho', 'Pay', 'Ju', 'Sha'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
]);
