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

if(!class_exists('JFormField')) {
	class_alias('Joomla\CMS\Form\FormField', 'JFormField');
}
class JFormFieldSelectproduct extends JFormField{

	protected $type = 'selectproduct';

	protected function getInput() {
		if(!defined('DS'))
			define('DS', DIRECTORY_SEPARATOR);
		if(!function_exists('hikashop_table') && !include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')){
			echo 'HikaShop is required';
			return;
		}

		if(is_array($this->value))
			$this->value = reset($this->value);

		$nameboxType = hikashop_get('type.namebox');
		$select = '<div style="height:130px; margin-left:150px;">' .
				$nameboxType->display(
					'jform[params][product_id]',
					$this->value,
					hikashopNameboxType::NAMEBOX_SINGLE,
					'product',
					array(
						'delete' => false,
						'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
					)
				).
				'</div>';

		$j5_class = '';
		if (HIKASHOP_J50) 
			$j5_class = 'class="hika_j5"';

		return '<div id="hikashop_main_content" '.$j5_class.' >'.$select.'</div>';
	}
}
