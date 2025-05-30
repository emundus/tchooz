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
class JFormFieldPlugintrigger extends JFormField
{
	var $type = 'plugintrigger';
	function getInput() {
		if(!defined('DS'))
			define('DS', DIRECTORY_SEPARATOR);
		if(!function_exists('hikashop_getCID') && !include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')){
			return 'This plugin can not work without the Hikashop Component';
		}

		$id = hikashop_getCID('extension_id');
		if(!empty($id)){
			$text = '<fieldset class="radio"><a id="'.$this->id.'" title="'.JText::_('Trigger').'"  href="'.JRoute::_('index.php?option=com_hikashop&ctrl=plugins&task=trigger&function='.$this->value.'&cid='.$id.'&'.hikashop_getFormToken().'=1').'" >'.JText::_('Trigger').'</a></fieldset>';
		}
		$j5_class = '';
		if (HIKASHOP_J50) 
			$j5_class = 'class="hika_j5"';

		return '<div id="hikashop_main_content" '.$j5_class.' >'.$text.'</div>';
	}
}
