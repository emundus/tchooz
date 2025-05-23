<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class hikashopPositionType extends hikashopType{
	function load($inside){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', 'top',JText::_('HIKA_TOP'));
		$this->values[] = JHTML::_('select.option', 'bottom',JText::_('HIKA_BOTTOM'));
		$this->values[] = JHTML::_('select.option', 'left',JText::_('HIKA_LEFT'));
		$this->values[] = JHTML::_('select.option', 'right',JText::_('HIKA_RIGHT'));
		if($inside){
			$this->values[] = JHTML::_('select.option', 'inside',JText::_('HIKA_INSIDE'));
		}
	}
	function display($map,$value, $inside=true, $radio=false){
		$this->load($inside);
		$type='hikaselect.genericlist';
		if($radio){
			$type='hikaselect.radiolist';
		}
		if(HIKASHOP_J40)
			$attribs = 'size="1"';
		if(HIKASHOP_J50)
			$attribs = 'class="custom-select" size="1"';
		return JHTML::_($type, $this->values, $map, $attribs, 'value', 'text', $value );
	}
}
