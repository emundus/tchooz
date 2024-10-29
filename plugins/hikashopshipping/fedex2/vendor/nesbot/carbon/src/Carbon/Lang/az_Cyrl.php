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


return array_replace_recursive(require __DIR__.'/az.php', [
    'weekdays' => ['базар', 'базар ертәси', 'чәршәнбә ахшамы', 'чәршәнбә', 'ҹүмә ахшамы', 'ҹүмә', 'шәнбә'],
    'weekdays_short' => ['Б.', 'Б.Е.', 'Ч.А.', 'Ч.', 'Ҹ.А.', 'Ҹ.', 'Ш.'],
    'weekdays_min' => ['Б.', 'Б.Е.', 'Ч.А.', 'Ч.', 'Ҹ.А.', 'Ҹ.', 'Ш.'],
    'months' => ['јанвар', 'феврал', 'март', 'апрел', 'май', 'ијун', 'ијул', 'август', 'сентјабр', 'октјабр', 'нојабр', 'декабр'],
    'months_short' => ['јан', 'фев', 'мар', 'апр', 'май', 'ијн', 'ијл', 'авг', 'сен', 'окт', 'ној', 'дек'],
    'months_standalone' => ['Јанвар', 'Феврал', 'Март', 'Апрел', 'Май', 'Ијун', 'Ијул', 'Август', 'Сентјабр', 'Октјабр', 'Нојабр', 'Декабр'],
    'meridiem' => ['а', 'п'],
]);
