<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
$jversion = preg_replace('#[^0-9\.]#i','',JVERSION);
if(!defined('HIKASHOP_J50')) define('HIKASHOP_J50',version_compare($jversion,'5.0.0','>=') ? true : false);
if(!class_exists('hikashopJoomlaPlugin')) {
    if(version_compare($jversion,'4.0.0','>=')) {
        include_once(__DIR__.'/pluginCompatJ4.php');
    } else {
        class hikashopJoomlaPlugin extends JPlugin{}
    }
}




