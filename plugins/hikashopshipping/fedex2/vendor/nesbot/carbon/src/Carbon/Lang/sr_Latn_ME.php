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



use Carbon\CarbonInterface;
use Symfony\Component\Translation\PluralizationRules;

if (class_exists(PluralizationRules::class)) {
    PluralizationRules::set(static function ($number) {
        return PluralizationRules::get($number, 'sr');
    }, 'sr_Latn_ME');
}

return array_replace_recursive(require __DIR__.'/sr.php', [
    'month' => ':count mjesec|:count mjeseca|:count mjeseci',
    'week' => ':count nedjelja|:count nedjelje|:count nedjelja',
    'second' => ':count sekund|:count sekunde|:count sekundi',
    'ago' => 'prije :time',
    'from_now' => 'za :time',
    'after' => ':time nakon',
    'before' => ':time prije',
    'week_from_now' => ':count nedjelju|:count nedjelje|:count nedjelja',
    'week_ago' => ':count nedjelju|:count nedjelje|:count nedjelja',
    'second_ago' => ':count sekund|:count sekunde|:count sekundi',
    'diff_tomorrow' => 'sjutra',
    'calendar' => [
        'nextDay' => '[sjutra u] LT',
        'nextWeek' => function (CarbonInterface $date) {
            switch ($date->dayOfWeek) {
                case 0:
                    return '[u nedjelju u] LT';
                case 3:
                    return '[u srijedu u] LT';
                case 6:
                    return '[u subotu u] LT';
                default:
                    return '[u] dddd [u] LT';
            }
        },
        'lastWeek' => function (CarbonInterface $date) {
            switch ($date->dayOfWeek) {
                case 0:
                    return '[prošle nedjelje u] LT';
                case 1:
                    return '[prošle nedjelje u] LT';
                case 2:
                    return '[prošlog utorka u] LT';
                case 3:
                    return '[prošle srijede u] LT';
                case 4:
                    return '[prošlog četvrtka u] LT';
                case 5:
                    return '[prošlog petka u] LT';
                default:
                    return '[prošle subote u] LT';
            }
        },
    ],
    'weekdays' => ['nedjelja', 'ponedjeljak', 'utorak', 'srijeda', 'četvrtak', 'petak', 'subota'],
    'weekdays_short' => ['ned.', 'pon.', 'uto.', 'sri.', 'čet.', 'pet.', 'sub.'],
]);
