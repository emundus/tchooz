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
    'meridiem' => ['kíkíríg', 'ngəgógəle'],
    'weekdays' => ['sɔ́ndɔ', 'mɔ́ndi', 'sɔ́ndɔ məlú mə́bɛ̌', 'sɔ́ndɔ məlú mə́lɛ́', 'sɔ́ndɔ məlú mə́nyi', 'fúladé', 'séradé'],
    'weekdays_short' => ['sɔ́n', 'mɔ́n', 'smb', 'sml', 'smn', 'fúl', 'sér'],
    'weekdays_min' => ['sɔ́n', 'mɔ́n', 'smb', 'sml', 'smn', 'fúl', 'sér'],
    'months' => ['ngɔn osú', 'ngɔn bɛ̌', 'ngɔn lála', 'ngɔn nyina', 'ngɔn tána', 'ngɔn saməna', 'ngɔn zamgbála', 'ngɔn mwom', 'ngɔn ebulú', 'ngɔn awóm', 'ngɔn awóm ai dziá', 'ngɔn awóm ai bɛ̌'],
    'months_short' => ['ngo', 'ngb', 'ngl', 'ngn', 'ngt', 'ngs', 'ngz', 'ngm', 'nge', 'nga', 'ngad', 'ngab'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],

]);
