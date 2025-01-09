<?php
/**
 * @package    HikaMarket for Joomla!
 * @version    5.0.0
 * @author     Obsidev S.A.R.L.
 * @copyright  (C) 2011-2024 OBSIDEV. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class hikamarketMenusType {
	private $values = null;
	private function load($value) {
		if(isset($this->values))
			return;
		if(!HIKASHOP_J30){
			$query = 'SELECT m.alias as name, m.id as itemid, mt.title ' .
				' FROM `#__menu` as m LEFT JOIN `#__menu_types` as mt on m.menutype = mt.menutype '.
				' WHERE m.client_id = 0 AND m.parent_id != 0 ORDER BY mt.title ASC, m.ordering ASC';
		} else {
			$query = 'SELECT m.alias as name, m.id as itemid, mt.title , m.link '.
				' FROM `#__menu` as m LEFT JOIN `#__menu_types` as mt on m.menutype = mt.menutype '.
				' WHERE m.client_id = 0 AND m.parent_id != 0 AND m.published = 1 AND m.type=\'component\' '.
				' ORDER BY mt.title ASC';
		}

		$db = JFactory::getDBO();
		$db->setQuery($query);
		$menus = $db->loadObjectList('itemid');
		$config_data = array();
		foreach($menus as $menu) {
			if(strpos($menu->link, 'index.php?option='.HIKAMARKET_COMPONENT)===false && $menu->itemid != $value)
				continue;
			$config_data[$menu->title][] = JHTML::_('select.option', $menu->itemid, $menu->name);
		}
		if(!HIKASHOP_J40) {
			$this->values = array(
				JHTML::_('select.option', '0', JText::_('HIKA_NONE'))
			);
			foreach($config_data as $optGroup => $values) {
				$this->values[] = JHTML::_('select.optgroup', $optGroup);
				$this->values = array_merge($this->values, $values);
				$this->values[] = JHTML::_('select.optgroup', '');
			}
		} else {
			$this->values = array(
				array('items' => array( JHTML::_('select.option', '0', JText::_('HIKA_NONE')) ))
			);
			foreach($config_data as $optGroup => $values) {
				$this->values[] = array(
					'text' => $optGroup,
					'items' => $values
				);
			}
		}
	}

	public function display($map, $value) {
		$this->load($value);
		if(!HIKASHOP_J40)
			return JHTML::_('select.genericlist', $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', $value);
		return JHTML::_('select.groupedlist', $this->values, $map, array('list.attr'=>'class="custom-select"', 'group.id' => 'id', 'list.select' => array($value)) );
	}
}
