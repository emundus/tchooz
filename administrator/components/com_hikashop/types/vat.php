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
class hikashopVatType extends hikashopType {
	function load(){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', 0,JText::_('NO_VAT_CHECK'));
		$this->values[] = JHTML::_('select.option', 1,JText::_('FORMAT_CHECK'));
		$this->values[] = JHTML::_('select.option', 2,JText::_('ONLINE_CHECK'));
	}
	function display($map,$value){
		$this->load();
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', (int)$value );
	}
}
