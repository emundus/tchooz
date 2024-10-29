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
        'L' => 'DD/MM/YYYY',
    ],
    'months' => ['Qunxa Garablu', 'Kudo', 'Ciggilta Kudo', 'Agda Baxisso', 'Caxah Alsa', 'Qasa Dirri', 'Qado Dirri', 'Liiqen', 'Waysu', 'Diteli', 'Ximoli', 'Kaxxa Garablu'],
    'months_short' => ['Qun', 'Kud', 'Cig', 'Agd', 'Cax', 'Qas', 'Qad', 'Leq', 'Way', 'Dit', 'Xim', 'Kax'],
    'weekdays' => ['Acaada', 'Etleeni', 'Talaata', 'Arbaqa', 'Kamiisi', 'Gumqata', 'Sabti'],
    'weekdays_short' => ['Aca', 'Etl', 'Tal', 'Arb', 'Kam', 'Gum', 'Sab'],
    'weekdays_min' => ['Aca', 'Etl', 'Tal', 'Arb', 'Kam', 'Gum', 'Sab'],
    'day_of_first_week_of_year' => 1,
    'meridiem' => ['saaku', 'carra'],
]);
