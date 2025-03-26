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
class hikashopConsistencyType extends hikashopType {
	function load($show_inherit = true, $groupby = false) {
		$this->values = array(
			0 => JHTML::_('select.option', 0, JTEXT::_('HIKASHOP_NO')),
			1 => JHTML::_('select.option', 1, JText::_('HIKASHOP_YES')),
			2 => JHTML::_('select.option', 2, JText::_('HIKASHOP_BTN_ALIGNED'))
		);
	}

	function display($map, $value, $form = true, $show_inherit = true, $groupby = false) {
		$this->load($show_inherit, $groupby);
		$options = 'class="custom-select" size="1" ';
		if(!$form) {
			$options .= 'onchange="this.form.submit();"';
		}
		return JHTML::_('select.genericlist', $this->values, $map, $options, 'value', 'text', $value);
	}
}
