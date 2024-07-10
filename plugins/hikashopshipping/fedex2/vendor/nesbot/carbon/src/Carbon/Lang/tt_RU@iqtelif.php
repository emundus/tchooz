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
        'L' => 'DD.MM.YYYY',
    ],
    'months' => ['Ğınwar', 'Fiwral\'', 'Mart', 'April', 'May', 'Yün', 'Yül', 'Awgust', 'Sintebír', 'Üktebír', 'Noyebír', 'Dikebír'],
    'months_short' => ['Ğın', 'Fiw', 'Mar', 'Apr', 'May', 'Yün', 'Yül', 'Awg', 'Sin', 'Ükt', 'Noy', 'Dik'],
    'weekdays' => ['Yekşembí', 'Düşembí', 'Sişembí', 'Çerşembí', 'Pencíşembí', 'Comğa', 'Şimbe'],
    'weekdays_short' => ['Yek', 'Düş', 'Siş', 'Çer', 'Pen', 'Com', 'Şim'],
    'weekdays_min' => ['Yek', 'Düş', 'Siş', 'Çer', 'Pen', 'Com', 'Şim'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['ÖA', 'ÖS'],
]);
