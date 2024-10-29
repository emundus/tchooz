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
        'L' => 'DD/MM/YY',
    ],
    'months' => ['jenuari', 'febuari', 'mas', 'epril', 'mei', 'jun', 'julai', 'ögus', 'septemba', 'öktoba', 'nöwemba', 'diksemba'],
    'months_short' => ['jen', 'feb', 'mas', 'epr', 'mei', 'jun', 'jul', 'ögu', 'sep', 'ökt', 'nöw', 'dis'],
    'weekdays' => ['sönda', 'mönda', 'sinda', 'mitiwö', 'sogipbono', 'nenggo', 'söndanggie'],
    'weekdays_short' => ['sön', 'mön', 'sin', 'mit', 'soi', 'nen', 'sab'],
    'weekdays_min' => ['sön', 'mön', 'sin', 'mit', 'soi', 'nen', 'sab'],
    'day_of_first_week_of_year' => 1,
]);
