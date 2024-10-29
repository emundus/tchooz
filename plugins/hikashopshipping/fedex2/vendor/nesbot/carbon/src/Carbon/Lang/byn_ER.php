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
    'months' => ['ልደትሪ', 'ካብኽብቲ', 'ክብላ', 'ፋጅኺሪ', 'ክቢቅሪ', 'ምኪኤል ትጓ̅ኒሪ', 'ኰርኩ', 'ማርያም ትሪ', 'ያኸኒ መሳቅለሪ', 'መተሉ', 'ምኪኤል መሽወሪ', 'ተሕሳስሪ'],
    'months_short' => ['ልደት', 'ካብኽ', 'ክብላ', 'ፋጅኺ', 'ክቢቅ', 'ም/ት', 'ኰር', 'ማርያ', 'ያኸኒ', 'መተሉ', 'ም/ም', 'ተሕሳ'],
    'weekdays' => ['ሰንበር ቅዳዅ', 'ሰኑ', 'ሰሊጝ', 'ለጓ ወሪ ለብዋ', 'ኣምድ', 'ኣርብ', 'ሰንበር ሽጓዅ'],
    'weekdays_short' => ['ሰ/ቅ', 'ሰኑ', 'ሰሊጝ', 'ለጓ', 'ኣምድ', 'ኣርብ', 'ሰ/ሽ'],
    'weekdays_min' => ['ሰ/ቅ', 'ሰኑ', 'ሰሊጝ', 'ለጓ', 'ኣምድ', 'ኣርብ', 'ሰ/ሽ'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['ፋዱስ ጃብ', 'ፋዱስ ደምቢ'],
]);
