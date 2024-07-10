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
    'months' => ['Jaunuwoa', 'Februwoa', 'Moaz', 'Aprell', 'Mai', 'Juni', 'Juli', 'August', 'Septamba', 'Oktoba', 'Nowamba', 'Dezamba'],
    'months_short' => ['Jan', 'Feb', 'Moz', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Now', 'Dez'],
    'weekdays' => ['Sinndag', 'Mondag', 'Dingsdag', 'Meddwäakj', 'Donnadag', 'Friedag', 'Sinnowend'],
    'weekdays_short' => ['Sdg', 'Mdg', 'Dsg', 'Mwk', 'Ddg', 'Fdg', 'Swd'],
    'weekdays_min' => ['Sdg', 'Mdg', 'Dsg', 'Mwk', 'Ddg', 'Fdg', 'Swd'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 4,
]);
