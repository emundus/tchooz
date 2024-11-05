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
    'meridiem' => ['maná', 'kugú'],
    'weekdays' => ['sɔ́ndɔ', 'mɔ́ndɔ', 'sɔ́ndɔ mafú mába', 'sɔ́ndɔ mafú málal', 'sɔ́ndɔ mafú mána', 'mabágá má sukul', 'sásadi'],
    'weekdays_short' => ['sɔ́n', 'mɔ́n', 'smb', 'sml', 'smn', 'mbs', 'sas'],
    'weekdays_min' => ['sɔ́n', 'mɔ́n', 'smb', 'sml', 'smn', 'mbs', 'sas'],
    'months' => ['ngwɛn matáhra', 'ngwɛn ńmba', 'ngwɛn ńlal', 'ngwɛn ńna', 'ngwɛn ńtan', 'ngwɛn ńtuó', 'ngwɛn hɛmbuɛrí', 'ngwɛn lɔmbi', 'ngwɛn rɛbvuâ', 'ngwɛn wum', 'ngwɛn wum navǔr', 'krísimin'],
    'months_short' => ['ng1', 'ng2', 'ng3', 'ng4', 'ng5', 'ng6', 'ng7', 'ng8', 'ng9', 'ng10', 'ng11', 'kris'],
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
