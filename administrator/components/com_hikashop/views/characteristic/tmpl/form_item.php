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
	$this->characteristic_value_input = "data[characteristic][characteristic_value]";
	if($this->translation){
		$this->setLayout('translation');
	}else{
		$this->setLayout('normal');
	}
	echo $this->loadTemplate();
?>
