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


return array_replace_recursive(require __DIR__.'/ta.php', [
    'formats' => [
        'LT' => 'a h:mm',
        'LTS' => 'a h:mm:ss',
        'L' => 'D/M/yy',
        'LL' => 'D MMM, YYYY',
        'LLL' => 'D MMMM, YYYY, a h:mm',
        'LLLL' => 'dddd, D MMMM, YYYY, a h:mm',
    ],
    'months' => ['ஜனவரி', 'பிப்ரவரி', 'மார்ச்', 'ஏப்ரல்', 'மே', 'ஜூன்', 'ஜூலை', 'ஆகஸ்ட்', 'செப்டம்பர்', 'அக்டோபர்', 'நவம்பர்', 'டிசம்பர்'],
    'months_short' => ['ஜன.', 'பிப்.', 'மார்.', 'ஏப்.', 'மே', 'ஜூன்', 'ஜூலை', 'ஆக.', 'செப்.', 'அக்.', 'நவ.', 'டிச.'],
    'weekdays' => ['ஞாயிறு', 'திங்கள்', 'செவ்வாய்', 'புதன்', 'வியாழன்', 'வெள்ளி', 'சனி'],
    'weekdays_short' => ['ஞாயி.', 'திங்.', 'செவ்.', 'புத.', 'வியா.', 'வெள்.', 'சனி'],
    'weekdays_min' => ['ஞா', 'தி', 'செ', 'பு', 'வி', 'வெ', 'ச'],
    'first_day_of_week' => 1,
    'meridiem' => ['மு.ப', 'பி.ப'],
]);
