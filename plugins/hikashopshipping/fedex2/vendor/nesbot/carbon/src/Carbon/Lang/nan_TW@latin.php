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
        'L' => 'YYYY-MM-DD',
    ],
    'months' => ['1goe̍h', '2goe̍h', '3goe̍h', '4goe̍h', '5goe̍h', '6goe̍h', '7goe̍h', '8goe̍h', '9goe̍h', '10goe̍h', '11goe̍h', '12goe̍h'],
    'months_short' => ['1g', '2g', '3g', '4g', '5g', '6g', '7g', '8g', '9g', '10g', '11g', '12g'],
    'weekdays' => ['lé-pài-ji̍t', 'pài-it', 'pài-jī', 'pài-saⁿ', 'pài-sì', 'pài-gō͘', 'pài-la̍k'],
    'weekdays_short' => ['lp', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6'],
    'weekdays_min' => ['lp', 'p1', 'p2', 'p3', 'p4', 'p5', 'p6'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['téng-po͘', 'ē-po͘'],
]);
