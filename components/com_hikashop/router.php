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

$jversion = preg_replace('#[^0-9\.]#i','',JVERSION);
if(version_compare($jversion,'4.0.0','>=')) {
	class hikashopRouter extends Joomla\CMS\Component\Router\RouterBase {

		public function build(&$query) {
			return _HikashopBuildRoute($query);
		}

		public function parse(&$segments) {
			return _HikashopParseRoute($segments);
		}
	}
}

function HikashopBuildRoute( &$query ) { return _HikashopBuildRoute($query, ':'); }

function _HikashopBuildRoute( &$query, $separator = '-' )
{
	$segments = array();
	$params = array('option','Itemid','start','format','limitstart','lang','cart_id', 'tmpl');
	if(!defined('DS'))
		define('DS', DIRECTORY_SEPARATOR);
	if(function_exists('hikashop_config') || include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php')) {
		JPluginHelper::importPlugin('hikashop');
		$app = JFactory::getApplication();
		$app->triggerEvent('onBeforeHikashopBuildRoute', array(&$query, $separator, &$params));

		$config = hikashop_config();
		if($config->get('activate_sef',1)){
			$checkoutSef=$config->get('checkout_sef_name','checkout');
			$categorySef = hikashop_get_sef_name($query, 'category');
			$productSef = hikashop_get_sef_name($query, 'product');

			if(isset($query['ctrl']) && isset($query['task'])){
				if($query['ctrl']=='category' && $query['task']=='listing'){
					$segments[] = $categorySef;
					unset( $query['ctrl'] );
					unset( $query['task'] );
				}
				else if($query['ctrl']=='product' && $query['task']=='show'){
					$segments[] = $productSef;
					unset( $query['ctrl'] );
					unset( $query['task'] );
				}
			} else if(!empty($query['Itemid']) && isset($query['cid']) && isset($query['name'])){
				$menuClass = hikashop_get('class.menus');
				$menu = $menuClass->get($query['Itemid']);
				if(!empty($menu) && !empty($menu->link)) {
					if($menu->link =='index.php?option=com_hikashop&view=category&layout=listing') {
						$segments[] = $categorySef;
					}
				}

			}

			if( ( isset($query['ctrl']) && $query['ctrl']=='checkout' || isset($query['view']) && $query['view']=='checkout' ) && !empty($query['Itemid']) && ( !isset($query['task']) && !isset($query['layout']) || @$query['task']=='step' || @$query['task']=='show' || @$query['layout']=='step' || @$query['layout']=='show' ) ) {
				if(empty($checkoutSef)){
					$menuClass = hikashop_get('class.menus');
					$menu = $menuClass->get($query['Itemid']);
					if(!empty($menu) && !empty($menu->link) && $menu->link =='index.php?option=com_hikashop&view=checkout&layout=step'){
						if(isset($query['ctrl'])) unset($query['ctrl']);
						if(isset($query['view'])) unset($query['view']);
						if(isset($query['layout'])) unset($query['layout']);
					}
				}else{
					if(isset($query['ctrl'])) unset($query['ctrl']);
					if(isset($query['view'])) unset($query['view']);
					if(isset($query['layout'])) unset($query['layout']);
					if(!empty($checkoutSef)) $segments[] = $checkoutSef;
				}
			}
		}
		$pathway_sef_name = $config->get('pathway_sef_name','category_pathway');
		if(isset($query[$pathway_sef_name])&& (empty($query[$pathway_sef_name])) || $config->get('simplified_breadcrumbs',1)){
			unset( $query[$pathway_sef_name] );
		}
		if(isset($query[$pathway_sef_name])){
			$category_pathway = $config->get('category_pathway','category_pathway');
			if($category_pathway!='category_pathway' && !empty($category_pathway)){
				$query[$category_pathway]=$query[$pathway_sef_name];
				unset( $query[$pathway_sef_name] );
			}
		}
		$related_sef_name = $config->get('related_sef_name','related_product');
		if(isset($query[$related_sef_name])&& $config->get('simplified_breadcrumbs',1)){
			unset( $query[$related_sef_name] );
		}
	}

	if (isset($query['ctrl'])) {
		$ctrl = $query['ctrl'];
		$segments[] = $query['ctrl'];
		unset( $query['ctrl'] );
		if (isset($query['task'])) {
			$segments[] = $query['task'];
			unset( $query['task'] );
		}
	}elseif(!empty($query['Itemid']) && isset($query['view'])){
		$ctrl = $query['view'];
		unset( $query['view'] );
		if(isset($query['layout'])){
			unset( $query['layout'] );
		}
	}else{
		$ctrl = '';
	}

	if(isset($query['product_id'])){
		$query['cid'] = $query['product_id'];
		unset($query['product_id']);
	}
	if(isset($query['cid']) && isset($query['name'])){
		if($config->get('sef_remove_id',0) && !empty($query['name']) && in_array($ctrl, array('product','category', ''))) {
			$int_at_the_beginning = (int)$query['name'];
			if($int_at_the_beginning){
				$query['name'] = $config->get('alias_prefix','p').$query['name'];
			}
			$segments[] = $query['name'];
		}else{
			if(is_numeric($query['name'])){
				$query['name']=$query['name'].'-';
			}
			$segments[] = $query['cid'].$separator.$query['name'];
		}
		unset($query['cid']);
		unset($query['name']);
	}

	if(!empty($query)){
		foreach($query as $name => $value){
			if(!in_array($name, $params) && substr($name,0,6) != 'x-wblr'){
					if(is_array($value)) $value = implode('-',$value);
					$segments[] = $name.$separator.$value;
				unset($query[$name]);
			}
		}
	}

	return $segments;
}

function HikashopParseRoute( $segments ) { return _HikashopParseRoute($segments, ':'); }

function _HikashopParseRoute( &$segments, $separator = '-' )
{
	$vars = array();
	$check = false;
	if(empty($segments))
		return $vars;

	if(!defined('DS'))
		define('DS', DIRECTORY_SEPARATOR);
	if(!function_exists('hikashop_config') && !include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php'))
		return $vars;

	$config =& hikashop_config();
	if(!$config->get('activate_sef', 1)) {
		foreach($segments as $name){
			hikashop_retrieve_url_id($vars,$name);
		}
		$segments = array();
		return $vars;
	}

	$categorySef=$config->get('category_sef_name','category');
	if(!empty($categorySef) && ctype_upper(str_replace('_', '', $categorySef)))
		$categorySef = JText::_($categorySef);
	$productSef=$config->get('product_sef_name','product');
	if(!empty($productSef) && ctype_upper(str_replace('_', '', $productSef)))
		$productSef = JText::_($productSef);
	$checkoutSef=$config->get('checkout_sef_name','checkout');
	$skip = false;
	if(isset($segments[0])) {
		$file = HIKASHOP_CONTROLLER.$segments[0].'.php';
		if(file_exists($file) && isset($segments[1])) {
			if(!($segments[0]=='product'&&$segments[1]=='show' || $segments[0]=='category'&&$segments[1]=='listing' || $segments[0]=='checkout'&&$segments[1]=='notice')){
				$controller = hikashop_get('controller.'.$segments[0],array(),true);
				if($controller->isIn($segments[1],array('display','modify_views','add','modify','delete'))){
					$skip = true;
				}
			}
		}
	}

	if(!$skip) {
		if(count($segments)==1){
			if(empty($categorySef)){
				$vars['ctrl']='category';
				$vars['task']='listing';
			}
			elseif(empty($productSef)){
				$vars['ctrl']='product';
				$vars['task']='show';
			}
		}

		$i = 0;

		foreach($segments as $k => $name) {
			if(strpos($name, $separator)) {
				if(empty($productSef) && !$check) {
					$vars['ctrl']='product';
					$vars['task']='show';
				}
				list($arg,$val) = explode($separator,$name,2);
				if($arg=='task' && ($val == 'step' || $val =='show')){
					$vars['ctrl']='checkout';
				}
				if($arg=='cid' && is_numeric($val) && count($segments) < 2){
					$vars['ctrl']='checkout';
					$vars['task']='show';
					$vars['cid'] = $val;
				}elseif(is_numeric($arg) && !is_numeric($val)){
					$vars['cid'] = $arg;
					$vars['name'] = $val;
				}elseif(is_numeric($arg)){
					$vars['Itemid'] = $arg;
				}elseif(str_replace(':','-',$name)==$productSef){
					$vars['ctrl']='product';
					$vars['task']='show';
				}else if(str_replace(':','-',$name)==$categorySef){
					$vars['ctrl']='category';
					$vars['task']='listing';
					$check=true;
				}else if(str_replace(':','-',$name)==$checkoutSef){
					$vars['ctrl']='checkout';
					$vars['task']='show';
					$check=true;
				}else if($arg=='step' && is_numeric($val)) {
					$vars['ctrl']='checkout';
					$vars['task']='step';
					$vars['step'] = $val;
				}else{
					if(hikashop_retrieve_url_id($vars,$name)) continue;
					if($arg == 'triggerplug') {
						$vars['task'] = $arg.'-'.$val;
					} else {
						$vars[$arg] = $val;
					}
				}
			}else if($name==$productSef){
				$vars['ctrl']='product';
				$vars['task']='show';
			}else if($name==$categorySef){
				$vars['ctrl']='category';
				$vars['task']='listing';
				$check=true;
			}else if($name==$checkoutSef && ( $name!= 'checkout' || !isset($segments[$k+1]) || $segments[$k+1] != 'notice' )){
				$vars['ctrl']='checkout';
				$vars['task']='step';
				$check=true;
			}else{
				if(hikashop_retrieve_url_id($vars,$name)) continue;
				$i++;
				if($i == 1){
					$vars['ctrl'] = $name;
					$vars['task'] = '';
				}elseif($i == 2)
					$vars['task'] = $name;
				$check=true;
			}
		}
		$segments = array();
		return $vars;
	}

	$i = 0;
	foreach($segments as $name) {
		if(strpos($name,$separator)){
			list($arg,$val) = explode($separator,$name,2);
			if(is_numeric($arg) && !is_numeric($val)){
				$vars['cid'] = $arg;
				$vars['name'] = $val;
			}elseif(is_numeric($arg)){
				if(hikashop_retrieve_url_id($vars,$name)) continue;
				$vars['Itemid'] = $arg;
			}else{
				if(hikashop_retrieve_url_id($vars,$name)) continue;
				$vars[$arg] = $val;
			}
		}else{
			if(hikashop_retrieve_url_id($vars,$name)) continue;
			$i++;
			if($i == 1) $vars['ctrl'] = $name;
			elseif($i == 2) $vars['task'] = $name;
		}
	}
	$category_pathway = $config->get('category_pathway','category_pathway');
	if($category_pathway!='category_pathway' && isset($vars[$category_pathway])){
		$vars['category_pathway']=$vars[$category_pathway];
	}

	$segments = array();
	return $vars;
}

function hikashop_get_sef_name(&$query, $type='product') {
	$config = hikashop_config();
	$sefName = $config->get($type.'_sef_name', $type);
	if(empty($sefName)) {
		$sefName = '';
	} else {
		if(ctype_upper(str_replace('_', '', $sefName))) {
			if(!empty($query['lang'])) {
				$code = $query['lang'];
				if(strlen($query['lang']) == 2) {
					$languages	= JLanguageHelper::getLanguages();
					foreach($languages as $language) {
						$sef = substr($language->lang_code, 0,2);
						if(!empty($language->sef))
							$sef = $language->sef;
						if($sef == $query['lang']) {
							$code = $language->lang_code;
							break;
						}
					}
				}
				$sefName = hikashop_translate($sefName, $code, true);
			} else {
				$sefName = JText::_($sefName);
			}
		}
	}
	return $sefName;
}

function hikashop_retrieve_url_id(&$vars,$name){
	$config =& hikashop_config();
	if($config->get('sef_remove_id',0) && isset($vars['ctrl']) && isset($vars['task'])){
		if($vars['ctrl']=='category' || ($vars['ctrl']=='product' && $vars['task']=='listing')){
			$type = 'category';
		}elseif($vars['ctrl']=='product' && $vars['task']=='show'){
			$type = 'product';
		}else{
			return false;
		}

		$db = JFactory::getDBO();
		$config = hikashop_config();
		$translationHelper = hikashop_get('helper.translation');
		$lang = JFactory::getLanguage();

		if($config->get('translated_aliases', 0) && $translationHelper->isMulti(true, false)) {
			$retrieved_id = $translationHelper->getOriginalId($type, $name);
			if(!empty($retrieved_id)){
				$vars['cid'] = $retrieved_id;
				$vars['name'] = $name;
				return true;
			}
		}
		$db->setQuery('SELECT '.$type.'_id FROM '.hikashop_table($type).' WHERE '.$type.'_alias = '.$db->Quote(str_replace(':','-',$name)));
		$retrieved_id = $db->loadResult();
		if($retrieved_id){
			$vars['cid'] = $retrieved_id;
			$vars['name'] = $name;
			return true;
		}

		if($config->get('translated_aliases', 0) && $translationHelper->isMulti(true, false)) {
			$retrieved_id = $translationHelper->getOriginalId($type, $name, true);
			if(!empty($retrieved_id)){
				$vars['cid'] = $retrieved_id;
				$vars['name'] = $name;
				return true;
			}
		}
		$name_regex = '^ *p?'.str_replace(array('-',':'),'.+',str_replace(array('*', '+', '(', ')', '?', '='), '', $name)).' *$';
		$db->setQuery('SELECT * FROM '.hikashop_table($type).' WHERE '.$type.'_alias REGEXP '.$db->Quote($name_regex).' OR '.$type.'_name REGEXP '.$db->Quote($name_regex));
		$retrieved = $db->loadObject();

		if($retrieved){
			$type_id = $type.'_id';
			$vars['cid'] = $retrieved->$type_id;
			$vars['name'] = $name;
			if($config->get('alias_auto_fill',1)){
				$type_alias = $type.'_alias';
				if(empty($retrieved->$type_alias)){
					$class = hikashop_get('class.'.$type);
					$class->addAlias($retrieved);

					if($config->get('sef_remove_id',0)){
						$int_at_the_beginning = (int)$retrieved->alias;
						if($int_at_the_beginning){
							$retrieved->alias = $config->get('alias_prefix','p').$retrieved->alias;
						}
					}

					$element = new stdClass();
					$element->$type_id = $retrieved->$type_id;
					$element->$type_alias = $retrieved->alias;

					$class->save($element);
				}
			}
			return true;
		}
	}
	return false;
}
