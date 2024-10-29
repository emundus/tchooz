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
    'meridiem' => ['Luma lwa K', 'luma lwa p'],
    'weekdays' => ['Ituku ja jumwa', 'Kuramuka jimweri', 'Kuramuka kawi', 'Kuramuka kadadu', 'Kuramuka kana', 'Kuramuka kasanu', 'Kifula nguwo'],
    'weekdays_short' => ['Jum', 'Jim', 'Kaw', 'Kad', 'Kan', 'Kas', 'Ngu'],
    'weekdays_min' => ['Jum', 'Jim', 'Kaw', 'Kad', 'Kan', 'Kas', 'Ngu'],
    'months' => ['Mori ghwa imbiri', 'Mori ghwa kawi', 'Mori ghwa kadadu', 'Mori ghwa kana', 'Mori ghwa kasanu', 'Mori ghwa karandadu', 'Mori ghwa mfungade', 'Mori ghwa wunyanya', 'Mori ghwa ikenda', 'Mori ghwa ikumi', 'Mori ghwa ikumi na imweri', 'Mori ghwa ikumi na iwi'],
    'months_short' => ['Imb', 'Kaw', 'Kad', 'Kan', 'Kas', 'Kar', 'Mfu', 'Wun', 'Ike', 'Iku', 'Imw', 'Iwi'],
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],
]);
