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


use Symfony\Component\Translation\PluralizationRules;

if (class_exists('Symfony\\Component\\Translation\\PluralizationRules')) {
    PluralizationRules::set(static function ($number) {
        return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
    }, 'sh');
}

return [
    'diff_now' => 'sada',
    'diff_yesterday' => 'juče',
    'diff_tomorrow' => 'sutra',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'MMMM D, YYYY',
        'LLL' => 'DD MMM HH:mm',
        'LLLL' => 'MMMM DD, YYYY HH:mm',
    ],
    'year' => ':count godina|:count godine|:count godina',
    'y' => ':count g.',
    'month' => ':count mesec|:count meseca|:count meseci',
    'm' => ':count m.',
    'week' => ':count nedelja|:count nedelje|:count nedelja',
    'w' => ':count n.',
    'day' => ':count dan|:count dana|:count dana',
    'd' => ':count d.',
    'hour' => ':count sat|:count sata|:count sati',
    'h' => ':count č.',
    'minute' => ':count minut|:count minuta|:count minuta',
    'min' => ':count min.',
    'second' => ':count sekund|:count sekunde|:count sekundi',
    's' => ':count s.',
    'ago' => 'pre :time',
    'from_now' => 'za :time',
    'after' => 'nakon :time',
    'before' => ':time raniјe',
    'weekdays' => ['Nedelja', 'Ponedeljak', 'Utorak', 'Sreda', 'Četvrtak', 'Petak', 'Subota'],
    'weekdays_short' => ['Ned', 'Pon', 'Uto', 'Sre', 'Čet', 'Pet', 'Sub'],
    'weekdays_min' => ['Ned', 'Pon', 'Uto', 'Sre', 'Čet', 'Pet', 'Sub'],
    'months' => ['Januar', 'Februar', 'Mart', 'April', 'Maj', 'Jun', 'Jul', 'Avgust', 'Septembar', 'Oktobar', 'Novembar', 'Decembar'],
    'months_short' => ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Avg', 'Sep', 'Okt', 'Nov', 'Dec'],
    'list' => [', ', ' i '],
    'meridiem' => ['pre podne', 'po podne'],
];
