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


return array_replace_recursive(require __DIR__.'/ru.php', [
    'formats' => [
        'L' => 'YYYY.MM.DD',
    ],
    'months' => ['тохсунньу', 'олунньу', 'кулун тутар', 'муус устар', 'ыам ыйын', 'бэс ыйын', 'от ыйын', 'атырдьах ыйын', 'балаҕан ыйын', 'алтынньы', 'сэтинньи', 'ахсынньы'],
    'months_short' => ['тохс', 'олун', 'кул', 'муус', 'ыам', 'бэс', 'от', 'атыр', 'бал', 'алт', 'сэт', 'ахс'],
    'weekdays' => ['баскыһыанньа', 'бэнидиэнньик', 'оптуорунньук', 'сэрэдэ', 'чэппиэр', 'бээтинсэ', 'субуота'],
    'weekdays_short' => ['бс', 'бн', 'оп', 'ср', 'чп', 'бт', 'сб'],
    'weekdays_min' => ['бс', 'бн', 'оп', 'ср', 'чп', 'бт', 'сб'],
    'first_day_of_week' => 1,
    'day_of_first_week_of_year' => 1,
]);
