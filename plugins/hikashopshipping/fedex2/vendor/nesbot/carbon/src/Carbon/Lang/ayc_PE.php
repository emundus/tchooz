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
    'months' => ['inïru', 'phiwriru', 'marsu', 'awrila', 'mayu', 'junyu', 'julyu', 'awustu', 'sitimri', 'uktuwri', 'nuwimri', 'risimri'],
    'months_short' => ['ini', 'phi', 'mar', 'awr', 'may', 'jun', 'jul', 'awu', 'sit', 'ukt', 'nuw', 'ris'],
    'weekdays' => ['tuminku', 'lunisa', 'martisa', 'mirkulisa', 'juywisa', 'wirnisa', 'sawäru'],
    'weekdays_short' => ['tum', 'lun', 'mar', 'mir', 'juy', 'wir', 'saw'],
    'weekdays_min' => ['tum', 'lun', 'mar', 'mir', 'juy', 'wir', 'saw'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['VM', 'NM'],
]);
