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
class JFormFieldFilters extends JFormField
{
	var $type = 'help';
	function getInput() {
		if(!defined('DS'))
			define('DS', DIRECTORY_SEPARATOR);
		if(!function_exists('hikashop_getCID') && !include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')){
			return 'This plugin can not work without the Hikashop Component';
		}
		$nameboxType = hikashop_get('type.namebox');
		if(!is_array($this->value))
			$this->value = explode(',',$this->value);
		$text = $nameboxType->display(
			$this->name,
			$this->value,
			hikashopNameboxType::NAMEBOX_MULTIPLE,
			'filter',
			array(
				'delete' => true,
				'returnOnEmpty' => false,
				'default_text' => '<em>'.JText::_('HIKA_ALL').'</em>',
				'url_params' => array(),
			)
		);
		if(empty($text))
			$text = hikashop_display(JText::_('PLEASE_CREATE_FILTERS_FIRST'), 'error', true);

		$j5_class = '';
		if (HIKASHOP_J50) 
			$j5_class = 'class="hika_j5"';

		return '<div id="hikashop_main_content" '.$j5_class.' >'.$text.'</div>';
	}
}
