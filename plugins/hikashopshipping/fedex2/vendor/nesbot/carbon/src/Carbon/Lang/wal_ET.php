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
    'months' => ['ጃንዩወሪ', 'ፌብሩወሪ', 'ማርች', 'ኤፕረል', 'ሜይ', 'ጁን', 'ጁላይ', 'ኦገስት', 'ሴፕቴምበር', 'ኦክተውበር', 'ኖቬምበር', 'ዲሴምበር'],
    'months_short' => ['ጃንዩ', 'ፌብሩ', 'ማርች', 'ኤፕረ', 'ሜይ ', 'ጁን ', 'ጁላይ', 'ኦገስ', 'ሴፕቴ', 'ኦክተ', 'ኖቬም', 'ዲሴም'],
    'weekdays' => ['ወጋ', 'ሳይኖ', 'ማቆሳኛ', 'አሩዋ', 'ሃሙሳ', 'አርባ', 'ቄራ'],
    'weekdays_short' => ['ወጋ ', 'ሳይኖ', 'ማቆሳ', 'አሩዋ', 'ሃሙሳ', 'አርባ', 'ቄራ '],
    'weekdays_min' => ['ወጋ ', 'ሳይኖ', 'ማቆሳ', 'አሩዋ', 'ሃሙሳ', 'አርባ', 'ቄራ '],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['ማለዶ', 'ቃማ'],
]);
