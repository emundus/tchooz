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
        'L' => 'D/M/YY',
    ],
    'months' => ['जनवरी', 'फरवरी', 'मार्च', 'अपरेल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितमबर', 'अकटूबर', 'नवमबर', 'दिसमबर'],
    'months_short' => ['जन', 'फर', 'मार्च', 'अप', 'मई', 'जून', 'जुला', 'अग', 'सित', 'अकटू', 'नव', 'दिस'],
    'weekdays' => ['इतवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'बिरसपत', 'सुकरवार', 'सनिवार'],
    'weekdays_short' => ['इत', 'सोम', 'मंग', 'बुध', 'बिर', 'सुक', 'सनि'],
    'weekdays_min' => ['इत', 'सोम', 'मंग', 'बुध', 'बिर', 'सुक', 'सनि'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['बिहिनियाँ', 'मंझनियाँ'],
]);
