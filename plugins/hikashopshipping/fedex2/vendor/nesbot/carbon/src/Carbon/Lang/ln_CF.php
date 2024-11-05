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


return array_replace_recursive(require __DIR__.'/ln.php', [
    'weekdays' => ['eyenga', 'mokɔlɔ mwa yambo', 'mokɔlɔ mwa míbalé', 'mokɔlɔ mwa mísáto', 'mokɔlɔ ya mínéi', 'mokɔlɔ ya mítáno', 'mpɔ́sɔ'],
    'weekdays_short' => ['eye', 'ybo', 'mbl', 'mst', 'min', 'mtn', 'mps'],
    'weekdays_min' => ['eye', 'ybo', 'mbl', 'mst', 'min', 'mtn', 'mps'],
    'meridiem' => ['ntɔ́ngɔ́', 'mpókwa'],
]);
