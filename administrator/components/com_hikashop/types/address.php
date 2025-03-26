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
class hikashopAddressType extends hikashopType{
	function load(){
		$this->values = array();
		$this->values[] = JHTML::_('select.option', '',JText::_('NO_ADDRESS') );
		$this->values[] = JHTML::_('select.option', 'billing',JText::_('HIKASHOP_BILLING_ADDRESS'));
		$this->values[] = JHTML::_('select.option', 'shipping',JText::_('HIKASHOP_SHIPPING_ADDRESS'));
	}
	function display($map,$value){
		$this->load();
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1"', 'value', 'text', $value );
	}
}
