<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.3
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
include_once(JPATH_ROOT.'/administrator/components/com_hikashop/pluginCompat.php');
class plgQuickiconHikaShop  extends hikashopJoomlaPlugin {
	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage('com_hikashop.sys');
	}

	public function onGetIcons($context) {
		if(!defined('DS'))
			define('DS',DIRECTORY_SEPARATOR);
		$hikashopHelper = rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php';

		if(HIKASHOP_J50)
			$paramContext = $this->params->get('context', 'site_quickicon');
		else
			$paramContext = $this->params->get('context', 'mod_quickicon');
		if(is_string($context) || is_null($context)) {
			$functionContext = $context;
		} else {
			$functionContext = $context->getContext();
		}
		if($functionContext != $paramContext || !file_exists($hikashopHelper) || !JFactory::getUser()->authorise('core.manage', 'com_hikashop')) {
			return;
		}

		if(version_compare(JVERSION, '4.0', '>=')) {
			$img = 'fa fa-shopping-cart';
		} else if(version_compare(JVERSION, '3.0', '>=')) {
			$img = 'cart';
		} else {
			$img = JURI::base().'../media/com_hikashop/images/icons/icon-48-hikashop.png';
		}

		if(HIKASHOP_J50 && !class_exists('JRoute'))
			class_alias('Joomla\CMS\Router\Route', 'JRoute');
		if(HIKASHOP_J50 && !class_exists('JText'))
			class_alias('Joomla\CMS\Language\Text', 'JText');

		$result = array(
			array(
				'link' => JRoute::_('index.php?option=com_hikashop'),
				'image' => $img,
				'text' => $this->params->get('displayedtext', JText::_('HIKASHOP')),
				'access' => array('core.manage', 'com_hikashop'),
				'id' => 'plg_quickicon_hikashop',
				'group' => 'MOD_QUICKICON_EXTENSIONS',
			)
		);
		if(!is_null($context) && !is_string($context) && is_object($context) && method_exists($context, 'getArgument')) {
			$resultArray = $context->getArgument('result', []);
			$resultArray[] = $result;
			$context->setArgument('result', $resultArray);
			return;
		}	
		return $result;
	}
}
