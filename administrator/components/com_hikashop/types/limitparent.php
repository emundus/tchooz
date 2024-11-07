<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.1
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class hikashopLimitparentType extends hikashopType{
	function load($type, $object, $value){
		$this->values = array();
		$db = JFactory::getDBO();
		$db->setQuery('SELECT * FROM #__hikashop_field WHERE field_table='.$db->Quote($type));
		$fields = $db->loadObjectList();
		if(!empty($fields)){
			foreach($fields as $field){
				if(is_object($object) && isset($object->field_namekey) && $object->field_namekey == $field->field_namekey)
					continue;
				if($field->field_type == "customtext" && $value != $field->field_namekey)
					continue;
				$this->values[] = JHTML::_('select.option', $field->field_namekey,$field->field_realname);
			}
			if(count($this->values)){
				$this->values[] = JHTML::_('select.option', '',JText::_('HIKA_ALL'));
			}
		}
	}
	function display($map,$value,$type,$parent_value,$object=null){
		$this->load($type, $object, $value);
		if(!count($this->values)){
			return JText::_('AT_LEAST_ONE_FIELD_PUBLISHED');
		}
		if(is_array($parent_value)){
			$parent_value=implode(',', $parent_value);
		}
		$url=hikashop_completeLink('field&task=parentfield&type='.$type.'&value='.$parent_value,true,true);
		$js ="
function hikashopLoadParent(namekey){
	window.Oby.xRequest('".$url."&namekey='+namekey, null,
		function(xhr,params) {
			old = window.document.getElementById('parent_value');
			if(old){
				old.innerHTML = xhr.responseText;
			}
		}
	);
}
window.hikashop.ready(function(){
	hikashopLoadParent(document.getElementById('limit_parent_select').value);
});
		";
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration( $js );
		$conditions = array(
			'IS' => JText::_('EQUAL_TO'),
			'IS NOT' => JText::_('NOT_EQUAL_TO'),
		);
		$condition = JHTML::_('select.genericlist', $conditions, 'field_options[limit_to_parent_condition]', 'class="custom-select" size="1"', 'value', 'text', @$object->field_options['limit_to_parent_condition'], 'limit_parent_select_condition' );
		return JHTML::_('select.genericlist',   $this->values, $map, 'class="custom-select" size="1" onChange="hikashopLoadParent(this.value);"', 'value', 'text', $value, 'limit_parent_select' ).$condition;
	}
}
