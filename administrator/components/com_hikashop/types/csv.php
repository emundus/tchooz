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
class hikashopCsvType extends hikashopType {
	function load(){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', ',',JText::_('COMMA'));
		$this->values[] = JHTML::_('select.option', ';',JText::_('SEMICOLON'));
		$this->values[] = JHTML::_('select.option', '|',JText::_('PIPE'));
	}
	function display($map,$value){
		$this->load();
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', $value );
	}
}
