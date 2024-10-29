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

return [
    'year' => ':count წელი',
    'y' => ':count წელი',
    'a_year' => '{1}წელი|]1,Inf[:count წელი',
    'month' => ':count თვე',
    'm' => ':count თვე',
    'a_month' => '{1}თვე|]1,Inf[:count თვე',
    'week' => ':count კვირა',
    'w' => ':count კვირა',
    'a_week' => '{1}კვირა|]1,Inf[:count კვირა',
    'day' => ':count დღე',
    'd' => ':count დღე',
    'a_day' => '{1}დღე|]1,Inf[:count დღე',
    'hour' => ':count საათი',
    'h' => ':count საათი',
    'a_hour' => '{1}საათი|]1,Inf[:count საათი',
    'minute' => ':count წუთი',
    'min' => ':count წუთი',
    'a_minute' => '{1}წუთი|]1,Inf[:count წუთი',
    'second' => ':count წამი',
    's' => ':count წამი',
    'a_second' => '{1}რამდენიმე წამი|]1,Inf[:count წამი',
    'ago' => function ($time) {
        $replacements = [
            'წელი' => 'წლის',
            'თვე' => 'თვის',
            'კვირა' => 'კვირის',
            'დღე' => 'დღის',
            'საათი' => 'საათის',
            'წუთი' => 'წუთის',
            'წამი' => 'წამის',
        ];
        $time = strtr($time, array_flip($replacements));
        $time = strtr($time, $replacements);

        return "$time წინ";
    },
    'from_now' => function ($time) {
        $replacements = [
            'წელი' => 'წელიწადში',
            'კვირა' => 'კვირაში',
            'დღე' => 'დღეში',
            'თვე' => 'თვეში',
            'საათი' => 'საათში',
            'წუთი' => 'წუთში',
            'წამი' => 'წამში',
        ];
        $time = strtr($time, array_flip($replacements));
        $time = strtr($time, $replacements);

        return $time;
    },
    'after' => function ($time) {
        $replacements = [
            'წელი' => 'წლის',
            'თვე' => 'თვის',
            'კვირა' => 'კვირის',
            'დღე' => 'დღის',
            'საათი' => 'საათის',
            'წუთი' => 'წუთის',
            'წამი' => 'წამის',
        ];
        $time = strtr($time, array_flip($replacements));
        $time = strtr($time, $replacements);

        return "$time შემდეგ";
    },
    'before' => function ($time) {
        $replacements = [
            'წელი' => 'წლით',
            'თვე' => 'თვით',
            'კვირა' => 'კვირით',
            'დღე' => 'დღით',
            'საათი' => 'საათით',
            'წუთი' => 'წუთით',
            'წამი' => 'წამით',
        ];
        $time = strtr($time, array_flip($replacements));
        $time = strtr($time, $replacements);

        return "$time ადრე";
    },
    'diff_now' => 'ახლა',
    'diff_today' => 'დღეს',
    'diff_yesterday' => 'გუშინ',
    'diff_tomorrow' => 'ხვალ',
    'formats' => [
        'LT' => 'HH:mm',
        'LTS' => 'HH:mm:ss',
        'L' => 'DD/MM/YYYY',
        'LL' => 'D MMMM YYYY',
        'LLL' => 'D MMMM YYYY HH:mm',
        'LLLL' => 'dddd, D MMMM YYYY HH:mm',
    ],
    'calendar' => [
        'sameDay' => '[დღეს], LT[-ზე]',
        'nextDay' => '[ხვალ], LT[-ზე]',
        'nextWeek' => function (CarbonInterface $current, CarbonInterface $other) {
            return ($current->isSameWeek($other) ? '' : '[შემდეგ] ').'dddd, LT[-ზე]';
        },
        'lastDay' => '[გუშინ], LT[-ზე]',
        'lastWeek' => '[წინა] dddd, LT-ზე',
        'sameElse' => 'L',
    ],
    'ordinal' => function ($number) {
        if ($number === 0) {
            return $number;
        }
        if ($number === 1) {
            return $number.'-ლი';
        }
        if (($number < 20) || ($number <= 100 && ($number % 20 === 0)) || ($number % 100 === 0)) {
            return 'მე-'.$number;
        }

        return $number.'-ე';
    },
    'months' => ['იანვარი', 'თებერვალი', 'მარტი', 'აპრილი', 'მაისი', 'ივნისი', 'ივლისი', 'აგვისტო', 'სექტემბერი', 'ოქტომბერი', 'ნოემბერი', 'დეკემბერი'],
    'months_standalone' => ['იანვარს', 'თებერვალს', 'მარტს', 'აპრილს', 'მაისს', 'ივნისს', 'ივლისს', 'აგვისტოს', 'სექტემბერს', 'ოქტომბერს', 'ნოემბერს', 'დეკემბერს'],
    'months_short' => ['იან', 'თებ', 'მარ', 'აპრ', 'მაი', 'ივნ', 'ივლ', 'აგვ', 'სექ', 'ოქტ', 'ნოე', 'დეკ'],
    'months_regexp' => '/(D[oD]?(\[[^\[\]]*\]|\s)+MMMM?|L{2,4}|l{2,4})/',
    'weekdays' => ['კვირას', 'ორშაბათს', 'სამშაბათს', 'ოთხშაბათს', 'ხუთშაბათს', 'პარასკევს', 'შაბათს'],
    'weekdays_standalone' => ['კვირა', 'ორშაბათი', 'სამშაბათი', 'ოთხშაბათი', 'ხუთშაბათი', 'პარასკევი', 'შაბათი'],
    'weekdays_short' => ['კვი', 'ორშ', 'სამ', 'ოთხ', 'ხუთ', 'პარ', 'შაბ'],
    'weekdays_min' => ['კვ', 'ორ', 'სა', 'ოთ', 'ხუ', 'პა', 'შა'],
    'weekdays_regexp' => '/^([^d].*|.*[^d])$/',
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
    'list' => [', ', ' და '],
    'meridiem' => function ($hour) {
        if ($hour >= 4) {
            if ($hour < 11) {
                return 'დილის';
            }

            if ($hour < 16) {
                return 'შუადღის';
            }

            if ($hour < 22) {
                return 'საღამოს';
            }
        }

        return 'ღამის';
    },
];
