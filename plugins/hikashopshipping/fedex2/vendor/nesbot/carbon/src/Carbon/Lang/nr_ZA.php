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
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['Janabari', 'uFeberbari', 'uMatjhi', 'u-Apreli', 'Meyi', 'Juni', 'Julayi', 'Arhostosi', 'Septemba', 'Oktoba', 'Usinyikhaba', 'Disemba'],
    'months_short' => ['Jan', 'Feb', 'Mat', 'Apr', 'Mey', 'Jun', 'Jul', 'Arh', 'Sep', 'Okt', 'Usi', 'Dis'],
    'weekdays' => ['uSonto', 'uMvulo', 'uLesibili', 'lesithathu', 'uLesine', 'ngoLesihlanu', 'umGqibelo'],
    'weekdays_short' => ['Son', 'Mvu', 'Bil', 'Tha', 'Ne', 'Hla', 'Gqi'],
    'weekdays_min' => ['Son', 'Mvu', 'Bil', 'Tha', 'Ne', 'Hla', 'Gqi'],
    'day_of_first_week_of_year' => 1,
]);
