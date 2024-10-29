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
    'meridiem' => ['a.g', 'a.k'],
    'weekdays' => ['tsuʔntsɨ', 'tsuʔukpà', 'tsuʔughɔe', 'tsuʔutɔ̀mlò', 'tsuʔumè', 'tsuʔughɨ̂m', 'tsuʔndzɨkɔʔɔ'],
    'weekdays_short' => ['nts', 'kpa', 'ghɔ', 'tɔm', 'ume', 'ghɨ', 'dzk'],
    'weekdays_min' => ['nts', 'kpa', 'ghɔ', 'tɔm', 'ume', 'ghɨ', 'dzk'],
    'months' => ['ndzɔ̀ŋɔ̀nùm', 'ndzɔ̀ŋɔ̀kƗ̀zùʔ', 'ndzɔ̀ŋɔ̀tƗ̀dʉ̀ghà', 'ndzɔ̀ŋɔ̀tǎafʉ̄ghā', 'ndzɔ̀ŋèsèe', 'ndzɔ̀ŋɔ̀nzùghò', 'ndzɔ̀ŋɔ̀dùmlo', 'ndzɔ̀ŋɔ̀kwîfɔ̀e', 'ndzɔ̀ŋɔ̀tƗ̀fʉ̀ghàdzughù', 'ndzɔ̀ŋɔ̀ghǔuwelɔ̀m', 'ndzɔ̀ŋɔ̀chwaʔàkaa wo', 'ndzɔ̀ŋèfwòo'],
    'months_short' => ['nùm', 'kɨz', 'tɨd', 'taa', 'see', 'nzu', 'dum', 'fɔe', 'dzu', 'lɔm', 'kaa', 'fwo'],
    'first_day_of_week' => 1,
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'D/M/YYYY',
        'LL' => 'D MMM, YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd D MMMM YYYY HH:mm',
    ],
]);
