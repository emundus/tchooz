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


return array_replace_recursive(require __DIR__.'/sd.php', [
    'formats' => [
        'L' => 'D/M/YY',
    ],
    'months' => ['जनवरी', 'फबरवरी', 'मार्चि', 'अप्रेल', 'मे', 'जूनि', 'जूलाइ', 'आगस्टु', 'सेप्टेंबरू', 'आक्टूबरू', 'नवंबरू', 'ॾिसंबरू'],
    'months_short' => ['जनवरी', 'फबरवरी', 'मार्चि', 'अप्रेल', 'मे', 'जूनि', 'जूलाइ', 'आगस्टु', 'सेप्टेंबरू', 'आक्टूबरू', 'नवंबरू', 'ॾिसंबरू'],
    'weekdays' => ['आर्तवारू', 'सूमरू', 'मंगलू', 'ॿुधरू', 'विस्पति', 'जुमो', 'छंछस'],
    'weekdays_short' => ['आर्तवारू', 'सूमरू', 'मंगलू', 'ॿुधरू', 'विस्पति', 'जुमो', 'छंछस'],
    'weekdays_min' => ['आर्तवारू', 'सूमरू', 'मंगलू', 'ॿुधरू', 'विस्पति', 'जुमो', 'छंछस'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['म.पू.', 'म.नं.'],
]);
